<?php
declare(strict_types=1);

namespace Gdbots\Iam\Validator;

use Gdbots\Iam\Exception\UserAlreadyExists;
use Gdbots\Pbj\Assertion;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\DependencyInjection\PbjxValidator;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Exception\RequestHandlingFailed;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\GetUserRequest\GetUserRequest;
use Gdbots\Schemas\Iam\Mixin\GetUserRequest\GetUserRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\User\User;
use Gdbots\Schemas\Iam\Mixin\User\UserV1Mixin;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Enum\Code;

class UserValidator implements EventSubscriber, PbjxValidator
{
    /**
     * @param PbjxEvent $pbjxEvent
     */
    public function validateCreateUser(PbjxEvent $pbjxEvent): void
    {
        $command = $pbjxEvent->getMessage();

        Assertion::true($command->has('node'), 'Field "node" is required.', 'node');
        $node = $command->get('node');
        Assertion::true($node->has('email'), 'Field "node.email" is required.', 'node.email');

        $this->normalizeEmail($node);
        $this->ensureEmailIsAvailable($pbjxEvent, NodeRef::fromNode($node), $node->get('email'));
    }

    /**
     * @param PbjxEvent $pbjxEvent
     */
    public function validateUpdateUser(PbjxEvent $pbjxEvent): void
    {
        $command = $pbjxEvent->getMessage();

        Assertion::true($command->has('new_node'), 'Field "new_node" is required.', 'new_node');
        $newNode = $command->get('new_node');
        Assertion::true($newNode->has('email'), 'Field "new_node.email" is required.', 'new_node.email');

        /*
         * An update SHOULD NOT change the email, so copy the email from
         * the old node if it's present. To change the email, use the
         * proper "change-email" command.
         */
        if ($command->has('old_node')) {
            $oldNode = $command->get('old_node');
            $newNode->set('email', $oldNode->get('email'));
            $this->normalizeEmail($newNode);
            return;
        }

        $this->normalizeEmail($newNode);
        $this->ensureEmailIsAvailable($pbjxEvent, $command->get('node_ref'), $newNode->get('email'));
    }

    /**
     * @param User $user
     */
    protected function normalizeEmail(User $user): void
    {
        $email = strtolower($user->get('email'));
        $emailParts = explode('@', $email);
        $user->set('email', $email);
        $user->set('email_domain', array_pop($emailParts));
    }

    /**
     * @param PbjxEvent $pbjxEvent
     * @param NodeRef   $nodeRef
     * @param string    $email
     *
     * @throws UserAlreadyExists
     * @throws \Throwable
     */
    protected function ensureEmailIsAvailable(PbjxEvent $pbjxEvent, NodeRef $nodeRef, string $email): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $command = $pbjxEvent->getMessage();

        try {
            $request = $this->createGetUserRequest($command, $nodeRef, $pbjx)
                ->set('consistent_read', true)
                ->set('qname', $nodeRef->getQName()->toString())
                ->set('email', $email);

            $response = $pbjx->copyContext($command, $request)->request($request);
        } catch (RequestHandlingFailed $e) {
            if (Code::NOT_FOUND === $e->getResponse()->get('error_code')) {
                // this is what we want
                return;
            }

            throw $e;
        } catch (\Throwable $t) {
            throw $t;
        }

        /** @var User $node */
        $node = $response->get('node');

        if ($nodeRef->getId() === (string)$node->get('_id')) {
            // this is the same node.
            return;
        }

        throw new UserAlreadyExists(
            sprintf(
                'User with email [%s] already exists so [%s] cannot continue.',
                $email,
                $command->generateMessageRef()
            )
        );
    }

    /**
     * @param Message $message
     * @param NodeRef $nodeRef
     * @param Pbjx    $pbjx
     *
     * @return GetUserRequest
     */
    protected function createGetUserRequest(Message $message, NodeRef $nodeRef, Pbjx $pbjx): GetUserRequest
    {
        /** @var GetUserRequest $request */
        $request = GetUserRequestV1Mixin::findOne()->createMessage();
        return $request;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        $curie = UserV1Mixin::findOne()->getCurie();
        $prefix = "{$curie->getVendor()}:{$curie->getPackage()}:command:";
        return [
            "{$prefix}create-user.validate" => 'validateCreateUser',
            "{$prefix}update-user.validate" => 'validateUpdateUser',
        ];
    }
}
