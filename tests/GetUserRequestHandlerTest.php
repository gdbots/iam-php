<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Node\UserV1;
use Gdbots\Iam\GetUserRequestHandler;
use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Iam\Request\GetUserRequestV1;
use Gdbots\Schemas\Iam\Request\GetUserResponseV1;

final class GetUserRequestHandlerTest extends AbstractPbjxTest
{
    public function testGetByNodeRefThatExists()
    {
        $node = UserV1::fromArray([
            '_id'    => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'status' => 'published',
        ]);
        $nodeRef = NodeRef::fromNode($node);
        $this->ncr->putNode($node);

        $request = GetUserRequestV1::create()->set('node_ref', $nodeRef);
        $handler = new GetUserRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);

        $this->assertSame(GetUserResponseV1::schema(), $response::schema());
        $this->assertSame($nodeRef->getId(), (string)$response->get('node')->get('_id'));
    }

    public function testGetByNodeRefThatDoesNotExists()
    {
        $this->expectException(NodeNotFound::class);
        $nodeRef = NodeRef::fromString('gdbots:iam:idontexist');
        $request = GetUserRequestV1::create()->set('node_ref', $nodeRef);
        $handler = new GetUserRequestHandler($this->ncr);
        $handler->handleRequest($request, $this->pbjx);
    }

    public function testGetByNothing()
    {
        $this->expectException(NodeNotFound::class);
        $request = GetUserRequestV1::create();
        $handler = new GetUserRequestHandler($this->ncr);
        $handler->handleRequest($request, $this->pbjx);
    }

    public function testGetByEmailThatExists()
    {
        $node = UserV1::fromArray([
            '_id'    => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'email'  => 'homer@simpson.com',
            'status' => 'published',
        ]);
        $nodeRef = NodeRef::fromNode($node);
        $email = $node->get('email');
        $this->ncr->putNode($node);

        $request = GetUserRequestV1::create()
            ->set('qname', $nodeRef->getQName()->toString())
            ->set('email', $email);
        $handler = new GetUserRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);

        $this->assertSame(GetUserResponseV1::schema(), $response::schema());
        $this->assertSame($nodeRef->getId(), (string)$response->get('node')->get('_id'));
    }

    public function testGetByEmailThatDoesNotExists()
    {
        $this->expectException(NodeNotFound::class);
        $request = GetUserRequestV1::create()
            ->set('qname', SchemaQName::fromString('acme:user')->toString())
            ->set('email', 'homer@simpson.com');
        $handler = new GetUserRequestHandler($this->ncr);
        $handler->handleRequest($request, $this->pbjx);
    }
}
