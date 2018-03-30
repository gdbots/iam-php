<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\QueryParser\Enum\BoolOperator;
use Gdbots\QueryParser\Node\Field;
use Gdbots\QueryParser\Node\Word;
use Gdbots\QueryParser\ParsedQuery;
use Gdbots\Schemas\Common\Enum\Trinary;
use Gdbots\Schemas\Iam\Mixin\SearchUsersRequest\SearchUsersRequestV1Mixin;
use Gdbots\Schemas\Iam\Mixin\SearchUsersResponse\SearchUsersResponseV1Mixin;
use Gdbots\Schemas\Ncr\Mixin\SearchNodesRequest\SearchNodesRequest;
use Gdbots\Schemas\Ncr\Mixin\SearchNodesResponse\SearchNodesResponse;

class SearchUsersRequestHandler extends AbstractSearchNodesRequestHandler
{
    /**
     * {@inheritdoc}
     */
    protected function createSearchNodesResponse(SearchNodesRequest $request, Pbjx $pbjx): SearchNodesResponse
    {
        /** @var SearchNodesResponse $response */
        $response = SearchUsersResponseV1Mixin::findOne()->createMessage();
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeSearchNodes(SearchNodesRequest $request, ParsedQuery $parsedQuery): void
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

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            SearchUsersRequestV1Mixin::findOne()->getCurie(),
        ];
    }
}
