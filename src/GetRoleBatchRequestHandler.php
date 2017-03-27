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
use Gdbots\Schemas\Iam\Mixin\GetRoleRequest\GetRoleRequestV1;
use Gdbots\Schemas\Iam\Mixin\GetRoleRequest\GetRoleRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\GetRoleBatchResponse\GetRoleBatchResponse;
use Gdbots\Schemas\Iam\Mixin\Role\Role;
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
        //$consistentRead = $request->get('consistent_read', false);

        $getRoleRequestHandler = new GetRoleRequestHandler($this->ncr);
        foreach($nodeRefs as $nodeRef) {
            $getRoleSchema = MessageResolver::findOneUsingMixin(GetRoleRequestV1Mixin::create(), 'iam', 'request');
            /** @var GetRoleRequestV1 $getRoleRequest */
            $getRoleRequest = $getRoleSchema->createMessage();
            $getRoleRequest->set('node_ref', $nodeRef);

            $getRoleResponse = $getRoleRequestHandler->handleRequest($getRoleRequest, $pbjx);

            /** @var Role $role */
            $role = $getRoleResponse->get('node');

            $response->addToMap('nodes', NodeRef::fromNode($role)->toString(), $role);
        }

        return $response;
    }
}
