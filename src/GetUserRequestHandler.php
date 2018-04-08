<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractGetNodeRequestHandler;
use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\IndexQueryBuilder;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\GetUserRequest\GetUserRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\User\User;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\GetNodeRequest\GetNodeRequest;
use Gdbots\Schemas\Ncr\Mixin\GetNodeResponse\GetNodeResponse;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;

class GetUserRequestHandler extends AbstractGetNodeRequestHandler
{
    /**
     * {@inheritdoc}
     */
    protected function handle(GetNodeRequest $request, Pbjx $pbjx): GetNodeResponse
    {
        if (!$request->has('email')) {
            return parent::handle($request, $pbjx);
        }

        $context = $this->createNcrContext($request);
        $qname = SchemaQName::fromString($request->get('qname'));
        $query = IndexQueryBuilder::create($qname, 'email', $request->get('email'))
            ->setCount(1)
            ->filterEq('status', NodeStatus::PUBLISHED)
            ->build();
        $result = $this->ncr->findNodeRefs($query, $context);
        if (!$result->count()) {
            throw new NodeNotFound('Unable to find user.');
        }

        $node = $this->ncr->getNode($result->getNodeRefs()[0], $request->get('consistent_read'), $context);
        $this->assertIsNodeSupported($node);
        return $this->createGetNodeResponse($request, $pbjx)->set('node', $node);
    }

    /**
     * {@inheritdoc}
     */
    protected function isNodeSupported(Node $node): bool
    {
        return $node instanceof User;
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            GetUserRequestV1Mixin::findOne()->getCurie(),
        ];
    }
}
