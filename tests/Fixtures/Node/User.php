<?php

namespace Gdbots\Tests\Iam\Fixtures\Node;

use Gdbots\Pbj\AbstractMessage;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\Schema;
use Gdbots\Pbj\Type as T;
use Gdbots\Schemas\Ncr\Mixin\Indexed\IndexedV1;
use Gdbots\Schemas\Ncr\Mixin\Indexed\IndexedV1Mixin;
use Gdbots\Schemas\Ncr\Mixin\Indexed\IndexedV1Trait;
use Gdbots\Schemas\Ncr\Mixin\Node\NodeV1;
use Gdbots\Schemas\Ncr\Mixin\Node\NodeV1Mixin;
use Gdbots\Schemas\Ncr\Mixin\Node\NodeV1Trait;
use Gdbots\Schemas\Iam\Mixin\User\UserV1 as GdbotsIamUserV1;
use Gdbots\Schemas\Iam\Mixin\User\UserV1Mixin;
use Gdbots\Schemas\Iam\Mixin\User\UserV1Trait;

final class User extends AbstractMessage implements
    NodeV1,
    GdbotsIamUserV1,
    IndexedV1

{
    use NodeV1Trait;
    use UserV1Trait;
    use IndexedV1Trait;

    /**
     * @return Schema
     */
    protected static function defineSchema()
    {
        $schema = new Schema('pbj:gdbots:iam:node:user:1-0-0', __CLASS__,
            [],
            [
                NodeV1Mixin::create(),
                UserV1Mixin::create(),
                IndexedV1Mixin::create(),
            ]
        );

        MessageResolver::registerSchema($schema);
        return $schema;
    }
}
