<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\NcrProjector;
use Gdbots\Pbj\Message;
use Gdbots\Schemas\Iam\Event\AppRolesGrantedV1;
use Gdbots\Schemas\Iam\Event\AppRolesRevokedV1;
use Gdbots\Schemas\Iam\Mixin\AppRolesGranted\AppRolesGrantedV1Mixin;
use Gdbots\Schemas\Iam\Mixin\AppRolesRevoked\AppRolesRevokedV1Mixin;

class NcrAppProjector extends NcrProjector
{
    public static function getSubscribedEvents()
    {
        return [
            AppRolesGrantedV1::SCHEMA_CURIE      => 'onEvent',
            AppRolesRevokedV1::SCHEMA_CURIE      => 'onEvent',

            // deprecated mixins, will be removed in 3.x
            AppRolesGrantedV1Mixin::SCHEMA_CURIE => 'onEvent',
            AppRolesRevokedV1Mixin::SCHEMA_CURIE => 'onEvent',
        ];
    }

    protected function createProjectedEventSuffix(Message $node, Message $event): string
    {
        return str_replace('app-', '', $event::schema()->getId()->getMessage());
    }
}
