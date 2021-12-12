<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\NcrProjector;

class NcrAppProjector extends NcrProjector
{
    public static function getSubscribedEvents(): array
    {
        return [
            'gdbots:iam:event:app-roles-granted' => 'onNodeEvent',
            'gdbots:iam:event:app-roles-revoked' => 'onNodeEvent',

            // deprecated mixins, will be removed in 4.x
            'gdbots:iam:mixin:app-roles-granted' => 'onNodeEvent',
            'gdbots:iam:mixin:app-roles-revoked' => 'onNodeEvent',
        ];
    }
}
