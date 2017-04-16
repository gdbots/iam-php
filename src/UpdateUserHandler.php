<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\UpdateUser\UpdateUser;
use Gdbots\Schemas\Iam\Mixin\User\User;
use Gdbots\Schemas\Iam\Mixin\UserUpdated\UserUpdatedV1Mixin;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\StreamId;

final class UpdateUserHandler implements CommandHandler
{
    use CommandHandlerTrait;

    /**
     * @param UpdateUser $command
     * @param Pbjx       $pbjx
     */
    protected function handle(UpdateUser $command, Pbjx $pbjx): void
    {
        $event = MessageResolver::findOneUsingMixin(UserUpdatedV1Mixin::create(), 'iam', 'event')->createMessage();
        $pbjx->copyContext($command, $event);

        /** @var User $newNode */
        $newNode = clone $command->get('new_node');
        $newNode
            ->set('updated_at', $event->get('occurred_at'))
            ->set('updater_ref', $event->get('ctx_user_ref'))
            ->set('last_event_ref', $event->generateMessageRef());

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
                ->set('creator_ref', $oldNode->get('creator_ref'))
                // email SHOULD NOT change during an update, use "change-email"
                ->set('email', $oldNode->get('email'))
                ->set('email_domain', $oldNode->get('email_domain'))
                // roles cannot change during an update
                ->clear('roles')
                ->addToSet('roles', $oldNode->get('roles', []));
        }

        if ($newNode->has('email')) {
            $email = strtolower($newNode->get('email'));
            $emailParts = explode('@', $email);
            $newNode->set('email', $email);
            $newNode->set('email_domain', array_pop($emailParts));
        }

        if (!$newNode->has('title')) {
            $newNode->set('title', trim($newNode->get('first_name') . ' ' . $newNode->get('last_name')));
        }

        // we really only have "active" and "deleted" users so we
        // force the status to "published" if not "deleted".
        if (!NodeStatus::DELETED()->equals($newNode->get('status'))) {
            $newNode->set('status', NodeStatus::PUBLISHED());
        }

        $newNode->set('etag', $newNode->generateEtag(['etag', 'updated_at']));
        $event
            ->set('node_ref', $command->get('node_ref'))
            ->set('new_node', $newNode)
            ->set('new_etag', $newNode->get('etag'));

        $streamId = StreamId::fromString(sprintf('user.history:%s', $newNode->get('_id')));
        $pbjx->getEventStore()->putEvents($streamId, [$event]);
    }
}
