<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\DeleteRole\DeleteRole;
use Gdbots\Schemas\Iam\Mixin\RoleDeleted\RoleDeletedV1Mixin;
use Gdbots\Schemas\Pbjx\StreamId;

final class DeleteRoleHandler implements CommandHandler
{
    use CommandHandlerTrait;

    /**
     * @param DeleteRole $command
     * @param Pbjx       $pbjx
     */
    protected function handle(DeleteRole $command, Pbjx $pbjx): void
    {
        $event = RoleDeletedV1Mixin::findOne()->createMessage();
        $event = $event->set('node_ref', $command->get('node_ref'));
        $pbjx->copyContext($command, $event);

        $streamId = StreamId::fromString(sprintf('role.history:%s', $event->get('node_ref')->getId()));
        $pbjx->getEventStore()->putEvents($streamId, [$event]);
    }
}
