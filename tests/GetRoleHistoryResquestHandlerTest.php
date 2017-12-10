<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Acme\Schemas\Iam\Event\RoleCreatedV1;
use Acme\Schemas\Iam\Event\RoleDeletedV1;
use Acme\Schemas\Iam\Event\RoleUpdatedV1;
use Acme\Schemas\Iam\Request\GetRoleHistoryRequestV1;
use Gdbots\Pbj\WellKnown\Microtime;
use Gdbots\Schemas\Pbjx\StreamId;
use Gdbots\Tests\Iam\AbstractPbjxTest;

final class GetRoleHistoryResquestHandlerTest extends AbstractPbjxTest
{
    /**
     * testHandleRequest. Test with default args
     */
    public function testHandleRequest()
    {
        $this->prepareEventsForStreamId('role.history:1234');
        // test default
        $request = GetRoleHistoryRequestV1::fromArray([
            'stream_id' => 'role.history:1234',
            'since'     => Microtime::create(),
        ]);

        $handler = new GetRoleHistoryRequestHandler();
        $response = $handler->handleRequest($request, $this->pbjx);
        $events = $response->get('events');

        $this->assertInstanceOf('Acme\Schemas\Iam\Request\GetRoleHistoryResponseV1', $response);
        $this->assertFalse($response->get('has_more'));
        $this->assertEquals(3, count($events));
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\RoleDeletedV1', $events[0]);
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\RoleCreatedV1', $events[count($events) - 1]);
    }

    /**
     * testHandleRequestWithCount. Test request with count less than results
     */
    public function testHandleRequestWithCount()
    {
        $this->prepareEventsForStreamId('role.history:4321');
        // test with count
        $request = GetRoleHistoryRequestV1::fromArray([
            'stream_id' => 'role.history:4321',
            'since'     => Microtime::create(),
            'count'     => 2,
        ]);

        $handler = new GetRoleHistoryRequestHandler();
        $response = $handler->handleRequest($request, $this->pbjx);
        $event = $response->get('events');

        //$this->assertTrue($response->get('has_more'));
        $this->assertEquals(2, count($response->get('events')));
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\RoleDeletedV1', $event[0]);
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\RoleUpdatedV1', $event[count($event) - 1]);
    }

    /**
     * testHandlerRequestWithForward. Test request with forward true
     */
    public function testHandleRequestWithForward()
    {
        $this->prepareEventsForStreamId('role.history:0000');
        // test with forward
        $request = GetRoleHistoryRequestV1::fromArray([
            'stream_id' => 'role.history:0000',
            'forward'   => true,
        ]);

        $handler = new GetRoleHistoryRequestHandler();
        $response = $handler->handleRequest($request, $this->pbjx);
        $event = $response->get('events');

        $this->assertFalse($response->get('has_more'));
        $this->assertEquals(3, count($response->get('events')));
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\RoleCreatedV1', $event[0]);
        $this->assertInstanceOf('Acme\Schemas\Iam\Event\RoleDeletedV1', $event[count($event) - 1]);
    }

    /**
     * @param $id
     *
     * @return void
     */
    private function prepareEventsForStreamId(string $id): void
    {
        $streamId = StreamId::fromString($id);

        $createRoleEvent = RoleCreatedV1::create();
        $updateRoleEvent = RoleUpdatedV1::create();
        $deleteRoleEvent = RoleDeletedV1::create();

        $this->pbjx->getEventStore()->putEvents($streamId, [$createRoleEvent, $updateRoleEvent, $deleteRoleEvent]);
    }
}
