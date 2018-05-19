<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractGetNodeHistoryRequestHandler;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Schemas\Iam\Mixin\App\AppV1Mixin;

class GetAppHistoryRequestHandler extends AbstractGetNodeHistoryRequestHandler
{
    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        $curie = AppV1Mixin::findOne()->getCurie();
        return [
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:request:get-app-history-request"),
        ];
    }
}
