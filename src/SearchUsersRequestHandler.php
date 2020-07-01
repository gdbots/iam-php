<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\QueryParser\Enum\BoolOperator;
use Gdbots\QueryParser\Node\Field;
use Gdbots\QueryParser\Node\Word;
use Gdbots\QueryParser\ParsedQuery;
use Gdbots\Schemas\Common\Enum\Trinary;
use Gdbots\Schemas\Iam\Mixin\SearchUsersRequest\SearchUsersRequestV1Mixin;
use Gdbots\Schemas\Iam\Request\SearchUsersRequestV1;
use Gdbots\Schemas\Iam\Request\SearchUsersResponseV1;

class SearchUsersRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin(SearchUsersRequestV1Mixin::SCHEMA_CURIE_MAJOR, false);
        $curies[] = SearchUsersRequestV1::SCHEMA_CURIE;
        return $curies;
    }

    protected function beforeSearchNodes(Message $request, ParsedQuery $parsedQuery): void
    {
        parent::beforeSearchNodes($request, $parsedQuery);

        $required = BoolOperator::REQUIRED();
        foreach ([SearchUsersRequestV1::IS_STAFF_FIELD, SearchUsersRequestV1::IS_BLOCKED_FIELD] as $trinary) {
            if (Trinary::UNKNOWN !== $request->get($trinary)) {
                $parsedQuery->addNode(
                    new Field(
                        $trinary,
                        new Word(Trinary::TRUE_VAL === $request->get($trinary) ? 'true' : 'false', $required),
                        $required
                    )
                );
            }
        }
    }

    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        return SearchUsersResponseV1::create();
    }
}
