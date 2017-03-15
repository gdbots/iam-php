<?php
declare(strict_types = 1);

namespace Gdbots\Tests\Iam;

use Gdbots\Iam\GetUserRequestHandler;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Tests\Iam\Fixtures\Node\User;
use Gdbots\Tests\Iam\Fixtures\Request\GetUserRequest;
use Gdbots\Tests\Iam\Fixtures\Request\GetUserResponse;

class GetUserRequestHandlerTest extends AbstractPbjxTest
{
    public function setup()
    {
        parent::setup();

        // ensure schemas are registered with the resolver
        GetUserResponse::schema();
    }

    public function testGetByNodeRefThatExists()
    {
        $node = User::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db']);
        $nodeRef = NodeRef::fromNode($node);
        $this->ncr->putNode($node);

        $request = GetUserRequest::create()->set('node_ref', $nodeRef);
        $handler = new GetUserRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);

        $this->assertSame(GetUserResponse::schema(), $response::schema());
        $this->assertSame($nodeRef->getId(), (string)$response->get('node')->get('_id'));
    }

    /**
     * @expectedException \Gdbots\Ncr\Exception\NodeNotFound
     */
    public function testGetByNodeRefThatDoesNotExists()
    {
        $nodeRef = NodeRef::fromString('gdbots:iam:idontexist');
        $request = GetUserRequest::create()->set('node_ref', $nodeRef);
        $handler = new GetUserRequestHandler($this->ncr);
        $handler->handleRequest($request, $this->pbjx);
    }

    /**
     * @expectedException \Gdbots\Ncr\Exception\NodeNotFound
     */
    public function testGetByNothing()
    {
        $request = GetUserRequest::create();
        $handler = new GetUserRequestHandler($this->ncr);
        $handler->handleRequest($request, $this->pbjx);
    }

    public function testGetByEmail()
    {
        $node = User::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db', 'email' => 'homer@simpson.com']);
        $nodeRef = NodeRef::fromNode($node);
        $email = $node->get('email');
        $this->ncr->putNode($node);

        $request = GetUserRequest::create()
            ->set('qname', $nodeRef->getQName()->toString())
            ->set('email', $email);
        $handler = new GetUserRequestHandler($this->ncr);
        //$response = $handler->handleRequest($request, $this->pbjx);

        //$this->assertSame(GetUserResponse::schema(), $response::schema());
        //$this->assertSame($nodeRef->getId(), (string)$response->get('node')->get('_id'));
    }
}
