<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbjx\DependencyInjection\PbjxProjector;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Schemas\Iam\Mixin\RoleCreated\RoleCreated;
use Gdbots\Schemas\Iam\Mixin\RoleDeleted\RoleDeleted;
use Gdbots\Schemas\Iam\Mixin\RoleUpdated\RoleUpdated;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;

class RoleProjector implements PbjxProjector
{
    use EventSubscriberTrait;

    /** @var Ncr */
    protected $ncr;

    /**
     * @param Ncr $ncr
     */
    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    /**
     * @param RoleCreated $event
     */
    public function onRoleCreated(RoleCreated $event): void
    {
        $node = $event->get('node');
        $this->ncr->putNode($node);
    }

    /**
     * @param RoleUpdated $event
     */
    public function onRoleUpdated(RoleUpdated $event): void
    {
        $newNode = $event->get('new_node');
        $expectedEtag = $event->isReplay() ? null : $event->get('old_etag');
        $this->ncr->putNode($newNode, $expectedEtag);
    }

    /**
     * @param RoleDeleted $event
     */
    public function onRoleDeleted(RoleDeleted $event): void
    {
        $this->ncr->deleteNode($event->get('node_ref'));
    }

    /**
     * @param Node  $node
     * @param Event $event
     */
    protected function putNode(Node $node, Event $event): void
    {
        $expectedEtag = $node->get('etag');
        $node
            ->set('updated_at', $event->get('occurred_at'))
            ->set('updater_ref', $event->get('ctx_user_ref'))
            ->set('last_event_ref', $event->generateMessageRef())
            ->set('etag', $node->generateEtag(['etag', 'updated_at']));

        $this->ncr->putNode($node, $expectedEtag);
    }
}
