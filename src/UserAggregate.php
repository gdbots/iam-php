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
use Gdbots\Schemas\Iam\Event\UserRolesGrantedV1;
use Gdbots\Schemas\Iam\Event\UserRolesRevokedV1;
use Gdbots\Schemas\Iam\Mixin\Role\RoleV1Mixin;
use Gdbots\Schemas\Iam\Mixin\User\UserV1Mixin;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Event\NodeCreatedV1;
use Gdbots\Schemas\Ncr\Event\NodeUpdatedV1;
use Gdbots\Schemas\Ncr\Mixin\Node\NodeV1Mixin;

class UserAggregate extends Aggregate
{
    protected function __construct(Message $node, Pbjx $pbjx, bool $syncAllEvents = false)
    {
        parent::__construct($node, $pbjx, $syncAllEvents);
        // users are only published or deleted, enforce it.
        if (NodeStatus::DELETED !== $this->node->fget(NodeV1Mixin::STATUS_FIELD)) {
            $this->node->set(NodeV1Mixin::STATUS_FIELD, NodeStatus::PUBLISHED());
        }
    }

    public function grantRolesToUser(Message $command): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get($command::NODE_REF_FIELD);
        $this->assertNodeRefMatches($nodeRef);

        $roleQname = SchemaCurie::fromString(
            MessageResolver::findOneUsingMixin(RoleV1Mixin::SCHEMA_CURIE_MAJOR, false)
        )->getQName();

        /** @var NodeRef $ref */
        foreach ($command->get($command::ROLES_FIELD, []) as $ref) {
            if ($ref->getQName() === $roleQname && !$this->node->isInSet(UserV1Mixin::ROLES_FIELD, $ref)) {
                $roles[] = $ref;
            }
        }

        if (empty($roles)) {
            // nothing to do
            return;
        }

        $event = $this->createUserRolesGranted($command);
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

    public function revokeRolesFromUser(Message $command): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get($command::NODE_REF_FIELD);
        $this->assertNodeRefMatches($nodeRef);

        $roleQname = SchemaCurie::fromString(
            MessageResolver::findOneUsingMixin(RoleV1Mixin::SCHEMA_CURIE_MAJOR, false)
        )->getQName();

        /** @var NodeRef $ref */
        foreach ($command->get($command::ROLES_FIELD, []) as $ref) {
            if ($ref->getQName() === $roleQname && $this->node->isInSet(UserV1Mixin::ROLES_FIELD, $ref)) {
                $roles[] = $ref;
            }
        }

        if (empty($roles)) {
            // nothing to do
            return;
        }

        $event = $this->createUserRolesRevoked($command);
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

    protected function applyUserRolesGranted(Message $event): void
    {
        $this->node->addToSet(
            UserV1Mixin::ROLES_FIELD,
            $event->get(UserRolesGrantedV1::ROLES_FIELD, [])
        );
    }

    protected function applyUserRolesRevoked(Message $event): void
    {
        $this->node->removeFromSet(
            UserV1Mixin::ROLES_FIELD,
            $event->get(UserRolesRevokedV1::ROLES_FIELD, [])
        );
    }

    protected function enrichNodeCreated(Message $event): void
    {
        /** @var Message $node */
        $node = $event->get(NodeCreatedV1::NODE_FIELD);

        // roles SHOULD be set with grant-roles-to-user
        $node->clear(UserV1Mixin::ROLES_FIELD);
        $this->setDefaultTitle($node);
        $this->setEmailDomain($node);

        parent::enrichNodeCreated($event);
    }

    protected function enrichNodeUpdated(Message $event): void
    {
        /** @var Message $oldNode */
        $oldNode = $event->get(NodeUpdatedV1::OLD_NODE_FIELD);

        /** @var Message $newNode */
        $newNode = $event->get(NodeUpdatedV1::NEW_NODE_FIELD);

        $newNode
            // email SHOULD NOT change during an update, use "change-email"
            ->set(UserV1Mixin::EMAIL_FIELD, $oldNode->get(UserV1Mixin::EMAIL_FIELD))
            ->set(UserV1Mixin::EMAIL_DOMAIN_FIELD, $oldNode->get(UserV1Mixin::EMAIL_DOMAIN_FIELD))
            // roles SHOULD NOT change during an update
            ->clear(UserV1Mixin::ROLES_FIELD)
            ->addToSet(UserV1Mixin::ROLES_FIELD, $oldNode->get(UserV1Mixin::ROLES_FIELD, []));

        // users are only published or deleted, enforce it.
        if (NodeStatus::DELETED !== $newNode->fget(NodeV1Mixin::STATUS_FIELD)) {
            $newNode->set(NodeV1Mixin::STATUS_FIELD, NodeStatus::PUBLISHED());
        }

        $this->setDefaultTitle($newNode);
        $this->setEmailDomain($newNode);

        parent::enrichNodeUpdated($event);
    }

    protected function setDefaultTitle(Message $node): void
    {
        if ($node->has(NodeV1Mixin::TITLE_FIELD)) {
            return;
        }

        $firstName = $node->get(UserV1Mixin::FIRST_NAME_FIELD);
        $lastName = $node->get(UserV1Mixin::LAST_NAME_FIELD);
        $title = trim("{$firstName} {$lastName}");
        $node->set(NodeV1Mixin::TITLE_FIELD, $title);
    }

    protected function setEmailDomain(Message $node): void
    {
        if (!$node->has(UserV1Mixin::EMAIL_FIELD)) {
            $node->clear(UserV1Mixin::EMAIL_DOMAIN_FIELD);
            return;
        }

        $email = strtolower($node->get(UserV1Mixin::EMAIL_FIELD));
        $emailParts = explode('@', $email);
        $node->set(UserV1Mixin::EMAIL_FIELD, $email);
        $node->set(UserV1Mixin::EMAIL_DOMAIN_FIELD, array_pop($emailParts));
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
    protected function createUserRolesGranted(Message $command): Message
    {
        return UserRolesGrantedV1::create();
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
    protected function createUserRolesRevoked(Message $command): Message
    {
        return UserRolesRevokedV1::create();
    }
}
