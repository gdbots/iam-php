<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\DeleteUser\DeleteUser;
use Gdbots\Schemas\Iam\Mixin\UserDeleted\UserDeletedV1Mixin;
use Gdbots\Schemas\Pbjx\StreamId;

final class DeleteUserHandler implements CommandHandler
{
    use CommandHandlerTrait;

    /**
     * @param DeleteUser $command
     * @param Pbjx       $pbjx
     */
    protected function handle(DeleteUser $command, Pbjx $pbjx): void
    {
        $event = MessageResolver::findOneUsingMixin(UserDeletedV1Mixin::create(), 'iam', 'event')->createMessage();
        $event = $event->set('node_ref', $command->get('node_ref'));
        $pbjx->copyContext($command, $event);

        $streamId = StreamId::fromString(sprintf('user.history:%s', $event->get('node_ref')->getId()));
        $pbjx->getEventStore()->putEvents($streamId, [$event]);
    }
}
