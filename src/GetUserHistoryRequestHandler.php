<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractGetNodeHistoryRequestHandler;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Schemas\Iam\Mixin\User\UserV1Mixin;

class GetUserHistoryRequestHandler extends AbstractGetNodeHistoryRequestHandler
{
    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        $curie = UserV1Mixin::findOne()->getCurie();
        return [
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:request:get-user-history-request"),
        ];
    }
}
