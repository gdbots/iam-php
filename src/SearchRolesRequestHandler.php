<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Request\SearchRolesRequestV1;
use Gdbots\Schemas\Iam\Request\SearchRolesResponseV1;

class SearchRolesRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        return [
            SearchRolesRequestV1::SCHEMA_CURIE,
        ];
    }

    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        return SearchRolesResponseV1::create();
    }
}
