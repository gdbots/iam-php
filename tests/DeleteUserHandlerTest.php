<?php
declare(strict_types = 1);

namespace Gdbots\Iam;

use Acme\Schemas\Iam\Command\DeleteUserV1;
use Acme\Schemas\Iam\Event\UserDeletedV1;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;
use Gdbots\Tests\Iam\AbstractPbjxTest;

class DeleteUserHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand()
    {
        $node_ref = NodeRef::fromString('acme:user:8695f644-0e7f-11e7-93ae-92361f002671');

        $command = DeleteUserV1::create();
        $command->set('node_ref', $node_ref);

        $expectedEvent = UserDeletedV1::create();

        $handler = new DeleteUserHandler();
        $handler->handleCommand($command, $this->pbjx);

        $this->eventStore->pipeAllEvents(function(Event $event, StreamId $streamId) use ($expectedEvent) {
            $this->assertSame($event::schema(), $expectedEvent::schema());
            $this->assertSame(StreamId::fromString("user.history:{$event->get('node_ref')->getId()}")->toString(), $streamId->toString());
        });
    }
}
