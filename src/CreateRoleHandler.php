<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractCreateNodeHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\CreateRole\CreateRoleV1Mixin;
use Gdbots\Schemas\Iam\Mixin\Role\Role;
use Gdbots\Schemas\Iam\Mixin\RoleCreated\RoleCreatedV1Mixin;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\CreateNode\CreateNode;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\Mixin\NodeCreated\NodeCreated;

class CreateRoleHandler extends AbstractCreateNodeHandler
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
    protected function createNodeCreated(CreateNode $command, Pbjx $pbjx): NodeCreated
    {
        /** @var NodeCreated $event */
        $event = RoleCreatedV1Mixin::findOne()->createMessage();
        return $event;
    }

    /**
     * {@inheritdoc}
     */
    protected function beforePutEvents(NodeCreated $event, CreateNode $command, Pbjx $pbjx): void
    {
        parent::beforePutEvents($event, $command, $pbjx);

        /** @var Role $node */
        $node = $event->get('node');
        $node
            ->set('status', NodeStatus::PUBLISHED())
            ->set('title', (string)$node->get('_id'));
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            CreateRoleV1Mixin::findOne()->getCurie(),
        ];
    }
}
