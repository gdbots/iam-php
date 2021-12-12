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
use Gdbots\Schemas\Iam\Request\GetUserRequestV1;
use Gdbots\Schemas\Pbjx\Enum\Code;

class UserValidator implements EventSubscriber, PbjxValidator
{
    public static function getSubscribedEvents(): array
    {
        return [
            'gdbots:iam:mixin:user.validate' => 'validate',
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

        Assertion::true($command->has('node'), 'Field "node" is required.', 'node');
        /** @var Message $node */
        $node = $command->get('node');
        Assertion::true($node->has('email'), 'Field "node.email" is required.', 'node.email');

        $this->normalizeEmail($node);
        $this->ensureEmailIsAvailable($pbjxEvent, NodeRef::fromNode($node), $node->get('email'));
    }

    protected function validateUpdateUser(PbjxEvent $pbjxEvent): void
    {
        $this->validateUpdateNode($pbjxEvent);
    }

    protected function validateUpdateNode(PbjxEvent $pbjxEvent): void
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

    protected function normalizeEmail(Message $node): void
    {
        $email = strtolower($node->get('email'));
        $emailParts = explode('@', $email);
        $node->set('email', $email);
        $node->set('email_domain', array_pop($emailParts));
    }

    protected function ensureEmailIsAvailable(PbjxEvent $pbjxEvent, NodeRef $nodeRef, string $email): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $command = $pbjxEvent->getMessage();

        try {
            $request = GetUserRequestV1::create()
                ->set('consistent_read', true)
                ->set('qname', $nodeRef->getQName()->toString())
                ->set('email', $email);

            $response = $pbjx->copyContext($command, $request)->request($request);
        } catch (RequestHandlingFailed $e) {
            if (Code::NOT_FOUND->value === $e->getCode()) {
                // this is what we want
                return;
            }

            throw $e;
        } catch (\Throwable $t) {
            throw $t;
        }

        /** @var Message $node */
        $node = $response->get('node');

        if ($nodeRef->getId() === $node->fget('_id')) {
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
