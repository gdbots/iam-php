<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\NcrProjector;

class NcrUserProjector extends NcrProjector
{
    public static function getSubscribedEvents()
    {
        return [
            'gdbots:iam:event:user-roles-granted' => 'onNodeEvent',
            'gdbots:iam:event:user-roles-revoked' => 'onNodeEvent',

            // deprecated mixins, will be removed in 3.x
            'gdbots:iam:mixin:user-roles-granted' => 'onNodeEvent',
            'gdbots:iam:mixin:user-roles-revoked' => 'onNodeEvent',
        ];
    }
}
