<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Pbjx\RequestHandlerTrait;
use Gdbots\Schemas\Iam\Mixin\GetUserHistoryRequest\GetUserHistoryRequest;
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

        $schema = MessageResolver::findOneUsingMixin(GetUserHistoryResponseV1Mixin::create(), 'iam', 'request');
        /** @var GetUserHistoryResponse $response */
        $response = $schema->createMessage();

        return $response
            ->set('has_more', $slice->hasMore())
            ->set('last_occurred_at', $slice->getLastOccurredAt())
            ->addToList('events', $slice->toArray()['events']);
    }
}
