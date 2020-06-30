<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\NcrProjector;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\User\UserV1Mixin;

class NcrUserProjector extends NcrProjector
{
    use EventSubscriberTrait;

    public static function getSubscribedEvents()
    {
        $curie = UserV1Mixin::findOne()->getCurie();
        return [
            "{$curie->getVendor()}:{$curie->getPackage()}:event:*" => 'onEvent',
        ];
    }

    /**
     * @param UserRolesGranted $event
     * @param Pbjx             $pbjx
     */
    public function onUserRolesGranted(UserRolesGranted $event, Pbjx $pbjx): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $event->get('node_ref');
        $node = $this->ncr->getNode($nodeRef, true, $this->createNcrContext($event));
        $node->addToSet('roles', $event->get('roles', []));
        $this->updateAndIndexNode($node, $event, $pbjx);
    }

    /**
     * @param UserRolesRevoked $event
     * @param Pbjx             $pbjx
     */
    public function onUserRolesRevoked(UserRolesRevoked $event, Pbjx $pbjx): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $event->get('node_ref');
        $node = $this->ncr->getNode($nodeRef, true, $this->createNcrContext($event));
        $node->removeFromSet('roles', $event->get('roles', []));
        $this->updateAndIndexNode($node, $event, $pbjx);
    }
}
