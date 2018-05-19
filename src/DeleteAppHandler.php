<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractDeleteNodeHandler;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Schemas\Iam\Mixin\App\App;
use Gdbots\Schemas\Iam\Mixin\App\AppV1Mixin;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;

class DeleteAppHandler extends AbstractDeleteNodeHandler
{
    /**
     * {@inheritdoc}
     */
    protected function isNodeSupported(Node $node): bool
    {
        return $node instanceof App;
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        $curie = AppV1Mixin::findOne()->getCurie();
        return [
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:command:delete-app"),
        ];
    }
}
