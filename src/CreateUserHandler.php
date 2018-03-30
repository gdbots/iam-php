<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractCreateNodeHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\CreateUser\CreateUserV1Mixin;
use Gdbots\Schemas\Iam\Mixin\User\User;
use Gdbots\Schemas\Iam\Mixin\UserCreated\UserCreatedV1Mixin;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\CreateNode\CreateNode;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\Mixin\NodeCreated\NodeCreated;

class CreateUserHandler extends AbstractCreateNodeHandler
{
    /**
     * {@inheritdoc}
     */
    protected function isNodeSupported(Node $node): bool
    {
        return $node instanceof User;
    }

    /**
     * {@inheritdoc}
     */
    protected function createNodeCreated(CreateNode $command, Pbjx $pbjx): NodeCreated
    {
        /** @var NodeCreated $event */
        $event = UserCreatedV1Mixin::findOne()->createMessage();
        return $event;
    }

    /**
     * {@inheritdoc}
     */
    protected function beforePutEvents(NodeCreated $event, CreateNode $command, Pbjx $pbjx): void
    {
        parent::beforePutEvents($event, $command, $pbjx);

        /** @var User $node */
        $node = $event->get('node');
        $node
            ->set('status', NodeStatus::PUBLISHED())
            // roles SHOULD be set with grant-roles-to-user
            ->clear('roles');

        if ($node->has('email')) {
            $email = strtolower($node->get('email'));
            $emailParts = explode('@', $email);
            $node->set('email', $email);
            $node->set('email_domain', array_pop($emailParts));
        }

        if (!$node->has('title')) {
            $node->set('title', trim($node->get('first_name') . ' ' . $node->get('last_name')));
        }
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
