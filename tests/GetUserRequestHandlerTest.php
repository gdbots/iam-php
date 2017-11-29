<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Node\UserV1;
use Acme\Schemas\Iam\Request\GetUserRequestV1;
use Acme\Schemas\Iam\Request\GetUserResponseV1;
use Gdbots\Iam\GetUserRequestHandler;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Schemas\Ncr\NodeRef;

class GetUserRequestHandlerTest extends AbstractPbjxTest
{
    public function testGetByNodeRefThatExists()
    {
        $node = UserV1::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db']);
        $nodeRef = NodeRef::fromNode($node);
        $this->ncr->putNode($node);

        $request = GetUserRequestV1::create()->set('node_ref', $nodeRef);
        $handler = new GetUserRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);

        $this->assertSame(GetUserResponseV1::schema(), $response::schema());
        $this->assertSame($nodeRef->getId(), (string)$response->get('node')->get('_id'));
    }

    /**
     * @expectedException \Gdbots\Ncr\Exception\NodeNotFound
     */
    public function testGetByNodeRefThatDoesNotExists()
    {
        $nodeRef = NodeRef::fromString('gdbots:iam:idontexist');
        $request = GetUserRequestV1::create()->set('node_ref', $nodeRef);
        $handler = new GetUserRequestHandler($this->ncr);
        $handler->handleRequest($request, $this->pbjx);
    }

    /**
     * @expectedException \Gdbots\Ncr\Exception\NodeNotFound
     */
    public function testGetByNothing()
    {
        $request = GetUserRequestV1::create();
        $handler = new GetUserRequestHandler($this->ncr);
        $handler->handleRequest($request, $this->pbjx);
    }

    public function testGetByEmailThatExists()
    {
        $node = UserV1::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db', 'email' => 'homer@simpson.com']);
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

    /**
     * @expectedException \Gdbots\Ncr\Exception\NodeNotFound
     */
    public function testGetByEmailThatDoesNotExists()
    {
        $request = GetUserRequestV1::create()
            ->set('qname', SchemaQName::fromString('acme:user')->toString())
            ->set('email', 'homer@simpson.com');
        $handler = new GetUserRequestHandler($this->ncr);
        $handler->handleRequest($request, $this->pbjx);
    }
}
