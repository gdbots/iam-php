<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\CreateRole\CreateRole;
use Gdbots\Schemas\Iam\Mixin\Role\Role;
use Gdbots\Schemas\Iam\Mixin\RoleCreated\RoleCreatedV1Mixin;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\StreamId;

final class CreateRoleHandler implements CommandHandler
{
    use CommandHandlerTrait;

    /**
     *
     * @param CreateRole $command
     * @param Pbjx       $pbjx
     */
    protected function handle(CreateRole $command, Pbjx $pbjx): void
    {
        $event = RoleCreatedV1Mixin::findOne()->createMessage();
        $pbjx->copyContext($command, $event);

        /** @var Role $node */
        $node = clone $command->get('node');
        $node
            ->clear('updated_at')
            ->clear('updater_ref')
            ->set('status', NodeStatus::PUBLISHED())
            ->set('created_at', $event->get('occurred_at'))
            ->set('creator_ref', $event->get('ctx_user_ref'))
            ->set('last_event_ref', $event->generateMessageRef())
            ->set('title', (string)$node->get('_id'));

        $node->set('etag', $node->generateEtag(['etag', 'updated_at']));
        $event->set('node', $node);

        $streamId = StreamId::fromString(sprintf('role.history:%s', $node->get('_id')));
        $pbjx->getEventStore()->putEvents($streamId, [$event]);
    }
}
