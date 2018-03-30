<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractGetNodeBatchRequestHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\GetUserBatchRequest\GetUserBatchRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\GetUserBatchResponse\GetUserBatchResponseV1Mixin;
use Gdbots\Schemas\Ncr\Mixin\GetNodeBatchRequest\GetNodeBatchRequest;
use Gdbots\Schemas\Ncr\Mixin\GetNodeBatchResponse\GetNodeBatchResponse;

class GetUserBatchRequestHandler extends AbstractGetNodeBatchRequestHandler
{
    /**
     * {@inheritdoc}
     */
    protected function createGetNodeBatchResponse(GetNodeBatchRequest $request, Pbjx $pbjx): GetNodeBatchResponse
    {
        /** @var GetNodeBatchResponse $response */
        $response = GetUserBatchResponseV1Mixin::findOne()->createMessage();
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            GetUserBatchRequestV1Mixin::findOne()->getCurie(),
        ];
    }
}
