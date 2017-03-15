<?php
declare(strict_types = 1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\UserCreated\UserCreated;
use Gdbots\Schemas\Iam\Mixin\UserDeleted\UserDeleted;
use Gdbots\Schemas\Iam\Mixin\UserRolesGranted\UserRolesGranted;
use Gdbots\Schemas\Iam\Mixin\UserRolesRevoked\UserRolesRevoked;
use Gdbots\Schemas\Iam\Mixin\UserUpdated\UserUpdated;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;

class UserProjector
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
     * @param UserCreated $event
     * @param Pbjx        $pbjx
     */
    public function onUserCreated(UserCreated $event, Pbjx $pbjx): void
    {
        $node = $event->get('node');
        $this->ncr->putNode($node, null);
        if (!$event->isReplay()) {
            $this->ncrSearch->indexNodes([$node]);
        }
    }

    /**
     * @param UserUpdated $event
     * @param Pbjx        $pbjx
     */
    public function onUserUpdated(UserUpdated $event, Pbjx $pbjx): void
    {
        $newNode = $event->get('new_node');
        $expectedEtag = $event->isReplay() ? null : $event->get('old_etag');
        $this->ncr->putNode($newNode, $expectedEtag);
        if (!$event->isReplay()) {
            $this->ncrSearch->indexNodes([$newNode]);
        }
    }

    /**
     * @param UserDeleted $event
     * @param Pbjx        $pbjx
     */
    public function onUserDeleted(UserDeleted $event, Pbjx $pbjx): void
    {
        $node = $this->ncr->getNode($event->get('node_ref'), true);
        // using soft delete for users
        $node->set('status', NodeStatus::DELETED());
        $this->putNode($node, $event);
    }

    /**
     * @param UserRolesGranted $event
     * @param Pbjx             $pbjx
     */
    public function onUserRolesGranted(UserRolesGranted $event, Pbjx $pbjx): void
    {
        $node = $this->ncr->getNode($event->get('node_ref'), true);
        $node->addToSet('roles', $event->get('roles', []));
        $this->putNode($node, $event);
    }

    /**
     * @param UserRolesRevoked $event
     * @param Pbjx             $pbjx
     */
    public function onUserRolesRevoked(UserRolesRevoked $event, Pbjx $pbjx): void
    {
        $node = $this->ncr->getNode($event->get('node_ref'), true);
        $node->removeFromSet('roles', $event->get('roles', []));
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

        $this->ncrSearch->indexNodes([$node]);
    }
}
