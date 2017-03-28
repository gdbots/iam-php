<?php
declare(strict_types = 1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\RoleCreated\RoleCreated;
use Gdbots\Schemas\Iam\Mixin\RoleDeleted\RoleDeleted;
use Gdbots\Schemas\Iam\Mixin\RoleUpdated\RoleUpdated;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;

class RoleProjector
{
    use EventSubscriberTrait;

    /** @var Ncr */
    protected $ncr;

    /**
     * @param Ncr       $ncr
     */
    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    /**
     * @param RoleCreated $event
     * @param Pbjx        $pbjx
     */
    public function onRoleCreated(RoleCreated $event, Pbjx $pbjx): void
    {
        $node = $event->get('node');
        $this->ncr->putNode($node, null);
    }

    /**
     * @param RoleUpdated $event
     * @param Pbjx        $pbjx
     */
    public function onRoleUpdated(RoleUpdated $event, Pbjx $pbjx): void
    {
        $newNode = $event->get('new_node');
        $expectedEtag = $event->isReplay() ? null : $event->get('old_etag');
        $this->ncr->putNode($newNode, $expectedEtag);
    }

    /**
     * @param RoleDeleted $event
     * @param Pbjx        $pbjx
     */
    public function onRoleDeleted(RoleDeleted $event, Pbjx $pbjx): void
    {
        $node = $this->ncr->getNode($event->get('node_ref'), true);
        // using soft delete for roles
        $node->set('status', NodeStatus::DELETED());
        $this->putNode($node, $event);
    }

    /**
     * @param Node    $node
     * @param Message $event
     */
    protected function putNode(Node $node, Message $event): void
    {
        $expectedEtag = $node->get('etag');
        $node
            ->set('updated_at', $event->get('occurred_at'))
            ->set('updater_ref', $event->get('ctx_user_ref'))
            ->set('last_event_ref', $event->generateMessageRef())
            ->set('etag', $node->generateEtag(['etag', 'updated_at']));

        $this->ncr->putNode($node, $expectedEtag);

        if ($event->isReplay()) {
            // on replay we don't want to reindex, we generally do that
            // as a separate task, in batches, using console ncr:reindex-nodes
            return;
        }
    }
}
