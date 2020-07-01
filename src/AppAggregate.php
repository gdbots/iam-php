<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Aggregate;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Common\Mixin\Taggable\TaggableV1Mixin;
use Gdbots\Schemas\Iam\Event\AppRolesGrantedV1;
use Gdbots\Schemas\Iam\Event\AppRolesRevokedV1;
use Gdbots\Schemas\Iam\Mixin\App\AppV1Mixin;
use Gdbots\Schemas\Iam\Mixin\Role\RoleV1Mixin;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Event\NodeUpdatedV1;
use Gdbots\Schemas\Ncr\Mixin\Node\NodeV1Mixin;

class AppAggregate extends Aggregate
{
    protected function __construct(Message $node, Pbjx $pbjx, bool $syncAllEvents = false)
    {
        parent::__construct($node, $pbjx, $syncAllEvents);
        // apps are only published or deleted, enforce it.
        if (NodeStatus::DELETED !== $this->node->fget(NodeV1Mixin::STATUS_FIELD)) {
            $this->node->set(NodeV1Mixin::STATUS_FIELD, NodeStatus::PUBLISHED());
        }
    }

    public function grantRolesToApp(Message $command): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get($command::NODE_REF_FIELD);
        $this->assertNodeRefMatches($nodeRef);

        $roleQname = SchemaCurie::fromString(
            MessageResolver::findOneUsingMixin(RoleV1Mixin::SCHEMA_CURIE_MAJOR, false)
        )->getQName();

        /** @var NodeRef $ref */
        foreach ($command->get($command::ROLES_FIELD, []) as $ref) {
            if ($ref->getQName() === $roleQname && !$this->node->isInSet(AppV1Mixin::ROLES_FIELD, $ref)) {
                $roles[] = $ref;
            }
        }

        if (empty($roles)) {
            // nothing to do
            return;
        }

        $event = $this->createAppRolesGranted($command);
        $this->pbjx->copyContext($command, $event);
        $event->set($event::NODE_REF_FIELD, $this->nodeRef);
        $event->addToSet($event::ROLES_FIELD, $roles);

        if ($event::schema()->hasMixin(TaggableV1Mixin::SCHEMA_CURIE)) {
            foreach ($command->get($event::TAGS_FIELD, []) as $k => $v) {
                $event->addToMap($event::TAGS_FIELD, $k, $v);
            }
        }

        $this->recordEvent($event);
    }

    public function revokeRolesFromApp(Message $command): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get($command::NODE_REF_FIELD);
        $this->assertNodeRefMatches($nodeRef);

        $roleQname = SchemaCurie::fromString(
            MessageResolver::findOneUsingMixin(RoleV1Mixin::SCHEMA_CURIE_MAJOR, false)
        )->getQName();

        /** @var NodeRef $ref */
        foreach ($command->get($command::ROLES_FIELD, []) as $ref) {
            if ($ref->getQName() === $roleQname && $this->node->isInSet(AppV1Mixin::ROLES_FIELD, $ref)) {
                $roles[] = $ref;
            }
        }

        if (empty($roles)) {
            // nothing to do
            return;
        }

        $event = $this->createAppRolesRevoked($command);
        $this->pbjx->copyContext($command, $event);
        $event->set($event::NODE_REF_FIELD, $this->nodeRef);
        $event->addToSet($event::ROLES_FIELD, $roles);

        if ($event::schema()->hasMixin(TaggableV1Mixin::SCHEMA_CURIE)) {
            foreach ($command->get($event::TAGS_FIELD, []) as $k => $v) {
                $event->addToMap($event::TAGS_FIELD, $k, $v);
            }
        }

        $this->recordEvent($event);
    }

    protected function applyAppRolesGranted(Message $event): void
    {
        $this->node->addToSet(
            AppV1Mixin::ROLES_FIELD,
            $event->get(AppRolesGrantedV1::ROLES_FIELD, [])
        );
    }

    protected function applyAppRolesRevoked(Message $event): void
    {
        $this->node->removeFromSet(
            AppV1Mixin::ROLES_FIELD,
            $event->get(AppRolesRevokedV1::ROLES_FIELD, [])
        );
    }

    protected function enrichNodeUpdated(Message $event): void
    {
        /** @var Message $newNode */
        $newNode = $event->get(NodeUpdatedV1::NEW_NODE_FIELD);

        // apps are only published or deleted, enforce it.
        if (NodeStatus::DELETED !== $newNode->fget(NodeV1Mixin::STATUS_FIELD)) {
            $newNode->set(NodeV1Mixin::STATUS_FIELD, NodeStatus::PUBLISHED());
        }

        parent::enrichNodeUpdated($event);
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createAppRolesGranted(Message $command): Message
    {
        return AppRolesGrantedV1::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param Message $command
     *
     * @return Message
     *
     * @deprecated Will be removed in 3.x.
     */
    protected function createAppRolesRevoked(Message $command): Message
    {
        return AppRolesRevokedV1::create();
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $name , array $arguments)
    {
        $newName = str_replace('App', 'Node', $name);
        if ($newName !== $name && is_callable([$this, $newName])) {
            return $this->$newName(...$arguments);
        }
    }
}
