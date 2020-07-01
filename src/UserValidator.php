<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Iam\Exception\UserAlreadyExists;
use Gdbots\Pbj\Assertion;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\DependencyInjection\PbjxValidator;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Exception\RequestHandlingFailed;
use Gdbots\Schemas\Iam\Mixin\User\UserV1Mixin;
use Gdbots\Schemas\Iam\Request\GetUserRequestV1;
use Gdbots\Schemas\Ncr\Command\CreateNodeV1;
use Gdbots\Schemas\Ncr\Command\UpdateNodeV1;
use Gdbots\Schemas\Ncr\Mixin\Node\NodeV1Mixin;
use Gdbots\Schemas\Pbjx\Enum\Code;

class UserValidator implements EventSubscriber, PbjxValidator
{
    public static function getSubscribedEvents()
    {
        return [
            UserV1Mixin::SCHEMA_CURIE . '.validate' => 'validate',
        ];
    }

    public function validate(PbjxEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->hasParentEvent() ? $pbjxEvent->getParentEvent() : $pbjxEvent;
        $method = $event->getMessage()::schema()->getHandlerMethodName(false, 'validate');
        if (is_callable([$this, $method])) {
            $this->$method($event);
        }
    }

    protected function validateCreateUser(PbjxEvent $pbjxEvent): void
    {
        $this->validateCreateNode($pbjxEvent);
    }

    protected function validateCreateNode(PbjxEvent $pbjxEvent): void
    {
        $command = $pbjxEvent->getMessage();

        Assertion::true($command->has(CreateNodeV1::NODE_FIELD), 'Field "node" is required.', 'node');
        /** @var Message $node */
        $node = $command->get(CreateNodeV1::NODE_FIELD);
        Assertion::true($node->has(UserV1Mixin::EMAIL_FIELD), 'Field "node.email" is required.', 'node.email');

        $this->normalizeEmail($node);
        $this->ensureEmailIsAvailable($pbjxEvent, NodeRef::fromNode($node), $node->get(UserV1Mixin::EMAIL_FIELD));
    }

    protected function validateUpdateUser(PbjxEvent $pbjxEvent): void
    {
        $this->validateUpdateNode($pbjxEvent);
    }

    protected function validateUpdateNode(PbjxEvent $pbjxEvent): void
    {
        $command = $pbjxEvent->getMessage();

        Assertion::true($command->has(UpdateNodeV1::NEW_NODE_FIELD), 'Field "new_node" is required.', 'new_node');
        $newNode = $command->get(UpdateNodeV1::NEW_NODE_FIELD);
        Assertion::true($newNode->has(UserV1Mixin::EMAIL_FIELD), 'Field "new_node.email" is required.', 'new_node.email');

        /*
         * An update SHOULD NOT change the email, so copy the email from
         * the old node if it's present. To change the email, use the
         * proper "change-email" command.
         */
        if ($command->has(UpdateNodeV1::OLD_NODE_FIELD)) {
            $oldNode = $command->get(UpdateNodeV1::OLD_NODE_FIELD);
            $newNode->set(UserV1Mixin::EMAIL_FIELD, $oldNode->get(UserV1Mixin::EMAIL_FIELD));
            $this->normalizeEmail($newNode);
            return;
        }

        $this->normalizeEmail($newNode);
        $this->ensureEmailIsAvailable(
            $pbjxEvent,
            $command->get(UpdateNodeV1::NODE_REF_FIELD),
            $newNode->get(UserV1Mixin::EMAIL_FIELD)
        );
    }

    protected function normalizeEmail(Message $node): void
    {
        $email = strtolower($node->get(UserV1Mixin::EMAIL_FIELD));
        $emailParts = explode('@', $email);
        $node->set(UserV1Mixin::EMAIL_FIELD, $email);
        $node->set(UserV1Mixin::EMAIL_DOMAIN_FIELD, array_pop($emailParts));
    }

    protected function ensureEmailIsAvailable(PbjxEvent $pbjxEvent, NodeRef $nodeRef, string $email): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $command = $pbjxEvent->getMessage();

        try {
            $request = GetUserRequestV1::create()
                ->set(GetUserRequestV1::CONSISTENT_READ_FIELD, true)
                ->set(GetUserRequestV1::QNAME_FIELD, $nodeRef->getQName()->toString())
                ->set(GetUserRequestV1::EMAIL_FIELD, $email);

            $response = $pbjx->copyContext($command, $request)->request($request);
        } catch (RequestHandlingFailed $e) {
            if (Code::NOT_FOUND === $e->getCode()) {
                // this is what we want
                return;
            }

            throw $e;
        } catch (\Throwable $t) {
            throw $t;
        }

        /** @var Message $node */
        $node = $response->get($response::NODE_FIELD);

        if ($nodeRef->getId() === $node->fget(NodeV1Mixin::_ID_FIELD)) {
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
}
