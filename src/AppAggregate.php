<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Aggregate;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Event\AppRolesGrantedV1;
use Gdbots\Schemas\Iam\Event\AppRolesRevokedV1;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;

class AppAggregate extends Aggregate
{
    protected function __construct(Message $node, Pbjx $pbjx, bool $syncAllEvents = false)
    {
        parent::__construct($node, $pbjx, $syncAllEvents);
        // apps are only published or deleted, enforce it.
        if (NodeStatus::DELETED->value !== $this->node->fget('status')) {
            $this->node->set('status', NodeStatus::PUBLISHED);
        }
    }

    public function grantRolesToApp(Message $command): void
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

        $event = $this->createAppRolesGranted($command);
        $this->copyContext($command, $event);
        $event->set('node_ref', $this->nodeRef);
        $event->addToSet('roles', $roles);

        $this->recordEvent($event);
    }

    public function revokeRolesFromApp(Message $command): void
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

        $event = $this->createAppRolesRevoked($command);
        $this->copyContext($command, $event);
        $event->set('node_ref', $this->nodeRef);
        $event->addToSet('roles', $roles);

        $this->recordEvent($event);
    }

    protected function applyAppRolesGranted(Message $event): void
    {
        $this->node->addToSet('roles', $event->get('roles', []));
    }

    protected function applyAppRolesRevoked(Message $event): void
    {
        $this->node->removeFromSet('roles', $event->get('roles', []));
    }

    protected function enrichNodeUpdated(Message $event): void
    {
        /** @var Message $oldNode */
        $oldNode = $event->get('old_node');

        /** @var Message $newNode */
        $newNode = $event->get('new_node');

        $newNode
            // roles SHOULD NOT change during an update
            ->clear('roles')
            ->addToSet('roles', $oldNode->get('roles', []));

        // apps are only published or deleted, enforce it.
        if (NodeStatus::DELETED->value !== $newNode->fget('status')) {
            $newNode->set('status', NodeStatus::PUBLISHED);
        }

        parent::enrichNodeUpdated($event);
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
    protected function createAppRolesGranted(Message $command): Message
    {
        return AppRolesGrantedV1::create();
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
    protected function createAppRolesRevoked(Message $command): Message
    {
        return AppRolesRevokedV1::create();
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
        $newName = str_replace('App', 'Node', $name);
        if ($newName !== $name && is_callable([$this, $newName])) {
            return $this->$newName(...$arguments);
        }
    }
}
