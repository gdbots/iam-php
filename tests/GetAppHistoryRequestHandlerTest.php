<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Event\AppCreatedV1;
use Acme\Schemas\Iam\Event\AppDeletedV1;
use Acme\Schemas\Iam\Event\AppUpdatedV1;
use Acme\Schemas\Iam\Request\GetAppHistoryRequestV1;
use Acme\Schemas\Iam\Request\GetAppHistoryResponseV1;
use Gdbots\Iam\GetAppHistoryRequestHandler;
use Gdbots\Pbj\WellKnown\Microtime;
use Gdbots\Schemas\Pbjx\StreamId;

final class GetAppHistoryRequestHandlerTest extends AbstractPbjxTest
{
    /**
     * testHandleRequest. Test with default args
     */
    public function testHandleRequest()
    {
        $this->prepareEventsForStreamId('ios-app.history:1234');
        // test default
        $request = GetAppHistoryRequestV1::fromArray([
            'stream_id' => 'ios-app.history:1234',
            'since'     => Microtime::create(),
        ]);

        $handler = new GetAppHistoryRequestHandler();
        /** @var GetAppHistoryResponseV1 $response */
        $response = $handler->handleRequest($request, $this->pbjx);
        $events = $response->get('events');

        $this->assertInstanceOf('Acme\Schemas\Iam\Request\GetAppHistoryResponseV1', $response);
        $this->assertFalse($response->get('has_more'));
        $this->assertEquals(3, count($events));
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\AppDeletedV1', $events[0]);
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\AppCreatedV1', $events[count($events) - 1]);
    }

    /**
     * testHandleRequestWithCount. Test request with count less than results
     */
    public function testHandleRequestWithCount()
    {
        $this->prepareEventsForStreamId('android-app.history:4321');
        // test with count
        $request = GetAppHistoryRequestV1::fromArray([
            'stream_id' => 'android-app.history:4321',
            'since'     => Microtime::create(),
            'count'     => 2,
        ]);

        $handler = new GetAppHistoryRequestHandler();
        /** @var GetAppHistoryResponseV1 $response */
        $response = $handler->handleRequest($request, $this->pbjx);
        $event = $response->get('events');

        //$this->assertTrue($response->get('has_more'));
        $this->assertEquals(2, count($response->get('events')));
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\AppDeletedV1', $event[0]);
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\AppUpdatedV1', $event[count($event) - 1]);
    }

    /**
     * testHandlerRequestWithForward. Test request with forward true
     */
    public function testHandleRequestWithForward()
    {
        $this->prepareEventsForStreamId('sms-app.history:0000');
        // test with forward
        $request = GetAppHistoryRequestV1::fromArray([
            'stream_id' => 'sms-app.history:0000',
            'forward'   => true,
        ]);

        $handler = new GetAppHistoryRequestHandler();
        /** @var GetAppHistoryResponseV1 $response */
        $response = $handler->handleRequest($request, $this->pbjx);
        $event = $response->get('events');

        $this->assertFalse($response->get('has_more'));
        $this->assertEquals(3, count($response->get('events')));
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\AppCreatedV1', $event[0]);
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\AppDeletedV1', $event[count($event) - 1]);
    }

    /**
     * @param $id
     *
     * @return void
     */
    private function prepareEventsForStreamId(string $id): void
    {
        $streamId = StreamId::fromString($id);

        $createAppEvent = AppCreatedV1::create();
        $updateAppEvent = AppUpdatedV1::create();
        $deleteAppEvent = AppDeletedV1::create();

        $this->pbjx->getEventStore()->putEvents($streamId, [$createAppEvent, $updateAppEvent, $deleteAppEvent]);
    }
}
