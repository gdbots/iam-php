<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\NcrProjector;

class NcrAppProjector extends NcrProjector
{
    public static function getSubscribedEvents()
    {
        return [
            'gdbots:iam:event:app-roles-granted' => 'onNodeEvent',
            'gdbots:iam:event:app-roles-revoked' => 'onNodeEvent',

            // deprecated mixins, will be removed in 3.x
            'gdbots:iam:mixin:app-roles-granted' => 'onNodeEvent',
            'gdbots:iam:mixin:app-roles-revoked' => 'onNodeEvent',
        ];
    }
}
