<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractRequestHandler;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\ListAllRolesRequest\ListAllRolesRequest;
use Gdbots\Schemas\Iam\Mixin\ListAllRolesRequest\ListAllRolesRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\ListAllRolesResponse\ListAllRolesResponse;
use Gdbots\Schemas\Iam\Mixin\Role\RoleV1Mixin;
use Gdbots\Schemas\Ncr\NodeRef;

class ListAllRolesRequestHandler extends AbstractRequestHandler
{
    /** @var Ncr */
    protected $ncr;

    /**
     * @param Ncr $ncr
     */
    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    /**
     * @param ListAllRolesRequest $request
     * @param Pbjx                $pbjx
     *
     * @return ListAllRolesResponse
     */
    protected function handle(ListAllRolesRequest $request, Pbjx $pbjx): ListAllRolesResponse
    {
        $roles = [];
        $this->ncr->pipeNodeRefs(RoleV1Mixin::findOne()->getQName(), function (NodeRef $nodeRef) use (&$roles) {
            $roles[] = $nodeRef;
        }, $this->createNcrContext($request));

        return $this->createListAllRolesResponse($request, $pbjx)->addToSet('roles', $roles);
    }

    /**
     * @param ListAllRolesRequest $request
     * @param Pbjx                $pbjx
     *
     * @return ListAllRolesResponse
     */
    protected function createListAllRolesResponse(ListAllRolesRequest $request, Pbjx $pbjx): ListAllRolesResponse
    {
        /** @var ListAllRolesResponse $response */
        $response = $this->createResponseFromRequest($request, $pbjx);
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            ListAllRolesRequestV1Mixin::findOne()->getCurie(),
        ];
    }
}
