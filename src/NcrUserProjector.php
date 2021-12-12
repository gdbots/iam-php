<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\NcrProjector;

class NcrUserProjector extends NcrProjector
{
    public static function getSubscribedEvents(): array
    {
        return [
            'gdbots:iam:event:user-roles-granted' => 'onNodeEvent',
            'gdbots:iam:event:user-roles-revoked' => 'onNodeEvent',

            // deprecated mixins, will be removed in 4.x
            'gdbots:iam:mixin:user-roles-granted' => 'onNodeEvent',
            'gdbots:iam:mixin:user-roles-revoked' => 'onNodeEvent',
        ];
    }
}
