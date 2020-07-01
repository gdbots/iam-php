<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Aggregate;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Event\NodeCreatedV1;
use Gdbots\Schemas\Ncr\Event\NodeUpdatedV1;
use Gdbots\Schemas\Ncr\Mixin\Node\NodeV1Mixin;

class RoleAggregate extends Aggregate
{
    protected function __construct(Message $node, Pbjx $pbjx, bool $syncAllEvents = false)
    {
        parent::__construct($node, $pbjx, $syncAllEvents);
        $this->node->set(NodeV1Mixin::TITLE_FIELD, $this->nodeRef->getId());

        // roles are only published or deleted, enforce it.
        if (NodeStatus::DELETED !== $this->node->fget(NodeV1Mixin::STATUS_FIELD)) {
            $this->node->set(NodeV1Mixin::STATUS_FIELD, NodeStatus::PUBLISHED());
        }
    }

    public function useSoftDelete(): bool
    {
        return false;
    }

    protected function enrichNodeCreated(Message $event): void
    {
        /** @var Message $node */
        $node = $event->get(NodeCreatedV1::NODE_FIELD);
        $node->set(NodeV1Mixin::TITLE_FIELD, $this->nodeRef->getId());
        parent::enrichNodeCreated($event);
    }

    protected function enrichNodeUpdated(Message $event): void
    {
        /** @var Message $newNode */
        $newNode = $event->get(NodeUpdatedV1::NEW_NODE_FIELD);
        $newNode->set(NodeV1Mixin::TITLE_FIELD, $this->nodeRef->getId());

        // roles are only published or deleted, enforce it.
        if (NodeStatus::DELETED !== $newNode->fget(NodeV1Mixin::STATUS_FIELD)) {
            $newNode->set(NodeV1Mixin::STATUS_FIELD, NodeStatus::PUBLISHED());
        }

        parent::enrichNodeUpdated($event);
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
        $newName = str_replace('Role', 'Node', $name);
        if ($newName !== $name && is_callable([$this, $newName])) {
            return $this->$newName(...$arguments);
        }
    }
}
