<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\Role\Role;
use Gdbots\Schemas\Iam\Mixin\RoleUpdated\RoleUpdatedV1Mixin;
use Gdbots\Schemas\Iam\Mixin\UpdateRole\UpdateRole;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\StreamId;

final class UpdateRoleHandler implements CommandHandler
{
    use CommandHandlerTrait;

    /**
     * @param UpdateRole $command
     * @param Pbjx       $pbjx
     */
    protected function handle(UpdateRole $command, Pbjx $pbjx): void
    {
        $event = RoleUpdatedV1Mixin::findOne()->createMessage();
        $pbjx->copyContext($command, $event);

        /** @var Role $newNode */
        $newNode = clone $command->get('new_node');
        $newNode
            ->set('updated_at', $event->get('occurred_at'))
            ->set('updater_ref', $event->get('ctx_user_ref'))
            ->set('last_event_ref', $event->generateMessageRef())
            ->set('title', (string)$newNode->get('_id'));

        if ($command->has('old_node')) {
            $oldNode = $command->get('old_node');
            $event
                ->set('old_node', $oldNode)
                ->set('old_etag', $oldNode->get('etag'));

            $newNode
                // status SHOULD NOT change during an update, use the appropriate
                // command to change a status (delete, publish, etc.)
                ->set('status', $oldNode->get('status'))
                // created_at and creator_ref MUST NOT change
                ->set('created_at', $oldNode->get('created_at'))
                ->set('creator_ref', $oldNode->get('creator_ref'));
        }

        // the status is either "published" or "deleted"
        if (!NodeStatus::DELETED()->equals($newNode->get('status'))) {
            $newNode->set('status', NodeStatus::PUBLISHED());
        }

        $newNode->set('etag', $newNode->generateEtag(['etag', 'updated_at']));
        $event
            ->set('node_ref', $command->get('node_ref'))
            ->set('new_node', $newNode)
            ->set('new_etag', $newNode->get('etag'));

        $streamId = StreamId::fromString(sprintf('role.history:%s', $newNode->get('_id')));
        $pbjx->getEventStore()->putEvents($streamId, [$event]);
    }
}
