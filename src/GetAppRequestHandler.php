<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Iam\Util\AppPbjxHelperTrait;
use Gdbots\Ncr\AbstractGetNodeRequestHandler;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Schemas\Iam\Mixin\App\AppV1Mixin;

class GetAppRequestHandler extends AbstractGetNodeRequestHandler
{
    use AppPbjxHelperTrait;

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        $curie = AppV1Mixin::findOne()->getCurie();
        return [
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:request:get-app-request"),
        ];
    }
}
