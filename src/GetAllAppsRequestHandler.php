<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Schemas\Iam\Request\SearchAppsRequestV1;

/**
 * @deprecated will be removed in 4.x
 */
class GetAllAppsRequestHandler implements RequestHandler
{
    public static function handlesCuries(): array
    {
        return MessageResolver::findAllUsingMixin('gdbots:iam:mixin:get-all-apps-request:v1', false);
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        $response = $this->createGetAllAppsResponse($request, $pbjx);
        $searchRequest = SearchAppsRequestV1::create();

        try {
            $searchResponse = $pbjx->copyContext($request, $searchRequest)->request($searchRequest);
        } catch (\Throwable $e) {
            return $response;
        }

        return $response->addToList('nodes', $searchResponse->get('nodes', []));
    }

    protected function createGetAllAppsResponse(Message $request, Pbjx $pbjx): Message
    {
        return MessageResolver::resolveCurie('*:iam:request:get-all-apps-response:v1')::create();
    }
}
