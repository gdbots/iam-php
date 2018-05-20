<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractDeleteNodeHandler;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\App\App;
use Gdbots\Schemas\Iam\Mixin\App\AppV1Mixin;
use Gdbots\Schemas\Ncr\Mixin\DeleteNode\DeleteNode;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\Mixin\NodeDeleted\NodeDeleted;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Mixin\Command\Command;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;

class DeleteAppHandler extends AbstractDeleteNodeHandler
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
    protected function createNodeDeleted(DeleteNode $command, Pbjx $pbjx): NodeDeleted
    {
        $curie = $command::schema()->getCurie();
        $eventCurie = "{$curie->getVendor()}:{$curie->getPackage()}:event:app-deleted";

        /** @var Event $class */
        $class = MessageResolver::resolveCurie(SchemaCurie::fromString($eventCurie));
        return $class::create();
    }

    /**
     * {@inheritdoc}
     */
    protected function createStreamId(NodeRef $nodeRef, Command $command, Event $event): StreamId
    {
        return StreamId::fromString(sprintf('app.history:%s', $nodeRef->getId()));
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        $curie = AppV1Mixin::findOne()->getCurie();
        return [
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:command:delete-app"),
        ];
    }
}
