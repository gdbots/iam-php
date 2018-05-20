<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Node\AndroidAppV1;
use Acme\Schemas\Iam\Request\ListAllAppsRequestV1;
use Gdbots\Iam\ListAllAppsRequestHandler;
use Gdbots\Schemas\Iam\AppId;
use Gdbots\Schemas\Ncr\NodeRef;

final class ListAllAppsRequestHandlerTest extends AbstractPbjxTest
{
    public function testWithResultsExpected()
    {
        $nodeRef1 = $this->createApp('8695f644-0e7f-11e7-93ae-92361f002671');
        $nodeRef2 = $this->createApp('7e95c544-112f-03c5-43ea-92361cd46437');

        $handler = new ListAllAppsRequestHandler($this->ncr);
        $response = $handler->handleRequest(ListAllAppsRequestV1::create(), $this->pbjx);
        $apps = $response->get('apps', []);

        $this->assertCount(2, $apps);
        $this->assertContains($nodeRef1->toString(), $apps);
        $this->assertContains($nodeRef2->toString(), $apps);
    }

    public function testNoResultsExpected()
    {
        $handler = new ListAllAppsRequestHandler($this->ncr);
        $response = $handler->handleRequest(ListAllAppsRequestV1::create(), $this->pbjx);
        $this->assertNull($response->get('apps'));
    }

    private function createApp(string $id): NodeRef
    {
        $node = AndroidAppV1::create()->set('_id', AppId::fromString($id));
        $this->ncr->putNode($node);
        return NodeRef::fromNode($node);
    }
}
