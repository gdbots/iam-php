<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\NcrProjector;
use Gdbots\Schemas\Iam\Event\UserRolesGrantedV1;
use Gdbots\Schemas\Iam\Event\UserRolesRevokedV1;
use Gdbots\Schemas\Iam\Mixin\UserRolesGranted\UserRolesGrantedV1Mixin;
use Gdbots\Schemas\Iam\Mixin\UserRolesRevoked\UserRolesRevokedV1Mixin;

class NcrUserProjector extends NcrProjector
{
    public static function getSubscribedEvents()
    {
        return [
            UserRolesGrantedV1::SCHEMA_CURIE      => 'onNodeEvent',
            UserRolesRevokedV1::SCHEMA_CURIE      => 'onNodeEvent',

            // deprecated mixins, will be removed in 3.x
            UserRolesGrantedV1Mixin::SCHEMA_CURIE => 'onNodeEvent',
            UserRolesRevokedV1Mixin::SCHEMA_CURIE => 'onNodeEvent',
        ];
    }
}
