<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Node\RoleV1;
use Acme\Schemas\Iam\Request\ListAllRolesRequestV1;
use Gdbots\Iam\ListAllRolesRequestHandler;
use Gdbots\Schemas\Iam\RoleId;
use Gdbots\Schemas\Ncr\NodeRef;

class ListAllRolesRequestHandlerTest extends AbstractPbjxTest
{
    public function testWithResultsExpected()
    {
        $nodeRef1 = $this->createRole('super-user');
        $nodeRef2 = $this->createRole('article-editor');

        $handler = new ListAllRolesRequestHandler($this->ncr);
        $response = $handler->handleRequest(ListAllRolesRequestV1::create(), $this->pbjx);
        $roles = $response->get('roles', []);

        $this->assertCount(2, $roles);
        $this->assertContains($nodeRef1->toString(), $roles);
        $this->assertContains($nodeRef2->toString(), $roles);
    }

    public function testNoResultsExpected()
    {
        $handler = new ListAllRolesRequestHandler($this->ncr);
        $response = $handler->handleRequest(ListAllRolesRequestV1::create(), $this->pbjx);
        $this->assertNull($response->get('roles'));
    }

    private function createRole(string $id): NodeRef
    {
        $node = RoleV1::create()->set('_id', RoleId::fromString($id));
        $this->ncr->putNode($node);
        return NodeRef::fromNode($node);
    }
}
