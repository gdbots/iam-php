<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractDeleteNodeHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\DeleteRole\DeleteRoleV1Mixin;
use Gdbots\Schemas\Iam\Mixin\Role\Role;
use Gdbots\Schemas\Iam\Mixin\RoleDeleted\RoleDeletedV1Mixin;
use Gdbots\Schemas\Ncr\Mixin\DeleteNode\DeleteNode;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\Mixin\NodeDeleted\NodeDeleted;

class DeleteRoleHandler extends AbstractDeleteNodeHandler
{
    /**
     * {@inheritdoc}
     */
    protected function isNodeSupported(Node $node): bool
    {
        return $node instanceof Role;
    }

    /**
     * {@inheritdoc}
     */
    protected function createNodeDeleted(DeleteNode $command, Pbjx $pbjx): NodeDeleted
    {
        /** @var NodeDeleted $event */
        $event = RoleDeletedV1Mixin::findOne()->createMessage();
        return $event;
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
