<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractCreateNodeHandler;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\App\App;
use Gdbots\Schemas\Iam\Mixin\App\AppV1Mixin;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\CreateNode\CreateNode;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\Mixin\NodeCreated\NodeCreated;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Mixin\Command\Command;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;

class CreateAppHandler extends AbstractCreateNodeHandler
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
    protected function beforePutEvents(NodeCreated $event, CreateNode $command, Pbjx $pbjx): void
    {
        parent::beforePutEvents($event, $command, $pbjx);

        /** @var App $node */
        $node = $event->get('node');
        $node->set('status', NodeStatus::PUBLISHED());
    }

    /**
     * {@inheritdoc}
     */
    protected function createNodeCreated(CreateNode $command, Pbjx $pbjx): NodeCreated
    {
        $curie = $command::schema()->getCurie();
        $eventCurie = "{$curie->getVendor()}:{$curie->getPackage()}:event:app-created";

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
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:command:create-app"),
        ];
    }
}
