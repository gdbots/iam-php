<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\GrantRolesToUser\GrantRolesToUser;
use Gdbots\Schemas\Iam\Mixin\UserRolesGranted\UserRolesGrantedV1Mixin;
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
        $event = MessageResolver::findOneUsingMixin(UserRolesGrantedV1Mixin::create(), 'iam', 'event')->createMessage();
        $event = $event->set('node_ref', $command->get('node_ref'));
        $pbjx->copyContext($command, $event);

        $event->addToSet('roles', $command->get('roles', []));

        $streamId = StreamId::fromString(sprintf('user.history:%s', $event->get('node_ref')->getId()));
        $pbjx->getEventStore()->putEvents($streamId, [$event]);
    }
}
