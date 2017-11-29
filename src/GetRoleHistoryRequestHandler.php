<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Pbjx\RequestHandlerTrait;
use Gdbots\Schemas\Iam\Mixin\GetRoleHistoryRequest\GetRoleHistoryRequest;
use Gdbots\Schemas\Iam\Mixin\GetRoleHistoryResponse\GetRoleHistoryResponse;
use Gdbots\Schemas\Iam\Mixin\GetRoleHistoryResponse\GetRoleHistoryResponseV1Mixin;

final class GetRoleHistoryRequestHandler implements RequestHandler
{
    use RequestHandlerTrait;

    /**
     * @param GetRoleHistoryRequest $request
     * @param Pbjx                  $pbjx
     *
     * @return GetRoleHistoryResponse
     */
    protected function handle(GetRoleHistoryRequest $request, Pbjx $pbjx): GetRoleHistoryResponse
    {
        $slice = $pbjx->getEventStore()->getStreamSlice(
            $request->get('stream_id'),
            $request->get('since'),
            $request->get('count'),
            $request->get('forward')
        );

        $schema = GetRoleHistoryResponseV1Mixin::findOne();
        /** @var GetRoleHistoryResponse $response */
        $response = $schema->createMessage();

        return $response
            ->set('has_more', $slice->hasMore())
            ->set('last_occurred_at', $slice->getLastOccurredAt())
            ->addToList('events', $slice->toArray()['events']);
    }
}
