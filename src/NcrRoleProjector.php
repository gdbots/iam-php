<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractNodeProjector;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\Role\RoleV1Mixin;
use Gdbots\Schemas\Ncr\Mixin\NodeCreated\NodeCreated;
use Gdbots\Schemas\Ncr\Mixin\NodeDeleted\NodeDeleted;
use Gdbots\Schemas\Ncr\Mixin\NodeUpdated\NodeUpdated;

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
        $curie = RoleV1Mixin::findOne()->getCurie();
        return [
            "{$curie->getVendor()}:{$curie->getPackage()}:event:*" => 'onEvent',
        ];
    }

    /**
     * @param NodeCreated $event
     * @param Pbjx        $pbjx
     */
    public function onRoleCreated(NodeCreated $event, Pbjx $pbjx): void
    {
        $this->handleNodeCreated($event, $pbjx);
    }

    /**
     * @param NodeDeleted $event
     * @param Pbjx        $pbjx
     */
    public function onRoleDeleted(NodeDeleted $event, Pbjx $pbjx): void
    {
        $this->handleNodeDeleted($event, $pbjx);
    }

    /**
     * @param NodeUpdated $event
     * @param Pbjx        $pbjx
     */
    public function onRoleUpdated(NodeUpdated $event, Pbjx $pbjx): void
    {
        $this->handleNodeUpdated($event, $pbjx);
    }
}
