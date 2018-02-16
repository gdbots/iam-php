<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Iam\Exception\InvalidArgumentException;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\DeleteRole\DeleteRole;
use Gdbots\Schemas\Iam\Mixin\DeleteRole\DeleteRoleV1Mixin;
use Gdbots\Schemas\Iam\Mixin\Role\RoleV1Mixin;
use Gdbots\Schemas\Iam\Mixin\RoleDeleted\RoleDeletedV1Mixin;
use Gdbots\Schemas\Ncr\NodeRef;
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
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');

        if ($nodeRef->getQName() !== RoleV1Mixin::findOne()->getQName()) {
            throw new InvalidArgumentException("Expected a role, got {$nodeRef}.");
        }

        $event = RoleDeletedV1Mixin::findOne()->createMessage();
        $event = $event->set('node_ref', $nodeRef);
        $pbjx->copyContext($command, $event);

        $streamId = StreamId::fromString(sprintf('role.history:%s', $nodeRef->getId()));
        $pbjx->getEventStore()->putEvents($streamId, [$event]);
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            DeleteRoleV1Mixin::findOne()->getCurie(),
        ];
    }
}
