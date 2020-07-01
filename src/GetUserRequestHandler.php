<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\GetNodeRequestHandler;
use Gdbots\Ncr\IndexQueryBuilder;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\GetUserRequest\GetUserRequestV1Mixin;
use Gdbots\Schemas\Iam\Request\GetUserRequestV1;
use Gdbots\Schemas\Iam\Request\GetUserResponseV1;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\Node\NodeV1Mixin;

class GetUserRequestHandler extends GetNodeRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin(GetUserRequestV1Mixin::SCHEMA_CURIE_MAJOR, false);
        $curies[] = GetUserRequestV1::SCHEMA_CURIE;
        return $curies;
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        if (!$request->has(GetUserRequestV1::EMAIL_FIELD)) {
            return parent::handleRequest($request, $pbjx);
        }

        $response = $this->createGetNodeResponse($request, $pbjx);
        $consistent = $request->get(GetUserRequestV1::CONSISTENT_READ_FIELD);
        $context = ['causator' => $request];

        $qname = SchemaQName::fromString($request->get(GetUserRequestV1::QNAME_FIELD));
        $query = IndexQueryBuilder::create($qname, 'email', $request->get(GetUserRequestV1::EMAIL_FIELD))
            ->setCount(1)
            ->filterEq(NodeV1Mixin::STATUS_FIELD, NodeStatus::PUBLISHED)
            ->build();
        $result = $this->ncr->findNodeRefs($query, $context);
        if (!$result->count()) {
            throw new NodeNotFound('Unable to find user.');
        }

        $node = $this->ncr->getNode($result->getNodeRefs()[0], $consistent, $context);

        if ($consistent) {
            $aggregate = AggregateResolver::resolve($node::schema()->getQName())::fromNode($node, $pbjx);
            $aggregate->sync($context);
            $node = $aggregate->getNode();
        }

        return $response->set(GetUserResponseV1::NODE_FIELD, $node);
    }

    protected function createGetNodeResponse(Message $request, Pbjx $pbjx): Message
    {
        return GetUserResponseV1::create();
    }
}
