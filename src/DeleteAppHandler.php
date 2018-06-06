<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Iam\Util\AppPbjxHelperTrait;
use Gdbots\Ncr\AbstractDeleteNodeHandler;
use Gdbots\Pbj\Schema;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Schemas\Iam\Mixin\App\AppV1Mixin;

class DeleteAppHandler extends AbstractDeleteNodeHandler
{
    use AppPbjxHelperTrait;

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        /** @var Schema $schema */
        $schema = AppV1Mixin::findAll()[0];
        $curie = $schema->getCurie();
        return [
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:command:delete-app"),
        ];
    }
}
