<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractNodeCommandHandler;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\App\App;
use Gdbots\Schemas\Iam\Mixin\AppRolesGranted\AppRolesGranted;
use Gdbots\Schemas\Iam\Mixin\AppRolesGranted\AppRolesGrantedV1Mixin;
use Gdbots\Schemas\Iam\Mixin\GrantRolesToApp\GrantRolesToApp;
use Gdbots\Schemas\Iam\Mixin\GrantRolesToApp\GrantRolesToAppV1Mixin;
use Gdbots\Schemas\Iam\Mixin\Role\RoleV1Mixin;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\NodeRef;

class GrantRolesToAppHandler extends AbstractNodeCommandHandler
{
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
     * @param GrantRolesToApp $command
     * @param Pbjx             $pbjx
     */
    protected function handle(GrantRolesToApp $command, Pbjx $pbjx): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');
        $node = $this->ncr->getNode($nodeRef, true, $this->createNcrContext($command));
        $this->assertIsNodeSupported($node);

        $event = $this->createAppRolesGranted($command, $pbjx);
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
     * {@inheritdoc}
     */
    protected function isNodeSupported(Node $node): bool
    {
        return $node instanceof App;
    }

    /**
     * @param GrantRolesToApp $command
     * @param Pbjx             $pbjx
     *
     * @return AppRolesGranted
     */
    protected function createAppRolesGranted(GrantRolesToApp $command, Pbjx $pbjx): AppRolesGranted
    {
        /** @var AppRolesGranted $event */
        $event = AppRolesGrantedV1Mixin::findOne()->createMessage();
        return $event;
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            GrantRolesToAppV1Mixin::findOne()->getCurie(),
        ];
    }
}
