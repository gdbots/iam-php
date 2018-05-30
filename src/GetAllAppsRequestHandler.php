<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractRequestHandler;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Schema;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\App\AppV1Mixin;
use Gdbots\Schemas\Iam\Mixin\GetAllAppsRequest\GetAllAppsRequest;
use Gdbots\Schemas\Iam\Mixin\GEtAllAppsRequest\GetAllAppsRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\GetAllAppsResponse\GetAllAppsResponse;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\NodeRef;

class GetAllAppsRequestHandler extends AbstractRequestHandler
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
     * @param GetAllAppsRequest $request
     * @param Pbjx               $pbjx
     *
     * @return GetAllAppsResponse
     */
    protected function handle(GetAllAppsRequest $request, Pbjx $pbjx): GetAllAppsResponse
    {
        $apps = [];
        /** @var Schema $schema */
        foreach (AppV1Mixin::findAll() as $schema) {
            $this->ncr->pipeNodes($schema->getQName(), function (Node $node) use (&$apps) {
                $apps[] = $node;
            }, $this->createNcrContext($request));
        }

        return $this->createGetAllAppsResponse($request, $pbjx)->addToList('nodes', $apps);
    }

    /**
     * @param GetAllAppsRequest $request
     * @param Pbjx               $pbjx
     *
     * @return GetAllAppsResponse
     */
    protected function createGetAllAppsResponse(GetAllAppsRequest $request, Pbjx $pbjx): GetAllAppsResponse
    {
        /** @var GetAllAppsResponse $response */
        $response = $this->createResponseFromRequest($request, $pbjx);
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            GetAllAppsRequestV1Mixin::findOne()->getCurie(),
        ];
    }
}
