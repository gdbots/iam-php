<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Aggregate;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Event\UserRolesGrantedV1;
use Gdbots\Schemas\Iam\Event\UserRolesRevokedV1;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;

class UserAggregate extends Aggregate
{
    protected function __construct(Message $node, Pbjx $pbjx, bool $syncAllEvents = false)
    {
        parent::__construct($node, $pbjx, $syncAllEvents);
        // users are only published or deleted, enforce it.
        if (NodeStatus::DELETED->value !== $this->node->fget('status')) {
            $this->node->set('status', NodeStatus::PUBLISHED);
        }
    }

    public function grantRolesToUser(Message $command): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');
        $this->assertNodeRefMatches($nodeRef);

        $roleQname = SchemaCurie::fromString(
            MessageResolver::findOneUsingMixin('gdbots:iam:mixin:role:v1', false)
        )->getQName();

        /** @var NodeRef $ref */
        foreach ($command->get('roles', []) as $ref) {
            if ($ref->getQName() === $roleQname && !$this->node->isInSet('roles', $ref)) {
                $roles[] = $ref;
            }
        }

        if (empty($roles)) {
            // nothing to do
            return;
        }

        $event = $this->createUserRolesGranted($command);
        $this->copyContext($command, $event);
        $event->set('node_ref', $this->nodeRef);
        $event->addToSet('roles', $roles);

        $this->recordEvent($event);
    }

    public function revokeRolesFromUser(Message $command): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');
        $this->assertNodeRefMatches($nodeRef);

        $roleQname = SchemaCurie::fromString(
            MessageResolver::findOneUsingMixin('gdbots:iam:mixin:role:v1', false)
        )->getQName();

        /** @var NodeRef $ref */
        foreach ($command->get('roles', []) as $ref) {
            if ($ref->getQName() === $roleQname && $this->node->isInSet('roles', $ref)) {
                $roles[] = $ref;
            }
        }

        if (empty($roles)) {
            // nothing to do
            return;
        }

        $event = $this->createUserRolesRevoked($command);
        $this->copyContext($command, $event);
        $event->set('node_ref', $this->nodeRef);
        $event->addToSet('roles', $roles);

        $this->recordEvent($event);
    }

    protected function applyUserRolesGranted(Message $event): void
    {
        $this->node->addToSet('roles', $event->get('roles', []));
    }

    protected function applyUserRolesRevoked(Message $event): void
    {
        $this->node->removeFromSet('roles', $event->get('roles', []));
    }

    protected function enrichNodeCreated(Message $event): void
    {
        /** @var Message $node */
        $node = $event->get('node');

        // roles SHOULD be set with grant-roles-to-user
        $node->clear('roles');
        $this->setDefaultTitle($node);
        $this->setEmailDomain($node);

        parent::enrichNodeCreated($event);
    }

    protected function enrichNodeUpdated(Message $event): void
    {
        /** @var Message $oldNode */
        $oldNode = $event->get('old_node');

        /** @var Message $newNode */
        $newNode = $event->get('new_node');

        $newNode
            // email SHOULD NOT change during an update, use "change-email"
            ->set('email', $oldNode->get('email'))
            ->set('email_domain', $oldNode->get('email_domain'))
            // roles SHOULD NOT change during an update
            ->clear('roles')
            ->addToSet('roles', $oldNode->get('roles', []));

        // users are only published or deleted, enforce it.
        if (NodeStatus::DELETED->value !== $newNode->fget('status')) {
            $newNode->set('status', NodeStatus::PUBLISHED);
        }

        $this->setDefaultTitle($newNode);
        $this->setEmailDomain($newNode);

        parent::enrichNodeUpdated($event);
    }

    protected function setDefaultTitle(Message $node): void
    {
        if ($node->has('title')) {
            return;
        }

        $firstName = $node->get('first_name');
        $lastName = $node->get('last_name');
        $title = trim("{$firstName} {$lastName}");
        $node->set('title', $title);
    }

    protected function setEmailDomain(Message $node): void
    {
        if (!$node->has('email')) {
            $node->clear('email_domain');
            return;
        }

        $email = strtolower($node->get('email'));
        $emailParts = explode('@', $email);
        $node->set('email', $email);
        $node->set('email_domain', array_pop($emailParts));
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 4.x.
     */
    protected function createUserRolesGranted(Message $command): Message
    {
        return UserRolesGrantedV1::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 4.x.
     */
    protected function createUserRolesRevoked(Message $command): Message
    {
        return UserRolesRevokedV1::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 4.x.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $newName = str_replace('User', 'Node', $name);
        if ($newName !== $name && is_callable([$this, $newName])) {
            return $this->$newName(...$arguments);
        }
    }
}
