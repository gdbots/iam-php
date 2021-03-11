<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Schemas\Iam\Request\SearchRolesRequestV1;

/**
 * @deprecated will be removed in 3.x
 */
class ListAllRolesRequestHandler implements RequestHandler
{
    public static function handlesCuries(): array
    {
        return MessageResolver::findAllUsingMixin('gdbots:iam:mixin:list-all-roles-request:v1', false);
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        $response = $this->createListAllRolesResponse($request, $pbjx);
        $searchRequest = SearchRolesRequestV1::create();

        try {
            $searchResponse = $pbjx->copyContext($request, $searchRequest)->request($searchRequest);
        } catch (\Throwable $e) {
            return $response;
        }

        $nodes = $searchResponse->get('nodes', []);
        $refs = array_map(fn(Message $node) => $node->generateNodeRef(), $nodes);

        return $response->addToSet('roles', $refs);
    }

    protected function createListAllRolesResponse(Message $request, Pbjx $pbjx): Message
    {
        return MessageResolver::resolveCurie('*:iam:request:list-all-roles-response:v1')::create();
    }
}
