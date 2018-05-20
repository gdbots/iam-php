<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractRequestHandler;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\App\AppV1Mixin;
use Gdbots\Schemas\Iam\Mixin\ListAllAppsRequest\ListAllAppsRequest;
use Gdbots\Schemas\Iam\Mixin\ListAllAppsRequest\ListAllAppsRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\ListAllAppsResponse\ListAllAppsResponse;
use Gdbots\Schemas\Ncr\NodeRef;

class ListAllAppsRequestHandler extends AbstractRequestHandler
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
     * @param ListAllAppsRequest $request
     * @param Pbjx                $pbjx
     *
     * @return ListAllAppsResponse
     */
    protected function handle(ListAllAppsRequest $request, Pbjx $pbjx): ListAllAppsResponse
    {
        $apps = [];
        $allMixins = AppV1Mixin::findAll();
        foreach ($allMixins as $mixin) {
            $this->ncr->pipeNodeRefs($mixin->getQName(), function (NodeRef $nodeRef) use (&$apps) {
                $apps[] = $nodeRef;
            }, $this->createNcrContext($request));
        }

        return $this->createListAllAppsResponse($request, $pbjx)->addToSet('apps', $apps);
    }

    /**
     * @param ListAllAppsRequest $request
     * @param Pbjx                $pbjx
     *
     * @return ListAllAppsResponse
     */
    protected function createListAllAppsResponse(ListAllAppsRequest $request, Pbjx $pbjx): ListAllAppsResponse
    {
        /** @var ListAllAppsResponse $response */
        $response = $this->createResponseFromRequest($request, $pbjx);
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            ListAllAppsRequestV1Mixin::findOne()->getCurie(),
        ];
    }
}
