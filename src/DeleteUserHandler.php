<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractDeleteNodeHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\DeleteUser\DeleteUserV1Mixin;
use Gdbots\Schemas\Iam\Mixin\User\User;
use Gdbots\Schemas\Iam\Mixin\UserDeleted\UserDeletedV1Mixin;
use Gdbots\Schemas\Ncr\Mixin\DeleteNode\DeleteNode;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\Mixin\NodeDeleted\NodeDeleted;

class DeleteUserHandler extends AbstractDeleteNodeHandler
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
    protected function createNodeDeleted(DeleteNode $command, Pbjx $pbjx): NodeDeleted
    {
        /** @var NodeDeleted $event */
        $event = UserDeletedV1Mixin::findOne()->createMessage();
        return $event;
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
