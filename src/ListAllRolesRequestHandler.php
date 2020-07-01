<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Schemas\Iam\Mixin\ListAllRolesRequest\ListAllRolesRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\ListAllRolesResponse\ListAllRolesResponseV1Mixin;
use Gdbots\Schemas\Iam\Request\SearchRolesRequestV1;
use Gdbots\Schemas\Iam\Request\SearchRolesResponseV1;

/**
 * @deprecated will be removed in 3.x
 */
class ListAllRolesRequestHandler implements RequestHandler
{
    public static function handlesCuries(): array
    {
        return [
            MessageResolver::findOneUsingMixin(ListAllRolesRequestV1Mixin::SCHEMA_CURIE_MAJOR, false),
        ];
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

        /** @var Message $role */
        $roles = $searchResponse->get(SearchRolesResponseV1::NODES_FIELD, []);
        $refs = array_map(fn(Message $role) => $role->generateNodeRef(), $roles);

        $response->addToSet(ListAllRolesResponseV1Mixin::ROLES_FIELD, $refs);
    }

    protected function createListAllRolesResponse(Message $request, Pbjx $pbjx): Message
    {
        $curie = MessageResolver::findOneUsingMixin(ListAllRolesResponseV1Mixin::SCHEMA_CURIE_MAJOR);
        return MessageResolver::resolveCurie($curie)::create();
    }
}
