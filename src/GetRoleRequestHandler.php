<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractGetNodeRequestHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\GetRoleRequest\GetRoleRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\GetRoleResponse\GetRoleResponseV1Mixin;
use Gdbots\Schemas\Ncr\Mixin\GetNodeRequest\GetNodeRequest;
use Gdbots\Schemas\Ncr\Mixin\GetNodeResponse\GetNodeResponse;

class GetRoleRequestHandler extends AbstractGetNodeRequestHandler
{
    /**
     * {@inheritdoc}
     */
    protected function createGetNodeResponse(GetNodeRequest $request, Pbjx $pbjx): GetNodeResponse
    {
        /** @var GetNodeResponse $response */
        $response = GetRoleResponseV1Mixin::findOne()->createMessage();
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            GetRoleRequestV1Mixin::findOne()->getCurie(),
        ];
    }
}
