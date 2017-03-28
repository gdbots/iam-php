<?php
declare(strict_types = 1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Node\UserV1;
use Acme\Schemas\Iam\Request\GetUserBatchRequestV1;
use Gdbots\Iam\GetUserBatchRequestHandler;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\NodeRef;

class GetUserBatchRequestHandlerTest extends AbstractPbjxTest
{
    public function testHandleRequest()
    {
        $node1 = $this->createUsers('7afcc2f1-9654-46d1-8fc1-b0511df257db');
        $node2 = $this->createUsers('2b591594-10e2-11e7-93ae-92361f002671');
        $node3 = $this->createUsers('301decd0-10e2-11e7-93ae-92361f002671');

        $request = GetUserBatchRequestV1::create()
            ->addToSet('node_refs', [NodeRef::fromNode($node1), NodeRef::fromNode($node2), NodeRef::fromNode($node3)]);

        $handler = new GetUserBatchRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);
        $usersMap = $response->get('nodes');

        $this->assertInstanceOf('Acme\Schemas\Iam\Request\GetUserBatchResponseV1', $response);
        $this->assertInstanceOf('Acme\Schemas\Iam\Node\UserV1', $usersMap['acme:user:7afcc2f1-9654-46d1-8fc1-b0511df257db']);
        $this->assertEquals(3, count($usersMap));
        $this->assertArrayHasKey('acme:user:7afcc2f1-9654-46d1-8fc1-b0511df257db', $usersMap);
        $this->assertArrayHasKey('acme:user:2b591594-10e2-11e7-93ae-92361f002671', $usersMap);
        $this->assertArrayHasKey('acme:user:301decd0-10e2-11e7-93ae-92361f002671', $usersMap);
        $this->assertTrue($node1->equals($usersMap['acme:user:7afcc2f1-9654-46d1-8fc1-b0511df257db']));
        $this->assertTrue($node2->equals($usersMap['acme:user:2b591594-10e2-11e7-93ae-92361f002671']));
        $this->assertTrue($node3->equals($usersMap['acme:user:301decd0-10e2-11e7-93ae-92361f002671']));
    }

    public function testHandlRequestWithMissingNodes()
    {
        $node1 = $this->createUsers('7afcc2f1-9654-46d1-8fc1-b0511df257db');
        $node2 = $this->createUsers('2b591594-10e2-11e7-93ae-92361f002671');

        $request = GetUserBatchRequestV1::create()
            ->addToSet('node_refs', [NodeRef::fromNode($node1), NodeRef::fromNode($node2), NodeRef::fromString('vendor:label:3bbb1694-10e2-15e7-93ae-92361f002672')]);

        $handler = new GetUserBatchRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);
        $usersMap = $response->get('nodes');
        $missingNodes = $response->get('missing_node_refs');

        $this->assertEquals(2, count($usersMap));
        $this->assertEquals(1, count($missingNodes));
        $this->assertArrayHasKey('acme:user:7afcc2f1-9654-46d1-8fc1-b0511df257db', $usersMap);
        $this->assertArrayHasKey('acme:user:2b591594-10e2-11e7-93ae-92361f002671', $usersMap);
        $this->assertTrue($node1->equals($usersMap['acme:user:7afcc2f1-9654-46d1-8fc1-b0511df257db']));
        $this->assertTrue($node2->equals($usersMap['acme:user:2b591594-10e2-11e7-93ae-92361f002671']));
        $this->assertContains('vendor:label:3bbb1694-10e2-15e7-93ae-92361f002672', $missingNodes);
    }
    /**
     * @param string $id
     * @return Node
     */
    public function createUsers(string $id = ''): Node
    {
        $node = UserV1::fromArray(['_id' => $id]);
        $this->ncr->putNode($node);

        return $node;
    }
}
