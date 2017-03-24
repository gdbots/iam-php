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
use Gdbots\Schemas\Iam\Mixin\GetUserRequest\GetUserRequestV1;
use Gdbots\Schemas\Iam\Mixin\GetUserRequest\GetUserRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\User\User;
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
        //$consistentRead = $request->get('consistent_read', false);

        $getUserRequestHandler = new GetUserRequestHandler($this->ncr);
        foreach($nodeRefs as $nodeRef) {
            // ??????
            $getUserSchema = MessageResolver::findOneUsingMixin(GetUserRequestV1Mixin::create(), 'iam', 'request');
            $getUserRequest = $getUserSchema->createMessage();
            $getUserRequest->set('node_ref', $nodeRef);

            $getUserResponse = $getUserRequestHandler->handleRequest($getUserRequest, $pbjx);

            /** @var User $user */
            $user = $getUserResponse->get('node');

            $response->addToMap('nodes', NodeRef::fromNode($user)->toString(), $user);
        }

        return $response;
    }
}
