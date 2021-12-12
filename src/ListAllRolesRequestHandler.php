<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Schemas\Iam\Request\SearchRolesRequestV1;

/**
 * @deprecated will be removed in 4.x
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
        $searchRequest = SearchRolesRequestV1::create()->set('count', 255);

        do {
            try {
                $searchResponse = $pbjx->copyContext($request, $searchRequest)->request($searchRequest);
            } catch (\Throwable $e) {
                return $response;
            }

            $nodes = $searchResponse->get('nodes', []);
            $refs = array_map(fn(Message $node) => $node->generateNodeRef(), $nodes);
            $response->addToSet('roles', $refs);
            $searchRequest = (clone $searchRequest)->set('page', $searchRequest->get('page') + 1);
        } while ($searchResponse->get('has_more'));

        return $response;
    }

    protected function createListAllRolesResponse(Message $request, Pbjx $pbjx): Message
    {
        return MessageResolver::resolveCurie('*:iam:request:list-all-roles-response:v1')::create();
    }
}
