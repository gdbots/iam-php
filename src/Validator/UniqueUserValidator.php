<?php
declare(strict_types=1);

namespace Gdbots\Iam\Validator;

use Gdbots\Iam\Exception\UserAlreadyExists;
use Gdbots\Pbj\Assertion;
use Gdbots\Pbj\WellKnown\Identifier;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Exception\RequestHandlingFailed;
use Gdbots\Schemas\Iam\Mixin\CreateUser\CreateUser;
use Gdbots\Schemas\Iam\Mixin\GetUserRequest\GetUserRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\UpdateUser\UpdateUser;
use Gdbots\Schemas\Iam\Mixin\User\UserV1Mixin;
use Gdbots\Schemas\Pbjx\Enum\Code;
use Gdbots\Schemas\Pbjx\Mixin\Request\Request;
use Gdbots\Schemas\Pbjx\StreamId;

final class UniqueUserValidator implements EventSubscriber
{
    /**
     * @param PbjxEvent $pbjxEvent
     */
    public function validateCreateUser(PbjxEvent $pbjxEvent): void
    {
        /** @var CreateUser $command */
        $command = $pbjxEvent->getMessage();

        Assertion::true($command->has('node'), 'Field "node" is required.', 'node');
        $node = $command->get('node');
        Assertion::true($node->has('email'), 'Field "node.email" is required.', 'node.email');

        $email = strtolower($node->get('email'));
        $emailParts = explode('@', $email);
        $node->set('email', $email);
        $node->set('email_domain', array_pop($emailParts));

        $this->ensureEmailIsAvailable($pbjxEvent, $node->get('email'), $node->get('_id'));
        $this->ensureIdDoesNotExist($pbjxEvent, $node->get('_id'));
    }

    /**
     * @param PbjxEvent $pbjxEvent
     */
    public function validateUpdateUser(PbjxEvent $pbjxEvent): void
    {
        /** @var UpdateUser $command */
        $command = $pbjxEvent->getMessage();

        Assertion::true($command->has('new_node'), 'Field "new_node" is required.', 'new_node');
        $newNode = $command->get('new_node');

        /*
         * An update SHOULD NOT change the email, so copy the email from
         * the old node if it's present. To change the email, use the
         * proper "change-email" command.
         */
        if ($command->has('old_node')) {
            $oldNode = $command->get('old_node');
            $newNode->set('email', $oldNode->get('email'));
            $newNode->set('email_domain', $oldNode->get('email_domain'));
            return;
        }

        $email = strtolower($newNode->get('email'));
        $emailParts = explode('@', $email);
        $newNode->set('email', $email);
        $newNode->set('email_domain', array_pop($emailParts));

        $this->ensureEmailIsAvailable($pbjxEvent, $newNode->get('email'), $newNode->get('_id'));
    }

    /**
     * @param PbjxEvent  $pbjxEvent
     * @param string     $email
     * @param Identifier $currentUserId
     *
     * @throws UserAlreadyExists
     * @throws \Exception
     */
    private function ensureEmailIsAvailable(PbjxEvent $pbjxEvent, string $email, ?Identifier $currentUserId = null): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $message = $pbjxEvent->getMessage();

        try {
            $getUserSchema = GetUserRequestV1Mixin::findOne();
            $userSchema = UserV1Mixin::findOne();
            /** @var Request $request */
            $request = $getUserSchema->createMessage()
                ->set('consistent_read', true)
                ->set('qname', $userSchema->getQName()->toString())
                ->set('email', $email);

            $response = $pbjx->copyContext($message, $request)->request($request);
        } catch (RequestHandlingFailed $e) {
            if (Code::NOT_FOUND === $e->getResponse()->get('error_code')) {
                // this is what we want
                return;
            }

            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }

        if (null !== $currentUserId && $currentUserId->equals($response->get('node')->get('_id'))) {
            // this is the same user.
            return;
        }

        throw new UserAlreadyExists(
            sprintf(
                'User with email [%s] already exists so [%s] cannot continue.',
                $email,
                $message->generateMessageRef()
            )
        );
    }

    /**
     * @param PbjxEvent  $pbjxEvent
     * @param Identifier $userId
     *
     * @throws UserAlreadyExists
     */
    private function ensureIdDoesNotExist(PbjxEvent $pbjxEvent, Identifier $userId): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $message = $pbjxEvent->getMessage();

        $streamId = StreamId::fromString("user.history:{$userId}");
        $slice = $pbjx->getEventStore()->getStreamSlice($streamId, null, 1, true, true);

        if ($slice->count()) {
            throw new UserAlreadyExists(
                sprintf(
                    'User with id [%s] already exists so [%s] cannot continue.',
                    $userId,
                    $message->generateMessageRef()
                )
            );
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'gdbots:iam:mixin:create-user.validate' => 'validateCreateUser',
            'gdbots:iam:mixin:update-user.validate' => 'validateUpdateUser',
        ];
    }
}
