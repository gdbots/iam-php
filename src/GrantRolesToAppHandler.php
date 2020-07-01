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
use Gdbots\Schemas\Iam\Command\GrantRolesToAppV1;
use Gdbots\Schemas\Iam\Mixin\GrantRolesToApp\GrantRolesToAppV1Mixin;

class GrantRolesToAppHandler implements CommandHandler
{
    protected Ncr $ncr;

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin(GrantRolesToAppV1Mixin::SCHEMA_CURIE_MAJOR, false);
        $curies[] = GrantRolesToAppV1::SCHEMA_CURIE;
        return $curies;
    }

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get(GrantRolesToAppV1::NODE_REF_FIELD);
        $context = ['causator' => $command];

        $node = $this->ncr->getNode($nodeRef, true, $context);

        /** @var AppAggregate $aggregate */
        $aggregate = AggregateResolver::resolve($nodeRef->getQName())::fromNode($node, $pbjx);
        $aggregate->sync($context);
        $aggregate->grantRolesToApp($command);
        $aggregate->commit($context);
    }
}