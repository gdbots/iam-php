<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractUpdateNodeHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\Role\Role;
use Gdbots\Schemas\Iam\Mixin\RoleUpdated\RoleUpdatedV1Mixin;
use Gdbots\Schemas\Iam\Mixin\UpdateRole\UpdateRoleV1Mixin;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\Mixin\NodeUpdated\NodeUpdated;
use Gdbots\Schemas\Ncr\Mixin\UpdateNode\UpdateNode;

class UpdateRoleHandler extends AbstractUpdateNodeHandler
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
    protected function createNodeUpdated(UpdateNode $command, Pbjx $pbjx): NodeUpdated
    {
        /** @var NodeUpdated $event */
        $event = RoleUpdatedV1Mixin::findOne()->createMessage();
        return $event;
    }

    /**
     * {@inheritdoc}
     */
    protected function beforePutEvents(NodeUpdated $event, UpdateNode $command, Pbjx $pbjx): void
    {
        parent::beforePutEvents($event, $command, $pbjx);

        /** @var Role $newNode */
        $newNode = $event->get('new_node');
        $newNode
            // a role can only be "published"
            ->set('status', NodeStatus::PUBLISHED())
            ->set('title', (string)$newNode->get('_id'));
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            UpdateRoleV1Mixin::findOne()->getCurie(),
        ];
    }
}
