<?php
declare(strict_types = 1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Node\RoleV1;
use Acme\Schemas\Iam\Request\ListAllRolesRequestV1;
use Gdbots\Iam\ListAllRolesRequestHandler;
use Gdbots\Schemas\Iam\Mixin\ListAllRolesRequest\ListAllRolesRequest;
use Gdbots\Schemas\Iam\RoleId;
use Gdbots\Schemas\Ncr\NodeRef;

class ListAllRolesRequestHandlerTest extends AbstractPbjxTest
{
    public function testHandleRequest() {
        $nodeRef1 = $this->createRole('super-user');
        $nodeRef2 = $this->createRole('article-editor');

        /** @var ListAllRolesRequest $response */
        $request = ListAllRolesRequestV1::create();

        $handler = new ListAllRolesRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);
        $roleNoderefs = $response->get('roles');

        $this->assertEquals(2, count($roleNoderefs));

        $roles = [];
        foreach ($roleNoderefs as $roleNodeRef) {
            $roles[] = $roleNodeRef;
        }

        $this->assertContains($nodeRef1->toString(), $roles);
        $this->assertContains($nodeRef2->toString(), $roles);
    }

    public function testResultNotFound()
    {
        /** @var ListAllRolesRequest $response */
        $request = ListAllRolesRequestV1::create();

        $handler = new ListAllRolesRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);

        $this->assertNull($response->get('roles'));
    }

    /**
     * @param string $id
     * @return NodeRef
     */
    private function createRole(string $id): NodeRef
    {
        $node = RoleV1::create()->set('_id', RoleId::fromString($id));

        $this->ncr->putNode($node, null);

        return NodeRef::fromNode($node);
    }
}
