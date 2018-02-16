<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Iam\Exception\InvalidArgumentException;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\GrantRolesToUser\GrantRolesToUser;
use Gdbots\Schemas\Iam\Mixin\GrantRolesToUser\GrantRolesToUserV1Mixin;
use Gdbots\Schemas\Iam\Mixin\Role\RoleV1Mixin;
use Gdbots\Schemas\Iam\Mixin\User\UserV1Mixin;
use Gdbots\Schemas\Iam\Mixin\UserRolesGranted\UserRolesGrantedV1Mixin;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\StreamId;

final class GrantRolesToUserHandler implements CommandHandler
{
    use CommandHandlerTrait;

    /**
     * @param GrantRolesToUser $command
     * @param Pbjx             $pbjx
     */
    protected function handle(GrantRolesToUser $command, Pbjx $pbjx): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');

        if ($nodeRef->getQName() !== UserV1Mixin::findOne()->getQName()) {
            throw new InvalidArgumentException("Expected a user, got {$nodeRef}.");
        }

        $event = UserRolesGrantedV1Mixin::findOne()->createMessage();
        $event = $event->set('node_ref', $nodeRef);
        $pbjx->copyContext($command, $event);

        $roles = [];
        $roleQname = RoleV1Mixin::findOne()->getQName();
        /** @var NodeRef $ref */
        foreach ($command->get('roles', []) as $ref) {
            if ($ref->getQName() === $roleQname) {
                $roles[] = $ref;
            }
        }

        $event->addToSet('roles', $roles);

        $streamId = StreamId::fromString(sprintf('user.history:%s', $nodeRef->getId()));
        $pbjx->getEventStore()->putEvents($streamId, [$event]);
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            GrantRolesToUserV1Mixin::findOne()->getCurie(),
        ];
    }
}
