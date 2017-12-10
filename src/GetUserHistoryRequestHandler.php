<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Pbjx\RequestHandlerTrait;
use Gdbots\Schemas\Iam\Mixin\GetUserHistoryRequest\GetUserHistoryRequest;
use Gdbots\Schemas\Iam\Mixin\GetUserHistoryRequest\GetUserHistoryRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\GetUserHistoryResponse\GetUserHistoryResponse;
use Gdbots\Schemas\Iam\Mixin\GetUserHistoryResponse\GetUserHistoryResponseV1Mixin;

final class GetUserHistoryRequestHandler implements RequestHandler
{
    use RequestHandlerTrait;

    /**
     * @param GetUserHistoryRequest $request
     * @param Pbjx                  $pbjx
     *
     * @return GetUserHistoryResponse
     */
    protected function handle(GetUserHistoryRequest $request, Pbjx $pbjx): GetUserHistoryResponse
    {
        $slice = $pbjx->getEventStore()->getStreamSlice(
            $request->get('stream_id'),
            $request->get('since'),
            $request->get('count'),
            $request->get('forward')
        );

        $schema = GetUserHistoryResponseV1Mixin::findOne();
        /** @var GetUserHistoryResponse $response */
        $response = $schema->createMessage();

        return $response
            ->set('has_more', $slice->hasMore())
            ->set('last_occurred_at', $slice->getLastOccurredAt())
            ->addToList('events', $slice->toArray()['events']);
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            GetUserHistoryRequestV1Mixin::findOne()->getCurie(),
        ];
    }
}
