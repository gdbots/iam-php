<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\NcrProjector;
use Gdbots\Schemas\Iam\Event\AppRolesGrantedV1;
use Gdbots\Schemas\Iam\Event\AppRolesRevokedV1;
use Gdbots\Schemas\Iam\Mixin\AppRolesGranted\AppRolesGrantedV1Mixin;
use Gdbots\Schemas\Iam\Mixin\AppRolesRevoked\AppRolesRevokedV1Mixin;

class NcrAppProjector extends NcrProjector
{
    public static function getSubscribedEvents()
    {
        return [
            AppRolesGrantedV1::SCHEMA_CURIE      => 'onNodeEvent',
            AppRolesRevokedV1::SCHEMA_CURIE      => 'onNodeEvent',

            // deprecated mixins, will be removed in 3.x
            AppRolesGrantedV1Mixin::SCHEMA_CURIE => 'onNodeEvent',
            AppRolesRevokedV1Mixin::SCHEMA_CURIE => 'onNodeEvent',
        ];
    }
}
