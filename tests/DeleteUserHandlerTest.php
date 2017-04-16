<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Command\DeleteUserV1;
use Acme\Schemas\Iam\Event\UserDeletedV1;
use Gdbots\Iam\DeleteUserHandler;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;

class DeleteUserHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand()
    {
        $nodeRef = NodeRef::fromString('acme:user:8695f644-0e7f-11e7-93ae-92361f002671');
        $command = DeleteUserV1::create();
        $command->set('node_ref', $nodeRef);

        $expectedEvent = UserDeletedV1::create();
        $expectedId = $nodeRef->getId();

        $handler = new DeleteUserHandler();
        $handler->handleCommand($command, $this->pbjx);

        $this->eventStore->pipeAllEvents(function (Event $event, StreamId $streamId) use ($expectedEvent, $expectedId) {
            $this->assertSame($event::schema(), $expectedEvent::schema());
            $this->assertSame(StreamId::fromString("user.history:{$expectedId}")->toString(), $streamId->toString());
        });
    }
}
