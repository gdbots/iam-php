<?php
declare(strict_types = 1);

namespace Gdbots\Iam;

use Acme\Schemas\Iam\Node\RoleV1;
use Acme\Schemas\Iam\Request\GetRoleRequestV1;
use Gdbots\Pbj\WellKnown\Microtime;
use Gdbots\Schemas\Iam\RoleId;
use Gdbots\Tests\Iam\AbstractPbjxTest;

class GetRoleRequestHandlerTest extends AbstractPbjxTest
{
    public function testHandleRequest()
    {
        $this->createRole();

        $request = GetRoleRequestV1::fromArray([
            'node_ref' => 'acme:role:super-user'
        ]);

        $handler = new GetRoleRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);
        $responseRole = $response->get('node');

        $this->assertInstanceOf('Acme\Schemas\Iam\Node\RoleV1', $responseRole);
        $this->assertSame('super-user', $responseRole->get('_id')->toString());
        $this->assertSame(['acme:blog:command:*', 'acme:video:command:*'], $responseRole->get('allowed'));
        $this->assertSame(['acme:blog:command:publish-article'], $responseRole->get('denied'));
    }

    /**
     * @expectedException Gdbots\Ncr\Exception\NodeNotFound
     * @expectedExceptionMessage No method available to find role.
     */
    public function testHandleNodeRefNotFound()
    {
        $request = GetRoleRequestV1::create();

        $handler = new GetRoleRequestHandler($this->ncr);
        $handler->handleRequest($request, $this->pbjx);
    }

    /**
     * @expectedException Gdbots\Ncr\Exception\NodeNotFound
     */
    public function testHandleNodeNotFound()
    {
        $request = GetRoleRequestV1::fromArray([
            'node_ref' => 'acme:role:wrong-node-ref'
        ]);

        $handler = new GetRoleRequestHandler($this->ncr);
        $handler->handleRequest($request, $this->pbjx);
    }

    /**
     *  create a role and put it to ncr
     */
    private function createRole(): void
    {
        $node = RoleV1::create()
            ->set('_id', RoleId::fromString('super-user'))
            ->addToSet('allowed', ['acme:blog:command:*', 'acme:video:command:*'])
            ->addToSet('denied', ['acme:blog:command:publish-article'])
            ->set('updated_at', Microtime::create());

        $this->ncr->putNode($node, null);
    }
}
