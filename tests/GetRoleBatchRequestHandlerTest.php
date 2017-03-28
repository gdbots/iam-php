<?php
declare(strict_types = 1);

namespace Gdbots\Iam;

use Acme\Schemas\Iam\Node\RoleV1;
use Acme\Schemas\Iam\Request\GetRoleBatchRequestV1;
use Gdbots\Schemas\Iam\RoleId;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Tests\Iam\AbstractPbjxTest;

class GetRoleBatchRequestHandlerTest extends AbstractPbjxTest
{
    public function testHandleRequest()
    {
        $node1 = $this->createRoles('super-user');
        $node2 = $this->createRoles('article-editor');
        $node3 = $this->createRoles('tester');

        $request = GetRoleBatchRequestV1::create()
            ->addToSet('node_refs', [NodeRef::fromNode($node1), NodeRef::fromNode($node2), NodeRef::fromNode($node3)]);

        $handler = new GetRoleBatchRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);
        $rolesMap = $response->get('nodes');

        $this->assertInstanceOf('Acme\Schemas\Iam\Request\GetRoleBatchResponseV1', $response);
        $this->assertInstanceOf('Acme\Schemas\Iam\Node\RoleV1', $rolesMap['acme:role:super-user']);
        $this->assertEquals(3, count($rolesMap));
        $this->assertArrayNotHasKey('missing_node_refs', $response->toArray());
        $this->assertArrayHasKey('acme:role:super-user', $rolesMap);
        $this->assertArrayHasKey('acme:role:article-editor', $rolesMap);
        $this->assertArrayHasKey('acme:role:tester', $rolesMap);
        $this->assertTrue($node1->equals($rolesMap['acme:role:super-user']));
        $this->assertTrue($node3->equals($rolesMap['acme:role:tester']));
    }

    public function testHandleRequestWithMissing()
    {
        $node1 = $this->createRoles('super-user');
        $node3 = $this->createRoles('tester');

        $request = GetRoleBatchRequestV1::create()
            ->addToSet('node_refs', [NodeRef::fromNode($node1), NodeRef::fromString('do:not:exists'), NodeRef::fromNode($node3)]);

        $handler = new GetRoleBatchRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);
        $rolesMap = $response->get('nodes');
        $roleMissing = $response->get('missing_node_refs');

        $this->assertEquals(2, count($rolesMap));
        $this->assertEquals(1, count($roleMissing));
        $this->assertArrayHasKey('acme:role:super-user', $rolesMap);
        $this->assertArrayHasKey('acme:role:tester', $rolesMap);
        $this->assertArrayNotHasKey('acme:role:article-editor', $rolesMap);
        $this->assertTrue($node1->equals($rolesMap['acme:role:super-user']));
        $this->assertTrue($node3->equals($rolesMap['acme:role:tester']));
        $this->assertContains('do:not:exists', $roleMissing);
    }

    /**
     * @param string $id
     * @return Node
     */
    public function createRoles(string $id = ''): Node
    {
        $node = RoleV1::fromArray(['_id' => RoleId::fromString($id)]);
        $this->ncr->putNode($node);

        return $node;
    }
}
