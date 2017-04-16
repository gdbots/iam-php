<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\IndexQueryBuilder;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Pbjx\RequestHandlerTrait;
use Gdbots\Schemas\Iam\Mixin\GetUserRequest\GetUserRequest;
use Gdbots\Schemas\Iam\Mixin\GetUserResponse\GetUserResponse;
use Gdbots\Schemas\Iam\Mixin\GetUserResponse\GetUserResponseV1Mixin;

final class GetUserRequestHandler implements RequestHandler
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
     * @param GetUserRequest $request
     *
     * @return GetUserResponse
     */
    protected function handle(GetUserRequest $request): GetUserResponse
    {
        if ($request->has('node_ref')) {
            $node = $this->ncr->getNode($request->get('node_ref'), $request->get('consistent_read'));
        } elseif ($request->has('email')) {
            $qname = SchemaQName::fromString($request->get('qname'));
            $query = IndexQueryBuilder::create($qname, 'email', $request->get('email'))
                ->setCount(1)
                ->build();
            $result = $this->ncr->findNodeRefs($query);
            if (!$result->count()) {
                throw new NodeNotFound('Unable to find user.');
            }

            $node = $this->ncr->getNode($result->getNodeRefs()[0], $request->get('consistent_read'));
        } else {
            throw new NodeNotFound('No method available to find user.');
        }

        $schema = MessageResolver::findOneUsingMixin(GetUserResponseV1Mixin::create(), 'iam', 'request');
        /** @var GetUserResponse $response */
        $response = $schema->createMessage();
        return $response->set('node', $node);
    }
}
