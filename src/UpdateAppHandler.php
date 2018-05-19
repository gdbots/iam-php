<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractUpdateNodeHandler;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\App\App;
use Gdbots\Schemas\Iam\Mixin\App\AppV1Mixin;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\Mixin\NodeUpdated\NodeUpdated;
use Gdbots\Schemas\Ncr\Mixin\UpdateNode\UpdateNode;

class UpdateAppHandler extends AbstractUpdateNodeHandler
{
    /**
     * {@inheritdoc}
     */
    protected function isNodeSupported(Node $node): bool
    {
        return $node instanceof App;
    }

    /**
     * {@inheritdoc}
     */
    protected function beforePutEvents(NodeUpdated $event, UpdateNode $command, Pbjx $pbjx): void
    {
        parent::beforePutEvents($event, $command, $pbjx);

        /** @var App $newNode */
        $newNode = $event->get('new_node');
        // a app can only be "published"
        $newNode->set('status', NodeStatus::PUBLISHED());
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        $curie = AppV1Mixin::findOne()->getCurie();
        return [
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:command:update-app"),
        ];
    }
}
