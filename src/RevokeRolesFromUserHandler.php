<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Command\RevokeRolesFromUserV1;
use Gdbots\Schemas\Iam\Mixin\RevokeRolesFromUser\RevokeRolesFromUserV1Mixin;

class RevokeRolesFromUserHandler implements CommandHandler
{
    protected Ncr $ncr;

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin(RevokeRolesFromUserV1Mixin::SCHEMA_CURIE_MAJOR, false);
        $curies[] = RevokeRolesFromUserV1::SCHEMA_CURIE;
        return $curies;
    }

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get(RevokeRolesFromUserV1::NODE_REF_FIELD);
        $context = ['causator' => $command];

        $node = $this->ncr->getNode($nodeRef, true, $context);

        /** @var UserAggregate $aggregate */
        $aggregate = AggregateResolver::resolve($nodeRef->getQName())::fromNode($node, $pbjx);
        $aggregate->sync($context);
        $aggregate->revokeRolesFromUser($command);
        $aggregate->commit($context);
    }
}
