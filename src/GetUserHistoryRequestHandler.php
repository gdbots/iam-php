<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractGetNodeHistoryRequestHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\GetUserHistoryRequest\GetUserHistoryRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\GetUserHistoryResponse\GetUserHistoryResponseV1Mixin;
use Gdbots\Schemas\Pbjx\Mixin\GetEventsRequest\GetEventsRequest;
use Gdbots\Schemas\Pbjx\Mixin\GetEventsResponse\GetEventsResponse;

class GetUserHistoryRequestHandler extends AbstractGetNodeHistoryRequestHandler
{
    /**
     * {@inheritdoc}
     */
    protected function createGetEventsResponse(GetEventsRequest $request, Pbjx $pbjx): GetEventsResponse
    {
        /** @var GetEventsResponse $response */
        $response = GetUserHistoryResponseV1Mixin::findOne()->createMessage();
        return $response;
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
