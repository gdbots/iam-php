<?php
declare(strict_types = 1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Command\DeleteRoleV1;
use Acme\Schemas\Iam\Event\RoleDeletedV1;
use Gdbots\Iam\DeleteRoleHandler;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;


class DeleteRoleHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand()
    {
        $node_ref = NodeRef::fromString('acme:role:8695f644-0e7f-11e7-93ae-92361f002671');

        $command = DeleteRoleV1::create();
        $command->set('node_ref', $node_ref);

        $expectedEvent = RoleDeletedV1::create();

        $handler = new DeleteRoleHandler();
        $handler->handleCommand($command, $this->pbjx);

        $this->eventStore->pipeAllEvents(function(Event $event, StreamId $streamId) use ($expectedEvent) {
            $this->assertSame($event::schema(), $expectedEvent::schema());
            $this->assertSame(StreamId::fromString("role.history:{$event->get('node_ref')->getId()}")->toString(), $streamId->toString());
        });
    }
}
