<?php
declare(strict_types = 1);

namespace Gdbots\Iam;

use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Pbjx\RequestHandlerTrait;
use Gdbots\QueryParser\Enum\BoolOperator;
use Gdbots\QueryParser\Node\Field;
use Gdbots\QueryParser\Node\Word;
use Gdbots\QueryParser\ParsedQuery;
use Gdbots\Schemas\Common\Enum\Trinary;
use Gdbots\Schemas\Iam\Mixin\SearchUsersRequest\SearchUsersRequest;
use Gdbots\Schemas\Iam\Mixin\SearchUsersResponse\SearchUsersResponse;
use Gdbots\Schemas\Iam\Mixin\SearchUsersResponse\SearchUsersResponseV1Mixin;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\SearchNodesRequest\SearchNodesRequest;

final class SearchUsersRequestHandler implements RequestHandler
{
    use RequestHandlerTrait;

    /** @var NcrSearch */
    private $ncrSearch;

    /**
     * @param NcrSearch $ncrSearch
     */
    public function __construct(NcrSearch $ncrSearch)
    {
        $this->ncrSearch = $ncrSearch;
    }

    /**
     * @param SearchUsersRequest $request
     * @param Pbjx               $pbjx
     *
     * @return SearchUsersResponse
     */
    protected function handle(SearchUsersRequest $request, Pbjx $pbjx): SearchUsersResponse
    {
        $schema = MessageResolver::findOneUsingMixin(SearchUsersResponseV1Mixin::create(), 'iam', 'request');
        /** @var SearchUsersResponse $response */
        $response = $schema->createMessage();

        $parsedQuery = ParsedQuery::fromArray(json_decode($request->get('parsed_query_json', '{}'), true));
        $required = BoolOperator::REQUIRED();
        $prohibited = BoolOperator::PROHIBITED();

        if (!$request->has('status') && !$request->isInSet('fields_used', 'status')) {
            $parsedQuery->addNode(new Field('status', new Word(NodeStatus::DELETED, $prohibited), $prohibited));
        }

        if (Trinary::UNKNOWN !== $request->get('is_staff')) {
            $parsedQuery->addNode(
                new Field(
                    'is_staff',
                    new Word(Trinary::TRUE_VAL === $request->get('is_staff') ? 'true' : 'false', $required),
                    $required
                )
            );
        }

        if (Trinary::UNKNOWN !== $request->get('is_blocked')) {
            $parsedQuery->addNode(
                new Field(
                    'is_blocked',
                    new Word(Trinary::TRUE_VAL === $request->get('is_blocked') ? 'true' : 'false', $required),
                    $required
                )
            );
        }

        /** @var SearchNodesRequest $request */
        $this->ncrSearch->searchNodes(
            $request,
            $parsedQuery,
            $response,
            [SchemaQName::fromString("{$response::schema()->getId()->getVendor()}:user")]
        );

        return $response;
    }
}
