<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Pbjx\RequestHandlerTrait;
use Gdbots\Schemas\Iam\Mixin\GetRoleBatchRequest\GetRoleBatchRequest;
use Gdbots\Schemas\Iam\Mixin\GetRoleBatchRequest\GetRoleBatchRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\GetRoleBatchResponse\GetRoleBatchResponse;
use Gdbots\Schemas\Iam\Mixin\GetRoleBatchResponse\GetRoleBatchResponseV1Mixin;
use Gdbots\Schemas\Ncr\NodeRef;

final class GetRoleBatchRequestHandler implements RequestHandler
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
     *
     * @return GetRoleBatchResponse
     */
    protected function handle(GetRoleBatchRequest $request): GetRoleBatchResponse
    {
        $schema = GetRoleBatchResponseV1Mixin::findOne();
        /** @var GetRoleBatchResponse $response */
        $response = $schema->createMessage();
        $nodeRefs = $request->get('node_refs');

        if (empty($nodeRefs)) {
            return $response;
        }

        $nodes = $this->ncr->getNodes($nodeRefs, $request->get('consistent_read'));
        foreach ($nodes as $nodeRef => $node) {
            $response->addToMap('nodes', $nodeRef, $node);
        }

        $missing = array_keys(array_diff_key(array_flip(array_map('strval', $nodeRefs)), $nodes));
        $missing = array_map(function ($str) {
            return NodeRef::fromString($str);
        }, $missing);
        $response->addToSet('missing_node_refs', $missing);

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
