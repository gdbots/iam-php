<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractGetNodeHistoryRequestHandler;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Schemas\Iam\Mixin\Role\RoleV1Mixin;

class GetRoleHistoryRequestHandler extends AbstractGetNodeHistoryRequestHandler
{
    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        $curie = RoleV1Mixin::findOne()->getCurie();
        return [
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:request:get-role-history-request"),
        ];
    }
}
