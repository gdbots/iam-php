<?php
declare(strict_types = 1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Pbjx\RequestHandlerTrait;
use Gdbots\Schemas\Iam\Mixin\GetRoleBatchRequest\GetRoleBatchRequest;
use Gdbots\Schemas\Iam\Mixin\GetRoleBatchResponse\GetRoleBatchResponseV1Mixin;
use Gdbots\Schemas\Iam\Mixin\GetRoleBatchResponse\GetRoleBatchResponse;
use Gdbots\Schemas\Ncr\NodeRef;

class GetRoleBatchRequestHandler implements RequestHandler
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
     * @param GetRoleBatchRequest $request
     * @param Pbjx                  $pbjx
     *
     * @return GetRoleBatchResponse
     */
    protected function handle(GetRoleBatchRequest $request, Pbjx $pbjx): GetRoleBatchResponse
    {
        $schema = MessageResolver::findOneUsingMixin(GetRoleBatchResponseV1Mixin::create(), 'iam', 'request');
        /** @var GetRoleBatchResponse $response */
        $response = $schema->createMessage();

        $nodeRefs = $request->get('node_refs', []);

        if (empty($nodeRefs)) {
            return $response;
        }

        $roles = $this->ncr->getNodes(
            $nodeRefs,
            $request->get('consistent_read', false),
            $request->get('context', [])
        );

        foreach ($roles as $nodeRef => $role) {
            $response->addToMap('nodes', $nodeRef, $role);
        }

        $missing = array_keys(array_diff_key(array_flip(array_map('strval', $nodeRefs)), $roles));
        $missing = array_map(function ($str) { return NodeRef::fromString($str); }, $missing);
        $response->addToSet('missing_node_refs', $missing);

        return $response;
    }
}
