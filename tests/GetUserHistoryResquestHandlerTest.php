<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Acme\Schemas\Iam\Event\UserCreatedV1;
use Acme\Schemas\Iam\Event\UserDeletedV1;
use Acme\Schemas\Iam\Event\UserUpdatedV1;
use Acme\Schemas\Iam\Request\GetUserHistoryRequestV1;
use Gdbots\Pbj\WellKnown\Microtime;
use Gdbots\Schemas\Pbjx\StreamId;
use Gdbots\Tests\Iam\AbstractPbjxTest;

final class GetUserHistoryResquestHandlerTest extends AbstractPbjxTest
{
    /**
     * testHandleRequest. Test with default args
     */
    public function testHandleRequest()
    {
        $this->prepareEventsForStreamId('user.history:1234');
        // test default
        $request = GetUserHistoryRequestV1::fromArray([
            'stream_id' => 'user.history:1234',
            'since'     => Microtime::create(),
        ]);

        $handler = new GetUserHistoryRequestHandler();
        $response = $handler->handleRequest($request, $this->pbjx);
        $events = $response->get('events');

        $this->assertInstanceOf('Acme\Schemas\Iam\Request\GetUserHistoryResponseV1', $response);
        $this->assertFalse($response->get('has_more'));
        $this->assertEquals(3, count($events));
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\UserDeletedV1', $events[0]);
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\UserCreatedV1', $events[count($events) - 1]);
    }

    /**
     * testHandleRequestWithCount. Test request with count less than results
     */
    public function testHandleRequestWithCount()
    {
        $this->prepareEventsForStreamId('user.history:4321');
        // test with count
        $request = GetUserHistoryRequestV1::fromArray([
            'stream_id' => 'user.history:4321',
            'since'     => Microtime::create(),
            'count'     => 2,
        ]);

        $handler = new GetUserHistoryRequestHandler();
        $response = $handler->handleRequest($request, $this->pbjx);
        $event = $response->get('events');

        //$this->assertTrue($response->get('has_more'));
        $this->assertEquals(2, count($response->get('events')));
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\UserDeletedV1', $event[0]);
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\UserUpdatedV1', $event[count($event) - 1]);
    }

    /**
     * testHandlerRequestWithForward. Test request with forward true
     */
    public function testHandleRequestWithForward()
    {
        $this->prepareEventsForStreamId('user.history:0000');
        // test with forward
        $request = GetUserHistoryRequestV1::fromArray([
            'stream_id' => 'user.history:0000',
            'forward'   => true,
        ]);

        $handler = new GetUserHistoryRequestHandler();
        $response = $handler->handleRequest($request, $this->pbjx);
        $event = $response->get('events');

        $this->assertFalse($response->get('has_more'));
        $this->assertEquals(3, count($response->get('events')));
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\UserCreatedV1', $event[0]);
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\UserDeletedV1', $event[count($event) - 1]);
    }

    /**
     * @param $id
     *
     * @return void
     */
    private function prepareEventsForStreamId(string $id): void
    {
        $streamId = StreamId::fromString($id);

        $createUserEvent = UserCreatedV1::create();
        $updateUserEvent = UserUpdatedV1::create();
        $deleteUserEvent = UserDeletedV1::create();

        $this->pbjx->getEventStore()->putEvents($streamId, [$createUserEvent, $updateUserEvent, $deleteUserEvent]);
    }
}
