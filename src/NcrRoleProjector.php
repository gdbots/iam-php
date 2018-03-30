<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractNodeProjector;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\RoleCreated\RoleCreated;
use Gdbots\Schemas\Iam\Mixin\RoleCreated\RoleCreatedV1Mixin;
use Gdbots\Schemas\Iam\Mixin\RoleDeleted\RoleDeleted;
use Gdbots\Schemas\Iam\Mixin\RoleUpdated\RoleUpdated;

class NcrRoleProjector extends AbstractNodeProjector implements EventSubscriber
{
    use EventSubscriberTrait;

    /** @var bool */
    protected $useSoftDelete = false;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $curie = RoleCreatedV1Mixin::findOne()->getCurie();
        return [
            "{$curie->getVendor()}:{$curie->getPackage()}:{$curie->getCategory()}:*" => 'onEvent',
        ];
    }

    /**
     * @param RoleCreated $event
     * @param Pbjx        $pbjx
     */
    public function onRoleCreated(RoleCreated $event, Pbjx $pbjx): void
    {
        $this->handleNodeCreated($event, $pbjx);
    }

    /**
     * @param RoleDeleted $event
     * @param Pbjx        $pbjx
     */
    public function onRoleDeleted(RoleDeleted $event, Pbjx $pbjx): void
    {
        $this->handleNodeDeleted($event, $pbjx);
    }

    /**
     * @param RoleUpdated $event
     * @param Pbjx        $pbjx
     */
    public function onRoleUpdated(RoleUpdated $event, Pbjx $pbjx): void
    {
        $this->handleNodeUpdated($event, $pbjx);
    }
}
