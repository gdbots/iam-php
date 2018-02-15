<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Pbjx\RequestHandlerTrait;
use Gdbots\Schemas\Iam\Mixin\ListAllRolesRequest\ListAllRolesRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\ListAllRolesResponse\ListAllRolesResponse;
use Gdbots\Schemas\Iam\Mixin\ListAllRolesResponse\ListAllRolesResponseV1Mixin;
use Gdbots\Schemas\Iam\Mixin\Role\RoleV1Mixin;
use Gdbots\Schemas\Ncr\NodeRef;

final class ListAllRolesRequestHandler implements RequestHandler
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
     * @return ListAllRolesResponse
     */
    protected function handle(): ListAllRolesResponse
    {
        $schema = ListAllRolesResponseV1Mixin::findOne();
        /** @var ListAllRolesResponse $response */
        $response = $schema->createMessage();

        $roles = [];
        $this->ncr->pipeNodeRefs(RoleV1Mixin::findOne()->getQName(), function (NodeRef $nodeRef) use (&$roles) {
            $roles[] = $nodeRef;
        });

        return $response->addToSet('roles', $roles);
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
