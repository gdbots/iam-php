<?php
declare(strict_types = 1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\RoleCreated\RoleCreated;
use Gdbots\Schemas\Iam\Mixin\RoleDeleted\RoleDeleted;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;

class RoleProjector
{
    use EventSubscriberTrait;

    /** @var Ncr */
    protected $ncr;

    /** @var NcrSearch */
    protected $ncrSearch;

    /**
     * @param Ncr       $ncr
     * @param NcrSearch $ncrSearch
     */
    public function __construct(Ncr $ncr, NcrSearch $ncrSearch)
    {
        $this->ncr = $ncr;
        $this->ncrSearch = $ncrSearch;
    }

    /**
     * @param RoleCreated $event
     * @param Pbjx        $pbjx
     */
    public function onRoleCreated(RoleCreated $event, Pbjx $pbjx): void
    {
        $node = $event->get('node');
        $this->ncr->putNode($node, null);
        if (!$event->isReplay()) {
            $this->ncrSearch->indexNodes([$node]);
        }
    }

    /**
     * @param RoleDeleted $event
     * @param Pbjx        $pbjx
     */
    public function onRoleDeleted(RoleDeleted $event, Pbjx $pbjx): void
    {
        $this->ncr->deleteNode($event->get('node_ref'));
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

        $this->ncrSearch->indexNodes([$node]);
    }
}
