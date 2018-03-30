<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractNodeCommandHandler;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\RevokeRolesFromUser\RevokeRolesFromUser;
use Gdbots\Schemas\Iam\Mixin\RevokeRolesFromUser\RevokeRolesFromUserV1Mixin;
use Gdbots\Schemas\Iam\Mixin\Role\RoleV1Mixin;
use Gdbots\Schemas\Iam\Mixin\User\User;
use Gdbots\Schemas\Iam\Mixin\UserRolesRevoked\UserRolesRevoked;
use Gdbots\Schemas\Iam\Mixin\UserRolesRevoked\UserRolesRevokedV1Mixin;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\NodeRef;

class RevokeRolesFromUserHandler extends AbstractNodeCommandHandler
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
     * @param RevokeRolesFromUser $command
     * @param Pbjx                $pbjx
     */
    protected function handle(RevokeRolesFromUser $command, Pbjx $pbjx): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');
        $node = $this->ncr->getNode($nodeRef, true, $this->createNcrContext($command));
        $this->assertIsNodeSupported($node);

        $event = $this->createUserRolesRevoked($command, $pbjx);
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
        return $node instanceof User;
    }

    /**
     * @param RevokeRolesFromUser $command
     * @param Pbjx                $pbjx
     *
     * @return UserRolesRevoked
     */
    protected function createUserRolesRevoked(RevokeRolesFromUser $command, Pbjx $pbjx): UserRolesRevoked
    {
        /** @var UserRolesRevoked $event */
        $event = UserRolesRevokedV1Mixin::findOne()->createMessage();
        return $event;
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            RevokeRolesFromUserV1Mixin::findOne()->getCurie(),
        ];
    }
}
