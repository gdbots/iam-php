<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractNodeProjector;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\App\AppV1Mixin;
use Gdbots\Schemas\Iam\Mixin\AppRolesGranted\AppRolesGranted;
use Gdbots\Schemas\Iam\Mixin\AppRolesRevoked\AppRolesRevoked;
use Gdbots\Schemas\Ncr\Mixin\NodeCreated\NodeCreated;
use Gdbots\Schemas\Ncr\Mixin\NodeDeleted\NodeDeleted;
use Gdbots\Schemas\Ncr\Mixin\NodeUpdated\NodeUpdated;
use Gdbots\Schemas\Ncr\NodeRef;

class NcrAppProjector extends AbstractNodeProjector implements EventSubscriber
{
    use EventSubscriberTrait;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $curie = AppV1Mixin::findOne()->getCurie();
        return [
            "{$curie->getVendor()}:{$curie->getPackage()}:event:*" => 'onEvent',
        ];
    }

    /**
     * @param NodeCreated $event
     * @param Pbjx        $pbjx
     */
    public function onAppCreated(NodeCreated $event, Pbjx $pbjx): void
    {
        $this->handleNodeCreated($event, $pbjx);
    }

    /**
     * @param NodeDeleted $event
     * @param Pbjx        $pbjx
     */
    public function onAppDeleted(NodeDeleted $event, Pbjx $pbjx): void
    {
        $this->handleNodeDeleted($event, $pbjx);
    }

    /**
     * @param AppRolesGranted $event
     * @param Pbjx             $pbjx
     */
    public function onAppRolesGranted(AppRolesGranted $event, Pbjx $pbjx): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $event->get('node_ref');
        $node = $this->ncr->getNode($nodeRef, true, $this->createNcrContext($event));
        $node->addToSet('roles', $event->get('roles', []));
        $this->updateAndIndexNode($node, $event, $pbjx);
    }

    /**
     * @param AppRolesRevoked $event
     * @param Pbjx             $pbjx
     */
    public function onAppRolesRevoked(AppRolesRevoked $event, Pbjx $pbjx): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $event->get('node_ref');
        $node = $this->ncr->getNode($nodeRef, true, $this->createNcrContext($event));
        $node->removeFromSet('roles', $event->get('roles', []));
        $this->updateAndIndexNode($node, $event, $pbjx);
    }

    /**
     * @param NodeUpdated $event
     * @param Pbjx        $pbjx
     */
    public function onAppUpdated(NodeUpdated $event, Pbjx $pbjx): void
    {
        $this->handleNodeUpdated($event, $pbjx);
    }
}
