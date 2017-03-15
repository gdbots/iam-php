<?php

namespace Gdbots\Tests\Iam\Fixtures\Event;

use Gdbots\Pbj\AbstractMessage;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\Schema;
use Gdbots\Pbj\Type as T;
use Gdbots\Schemas\Ncr\Mixin\NodeCreated\NodeCreatedV1;
use Gdbots\Schemas\Ncr\Mixin\NodeCreated\NodeCreatedV1Mixin;
use Gdbots\Schemas\Ncr\Mixin\NodeCreated\NodeCreatedV1Trait;
use Gdbots\Schemas\Pbjx\Mixin\Event\EventV1;
use Gdbots\Schemas\Pbjx\Mixin\Event\EventV1Mixin;
use Gdbots\Schemas\Pbjx\Mixin\Event\EventV1Trait;
use Gdbots\Schemas\Iam\Mixin\UserCreated\UserCreatedV1 as GdbotsIamUserCreatedV1;
use Gdbots\Schemas\Iam\Mixin\UserCreated\UserCreatedV1Mixin;
use Gdbots\Schemas\Iam\Mixin\UserCreated\UserCreatedV1Trait;

final class UserCreated extends AbstractMessage implements
    EventV1,
    NodeCreatedV1,
    GdbotsIamUserCreatedV1

{
    use EventV1Trait;
    use NodeCreatedV1Trait;
    use UserCreatedV1Trait;

    /**
     * @return Schema
     */
    protected static function defineSchema()
    {
        $schema = new Schema('pbj:gdbots:iam:event:user-created:1-0-0', __CLASS__,
            [],
            [
                EventV1Mixin::create(),
                NodeCreatedV1Mixin::create(),
                UserCreatedV1Mixin::create(),
            ]
        );

        MessageResolver::registerSchema($schema);
        return $schema;
    }
}
