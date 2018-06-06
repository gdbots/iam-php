<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Iam\Util\AppPbjxHelperTrait;
use Gdbots\Ncr\AbstractNodeCommandHandler;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\AppRolesRevoked\AppRolesRevoked;
use Gdbots\Schemas\Iam\Mixin\AppRolesRevoked\AppRolesRevokedV1Mixin;
use Gdbots\Schemas\Iam\Mixin\RevokeRolesFromApp\RevokeRolesFromApp;
use Gdbots\Schemas\Iam\Mixin\RevokeRolesFromApp\RevokeRolesFromAppV1Mixin;
use Gdbots\Schemas\Iam\Mixin\Role\RoleV1Mixin;
use Gdbots\Schemas\Ncr\NodeRef;

class RevokeRolesFromAppHandler extends AbstractNodeCommandHandler
{
    use AppPbjxHelperTrait;

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
     * @param RevokeRolesFromApp $command
     * @param Pbjx               $pbjx
     */
    protected function handle(RevokeRolesFromApp $command, Pbjx $pbjx): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');
        $node = $this->ncr->getNode($nodeRef, true, $this->createNcrContext($command));
        $this->assertIsNodeSupported($node);

        $event = $this->createAppRolesRevoked($command, $pbjx);
        $pbjx->copyContext($command, $event);
        $event->set('node_ref', $nodeRef);

        $roles = [];
        $roleQname = RoleV1Mixin::findOne()->getQName();
        /** @var NodeRef $ref */
        foreach ($command->get('roles', []) as $ref) {
            if ($ref->getQName() === $roleQname) {
                $roles[] = $ref;
            }
        }

        $event->addToSet('roles', $roles);

        $this->bindFromNode($event, $node, $pbjx);
        $this->putEvents($command, $pbjx, $this->createStreamId($nodeRef, $command, $event), [$event]);
    }

    /**
     * @param RevokeRolesFromApp $command
     * @param Pbjx               $pbjx
     *
     * @return AppRolesRevoked
     */
    protected function createAppRolesRevoked(RevokeRolesFromApp $command, Pbjx $pbjx): AppRolesRevoked
    {
        /** @var AppRolesRevoked $event */
        $event = AppRolesRevokedV1Mixin::findOne()->createMessage();
        return $event;
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            RevokeRolesFromAppV1Mixin::findOne()->getCurie(),
        ];
    }
}
