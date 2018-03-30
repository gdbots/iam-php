<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractNodeProjector;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\UserCreated\UserCreated;
use Gdbots\Schemas\Iam\Mixin\UserCreated\UserCreatedV1Mixin;
use Gdbots\Schemas\Iam\Mixin\UserDeleted\UserDeleted;
use Gdbots\Schemas\Iam\Mixin\UserRolesGranted\UserRolesGranted;
use Gdbots\Schemas\Iam\Mixin\UserRolesRevoked\UserRolesRevoked;
use Gdbots\Schemas\Iam\Mixin\UserUpdated\UserUpdated;
use Gdbots\Schemas\Ncr\NodeRef;

class NcrUserProjector extends AbstractNodeProjector implements EventSubscriber
{
    use EventSubscriberTrait;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $curie = UserCreatedV1Mixin::findOne()->getCurie();
        return [
            "{$curie->getVendor()}:{$curie->getPackage()}:{$curie->getCategory()}:*" => 'onEvent',
        ];
    }

    /**
     * @param UserCreated $event
     * @param Pbjx        $pbjx
     */
    public function onUserCreated(UserCreated $event, Pbjx $pbjx): void
    {
        $this->handleNodeCreated($event, $pbjx);
    }

    /**
     * @param UserDeleted $event
     * @param Pbjx        $pbjx
     */
    public function onUserDeleted(UserDeleted $event, Pbjx $pbjx): void
    {
        $this->handleNodeDeleted($event, $pbjx);
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

    /**
     * @param UserUpdated $event
     * @param Pbjx        $pbjx
     */
    public function onUserUpdated(UserUpdated $event, Pbjx $pbjx): void
    {
        $this->handleNodeUpdated($event, $pbjx);
    }
}
