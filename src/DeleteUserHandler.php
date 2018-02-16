<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Iam\Exception\InvalidArgumentException;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\DeleteUser\DeleteUser;
use Gdbots\Schemas\Iam\Mixin\DeleteUser\DeleteUserV1Mixin;
use Gdbots\Schemas\Iam\Mixin\User\UserV1Mixin;
use Gdbots\Schemas\Iam\Mixin\UserDeleted\UserDeletedV1Mixin;
use Gdbots\Schemas\Ncr\NodeRef;
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
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');

        if ($nodeRef->getQName() !== UserV1Mixin::findOne()->getQName()) {
            throw new InvalidArgumentException("Expected a user, got {$nodeRef}.");
        }

        $event = UserDeletedV1Mixin::findOne()->createMessage();
        $event = $event->set('node_ref', $nodeRef);
        $pbjx->copyContext($command, $event);

        $streamId = StreamId::fromString(sprintf('user.history:%s', $nodeRef->getId()));
        $pbjx->getEventStore()->putEvents($streamId, [$event]);
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            DeleteUserV1Mixin::findOne()->getCurie(),
        ];
    }
}
