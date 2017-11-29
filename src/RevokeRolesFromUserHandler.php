<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\RevokeRolesFromUser\RevokeRolesFromUser;
use Gdbots\Schemas\Iam\Mixin\UserRolesRevoked\UserRolesRevokedV1Mixin;
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
        $event = UserRolesRevokedV1Mixin::findOne()->createMessage();
        $event = $event->set('node_ref', $command->get('node_ref'));
        $pbjx->copyContext($command, $event);

        $event->addToSet('roles', $command->get('roles', []));

        $streamId = StreamId::fromString(sprintf('user.history:%s', $event->get('node_ref')->getId()));
        $pbjx->getEventStore()->putEvents($streamId, [$event]);
    }
}
