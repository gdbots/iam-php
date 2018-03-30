<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractGetNodeBatchRequestHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\GetRoleBatchRequest\GetRoleBatchRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\GetRoleBatchResponse\GetRoleBatchResponseV1Mixin;
use Gdbots\Schemas\Ncr\Mixin\GetNodeBatchRequest\GetNodeBatchRequest;
use Gdbots\Schemas\Ncr\Mixin\GetNodeBatchResponse\GetNodeBatchResponse;

class GetRoleBatchRequestHandler extends AbstractGetNodeBatchRequestHandler
{
    /**
     * {@inheritdoc}
     */
    protected function createGetNodeBatchResponse(GetNodeBatchRequest $request, Pbjx $pbjx): GetNodeBatchResponse
    {
        /** @var GetNodeBatchResponse $response */
        $response = GetRoleBatchResponseV1Mixin::findOne()->createMessage();
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            GetRoleBatchRequestV1Mixin::findOne()->getCurie(),
        ];
    }
}
