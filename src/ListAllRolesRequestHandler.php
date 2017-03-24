<?php
declare(strict_types = 1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Pbjx\RequestHandlerTrait;
use Gdbots\Schemas\Iam\Mixin\ListAllRolesRequest\ListAllRolesRequest;
use Gdbots\Schemas\Iam\Mixin\ListAllRolesResponse\ListAllRolesResponse;
use Gdbots\Schemas\Iam\Mixin\ListAllRolesResponse\ListAllRolesResponseV1Mixin;
use Gdbots\Schemas\Ncr\NodeRef;

class ListAllRolesRequestHandler implements RequestHandler
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
     * @param ListAllRolesRequest $request
     * @param Pbjx $pbjx

     * @return ListAllRolesResponse
     */
    protected function handle(ListAllRolesRequest $request, Pbjx $pbjx): ListAllRolesResponse
    {
        $schema = MessageResolver::findOneUsingMixin(ListAllRolesResponseV1Mixin::create(), 'iam', 'request');
        /** @var ListAllRolesResponse $response */
        $response = $schema->createMessage();

        $q = SchemaQName::fromString("{$response::schema()->getId()->getVendor()}:role");

        $roles = [];
        $this->ncr->pipeNodeRefs($q, function(NodeRef $nodeRef) use (&$roles) {
            $roles[] = $nodeRef;
        });

        return $response->addToSet('roles', $roles);
    }
}
