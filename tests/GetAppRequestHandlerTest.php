<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Acme\Schemas\Iam\Node\SlackAppV1;
use Acme\Schemas\Iam\Request\GetAppRequestV1;
use Gdbots\Pbj\WellKnown\Microtime;
use Gdbots\Schemas\Iam\AppId;
use Gdbots\Tests\Iam\AbstractPbjxTest;

final class GetAppRequestHandlerTest extends AbstractPbjxTest
{
    public function testHandleRequest()
    {
        $this->createApp();

        $request = GetAppRequestV1::fromArray([
            'node_ref' => 'acme:slack-app:8695f644-0e7f-11e7-93ae-92361f002671',
        ]);

        $handler = new GetAppRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);
        $responseApp = $response->get('node');

        $this->assertInstanceOf('Acme\Schemas\Iam\Node\SlackAppV1', $responseApp);
        $this->assertSame('8695f644-0e7f-11e7-93ae-92361f002671', $responseApp->get('_id')->toString());
        $this->assertSame('the test slack app', $responseApp->get('title'));
    }

    /**
     * @expectedException \Gdbots\Ncr\Exception\NodeNotFound
     */
    public function testHandleNodeRefNotFound()
    {
        $request = GetAppRequestV1::create();

        $handler = new GetAppRequestHandler($this->ncr);
        $handler->handleRequest($request, $this->pbjx);
    }

    /**
     * @expectedException \Gdbots\Ncr\Exception\NodeNotFound
     */
    public function testHandleNodeNotFound()
    {
        $request = GetAppRequestV1::fromArray([
            'node_ref' => 'acme:app:wrong-node-ref',
        ]);

        $handler = new GetAppRequestHandler($this->ncr);
        $handler->handleRequest($request, $this->pbjx);
    }

    /**
     *  create a app and put it to ncr
     */
    private function createApp(): void
    {
        $node = SlackAppV1::create()
            ->set('_id', AppId::fromString('8695f644-0e7f-11e7-93ae-92361f002671'))
            ->set('title', 'the test slack app')
            ->set('updated_at', Microtime::create());

        $this->ncr->putNode($node, null);
    }
}
