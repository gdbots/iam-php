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
use Gdbots\Schemas\Iam\Request\GetUserResponseV1;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;

class GetUserRequestHandler extends GetNodeRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('gdbots:iam:mixin:get-user-request:v1', false);
        $curies[] = 'gdbots:iam:request:get-user-request';
        return $curies;
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        if (!$request->has('email')) {
            return parent::handleRequest($request, $pbjx);
        }

        $response = $this->createGetNodeResponse($request, $pbjx);
        $consistent = $request->get('consistent_read');
        $context = ['causator' => $request];

        $qname = SchemaQName::fromString($request->get('qname'));
        $query = IndexQueryBuilder::create($qname, 'email', $request->get('email'))
            ->setCount(1)
            ->filterEq('status', NodeStatus::PUBLISHED)
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

        return $response->set('node', $node);
    }

    protected function createGetNodeResponse(Message $request, Pbjx $pbjx): Message
    {
        $legacy = '*:iam:request:get-user-response';
        if (MessageResolver::hasCurie($legacy)) {
            return MessageResolver::resolveCurie($legacy)::create();
        }

        return GetUserResponseV1::create();
    }
}
