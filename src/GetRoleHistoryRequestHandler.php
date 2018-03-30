<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractGetNodeHistoryRequestHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\GetRoleHistoryRequest\GetRoleHistoryRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\GetRoleHistoryResponse\GetRoleHistoryResponseV1Mixin;
use Gdbots\Schemas\Pbjx\Mixin\GetEventsRequest\GetEventsRequest;
use Gdbots\Schemas\Pbjx\Mixin\GetEventsResponse\GetEventsResponse;

class GetRoleHistoryRequestHandler extends AbstractGetNodeHistoryRequestHandler
{
    /**
     * {@inheritdoc}
     */
    protected function createGetEventsResponse(GetEventsRequest $request, Pbjx $pbjx): GetEventsResponse
    {
        /** @var GetEventsResponse $response */
        $response = GetRoleHistoryResponseV1Mixin::findOne()->createMessage();
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            GetRoleHistoryRequestV1Mixin::findOne()->getCurie(),
        ];
    }
}
