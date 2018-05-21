<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractGetNodeHistoryRequestHandler;
use Gdbots\Pbj\Schema;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\App\AppV1Mixin;
use Gdbots\Schemas\Pbjx\Mixin\GetEventsRequest\GetEventsRequest;
use Gdbots\Schemas\Pbjx\StreamId;

class GetAppHistoryRequestHandler extends AbstractGetNodeHistoryRequestHandler
{
    /**
     * {@inheritdoc}
     */
    protected function canReadStream(GetEventsRequest $request, Pbjx $pbjx): bool
    {
        /** @var StreamId $streamId */
        $streamId = $request->get('stream_id');
        $validTopics = [];

        /** @var Schema $schema */
        foreach (AppV1Mixin::findAll() as $schema) {
            $qname = $schema->getQName();
            // e.g. "sms-app.history", "ios-app.history"
            $validTopics[$qname->getMessage() . '.history'] = true;
        }

        return isset($validTopics[$streamId->getTopic()]);
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        /** @var Schema $schema */
        $schema = AppV1Mixin::findAll()[0];
        $curie = $schema->getCurie();
        return [
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:command:get-app-history-request"),
        ];
    }
}
