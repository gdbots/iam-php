<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Aggregate;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;

class RoleAggregate extends Aggregate
{
    protected function __construct(Message $node, Pbjx $pbjx, bool $syncAllEvents = false)
    {
        parent::__construct($node, $pbjx, $syncAllEvents);
        $this->node->set('title', $this->nodeRef->getId());

        // roles are only published or deleted, enforce it.
        if (NodeStatus::DELETED !== $this->node->fget('status')) {
            $this->node->set('status', NodeStatus::PUBLISHED());
        }
    }

    public function useSoftDelete(): bool
    {
        return false;
    }

    protected function enrichNodeCreated(Message $event): void
    {
        /** @var Message $node */
        $node = $event->get('node');
        $node->set('title', $this->nodeRef->getId());
        parent::enrichNodeCreated($event);
    }

    protected function enrichNodeUpdated(Message $event): void
    {
        /** @var Message $newNode */
        $newNode = $event->get('new_node');
        $newNode->set('title', $this->nodeRef->getId());

        // roles are only published or deleted, enforce it.
        if (NodeStatus::DELETED !== $newNode->fget('status')) {
            $newNode->set('status', NodeStatus::PUBLISHED());
        }

        parent::enrichNodeUpdated($event);
    }

    /**
     * This is for legacy uses of command/event mixins for common
     * ncr operations. It will be removed in 3.x.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $newName = str_replace('Role', 'Node', $name);
        if ($newName !== $name && is_callable([$this, $newName])) {
            return $this->$newName(...$arguments);
        }
    }
}
