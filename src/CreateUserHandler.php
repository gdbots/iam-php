<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\CreateUser\CreateUser;
use Gdbots\Schemas\Iam\Mixin\CreateUser\CreateUserV1Mixin;
use Gdbots\Schemas\Iam\Mixin\User\User;
use Gdbots\Schemas\Iam\Mixin\UserCreated\UserCreatedV1Mixin;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\StreamId;

final class CreateUserHandler implements CommandHandler
{
    use CommandHandlerTrait;

    /**
     * fixme: validate that _id was safely set by server?
     *
     * @param CreateUser $command
     * @param Pbjx       $pbjx
     */
    protected function handle(CreateUser $command, Pbjx $pbjx): void
    {
        $event = UserCreatedV1Mixin::findOne()->createMessage();
        $pbjx->copyContext($command, $event);

        /** @var User $node */
        $node = clone $command->get('node');
        $node
            ->clear('updated_at')
            ->clear('updater_ref')
            ->clear('roles')
            ->set('status', NodeStatus::PUBLISHED())
            ->set('created_at', $event->get('occurred_at'))
            ->set('creator_ref', $event->get('ctx_user_ref'))
            ->set('last_event_ref', $event->generateMessageRef());

        if ($node->has('email')) {
            $email = strtolower($node->get('email'));
            $emailParts = explode('@', $email);
            $node->set('email', $email);
            $node->set('email_domain', array_pop($emailParts));
        }

        if (!$node->has('title')) {
            $node->set('title', trim($node->get('first_name') . ' ' . $node->get('last_name')));
        }

        $event->set('node', $node);
        $streamId = StreamId::fromString(sprintf('user.history:%s', $node->get('_id')));
        $pbjx->getEventStore()->putEvents($streamId, [$event]);
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            CreateUserV1Mixin::findOne()->getCurie(),
        ];
    }
}
