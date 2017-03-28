<?php
declare(strict_types = 1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Pbjx\RequestHandlerTrait;
use Gdbots\Schemas\Iam\Mixin\GetUserBatchRequest\GetUserBatchRequest;
use Gdbots\Schemas\Iam\Mixin\GetUserBatchResponse\GetUserBatchResponse;
use Gdbots\Schemas\Iam\Mixin\GetUserBatchResponse\GetUserBatchResponseV1Mixin;
use Gdbots\Schemas\Ncr\NodeRef;

class GetUserBatchRequestHandler implements RequestHandler
{
    use RequestHandlerTrait;

    /** @var Ncr */
    private $ncr;

    /**
     * @param Ncr $ncr
     */
    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    /**
     * @param GetUserBatchRequest $request
     * @param Pbjx                  $pbjx
     *
     * @return GetUserBatchResponse
     */
    protected function handle(GetUserBatchRequest $request, Pbjx $pbjx): GetUserBatchResponse
    {
        $schema = MessageResolver::findOneUsingMixin(GetUserBatchResponseV1Mixin::create(), 'iam', 'request');
        /** @var GetUserBatchResponse $response */
        $response = $schema->createMessage();

        $nodeRefs = $request->get('node_refs', []);

        if (empty($nodeRefs)) {
            return $response;
        }

        $users = $this->ncr->getNodes(
            $nodeRefs,
            $request->get('consistent_read', false),
            $request->get('context', [])
        );

        foreach ($users as $nodeRef => $user) {
            $response->addToMap('nodes', $nodeRef, $user);
        }

        $missing = array_keys(array_diff_key(array_flip(array_map('strval', $nodeRefs)), $users));
        $missing = array_map(function ($str) { return NodeRef::fromString($str); }, $missing);
        $response->addToSet('missing_node_refs', $missing);

        return $response;
    }
}
