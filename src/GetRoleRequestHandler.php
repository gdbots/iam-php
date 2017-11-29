<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Pbjx\RequestHandlerTrait;
use Gdbots\Schemas\Iam\Mixin\GetRoleRequest\GetRoleRequest;
use Gdbots\Schemas\Iam\Mixin\GetRoleResponse\GetRoleResponse;
use Gdbots\Schemas\Iam\Mixin\GetRoleResponse\GetRoleResponseV1Mixin;

final class GetRoleRequestHandler implements RequestHandler
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
     * @param GetRoleRequest $request
     *
     * @return GetRoleResponse
     */
    protected function handle(GetRoleRequest $request): GetRoleResponse
    {
        if (!$request->has('node_ref')) {
            throw new NodeNotFound('No method available to find role.');
        }

        $node = $this->ncr->getNode($request->get('node_ref'), $request->get('consistent_read'));

        $schema = GetRoleResponseV1Mixin::findOne();
        /** @var GetRoleResponse $response */
        $response = $schema->createMessage();
        return $response->set('node', $node);
    }
}
