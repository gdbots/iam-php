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
use Gdbots\Schemas\Iam\Request\SearchUsersResponseV1;

class SearchUsersRequestHandler extends AbstractSearchNodesRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('gdbots:iam:mixin:search-users-request:v1', false);
        $curies[] = 'gdbots:iam:request:search-users-request';
        return $curies;
    }

    protected function beforeSearchNodes(Message $request, ParsedQuery $parsedQuery): void
    {
        parent::beforeSearchNodes($request, $parsedQuery);

        $required = BoolOperator::REQUIRED();
        foreach (['is_staff', 'is_blocked'] as $trinary) {
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
        $legacy = '*:iam:request:search-users-response';
        if (MessageResolver::hasCurie($legacy)) {
            return MessageResolver::resolveCurie($legacy)::create();
        }

        return SearchUsersResponseV1::create();
    }
}
