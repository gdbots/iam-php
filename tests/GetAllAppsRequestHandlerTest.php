<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Node\AndroidAppV1;
use Acme\Schemas\Iam\Request\GetAllAppsRequestV1;
use Gdbots\Iam\GetAllAppsRequestHandler;
use Gdbots\Schemas\Iam\AppId;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;

final class GetAllAppsRequestHandlerTest extends AbstractPbjxTest
{
    public function testWithResultsExpected()
    {
        $node1 = $this->createApp('8695f644-0e7f-11e7-93ae-92361f002671');
        $node2 = $this->createApp('7e95c544-112f-03c5-43ea-92361cd46437');

        $handler = new GetAllAppsRequestHandler($this->ncr);
        $response = $handler->handleRequest(GetAllAppsRequestV1::create(), $this->pbjx);
        $apps = $response->get('nodes', []);

        $this->assertCount(2, $apps);
        $this->assertTrue($node1->equals($apps[0]));
        $this->assertTrue($node2->equals($apps[1]));
    }

    public function testNoResultsExpected()
    {
        $handler = new GetAllAppsRequestHandler($this->ncr);
        $response = $handler->handleRequest(GetAllAppsRequestV1::create(), $this->pbjx);
        $this->assertNull($response->get('nodes'));
    }

    /**
     * Creates a node and put it to NCR
     * @param string $id
     *
     * @return Node
     */
    private function createApp(string $id): Node
    {
        $node = AndroidAppV1::create()->set('_id', AppId::fromString($id));
        $this->ncr->putNode($node);
        return $node;
    }
}
