<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Iam\Exception\InvalidArgumentException;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\RevokeRolesFromUser\RevokeRolesFromUser;
use Gdbots\Schemas\Iam\Mixin\RevokeRolesFromUser\RevokeRolesFromUserV1Mixin;
use Gdbots\Schemas\Iam\Mixin\Role\RoleV1Mixin;
use Gdbots\Schemas\Iam\Mixin\User\UserV1Mixin;
use Gdbots\Schemas\Iam\Mixin\UserRolesRevoked\UserRolesRevokedV1Mixin;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\StreamId;

final class RevokeRolesFromUserHandler implements CommandHandler
{
    use CommandHandlerTrait;

    /**
     * @param RevokeRolesFromUser $command
     * @param Pbjx                $pbjx
     */
    protected function handle(RevokeRolesFromUser $command, Pbjx $pbjx): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');

        if ($nodeRef->getQName() !== UserV1Mixin::findOne()->getQName()) {
            throw new InvalidArgumentException("Expected a user, got {$nodeRef}.");
        }

        $event = UserRolesRevokedV1Mixin::findOne()->createMessage();
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
            RevokeRolesFromUserV1Mixin::findOne()->getCurie(),
        ];
    }
}
