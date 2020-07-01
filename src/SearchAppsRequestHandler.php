<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbjx\Pbjx;
use Gdbots\QueryParser\ParsedQuery;
use Gdbots\Schemas\Iam\Mixin\App\AppV1Mixin;
use Gdbots\Schemas\Iam\Mixin\GetAllAppsRequest\GetAllAppsRequestV1Mixin;
use Gdbots\Schemas\Iam\Request\SearchAppsRequestV1;
use Gdbots\Schemas\Iam\Request\SearchAppsResponseV1;

class SearchAppsRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin(GetAllAppsRequestV1Mixin::SCHEMA_CURIE_MAJOR, false);
        $curies[] = SearchAppsRequestV1::SCHEMA_CURIE;
        return $curies;
    }

    protected function createQNamesForSearchNodes(Message $request, ParsedQuery $parsedQuery): array
    {
        static $qnames = null;
        if (null === $qnames) {
            $curies = MessageResolver::findAllUsingMixin(AppV1Mixin::SCHEMA_CURIE_MAJOR, false);
            $qnames = array_map(fn(string $curie) => SchemaCurie::fromString($curie)->getQName(), $curies);
            if (empty($qnames)) {
                $qnames = [SchemaQName::fromString(MessageResolver::getDefaultVendor() . ':no-app')];
            }
        }

        return $qnames;
    }

    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        return SearchAppsResponseV1::create();
    }
}
