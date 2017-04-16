<?php
declare(strict_types=1);

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
        $nodeRef = NodeRef::fromString('acme:role:super-user');
        $command = DeleteRoleV1::create();
        $command->set('node_ref', $nodeRef);

        $expectedEvent = RoleDeletedV1::create();
        $expectedId = $nodeRef->getId();

        $handler = new DeleteRoleHandler();
        $handler->handleCommand($command, $this->pbjx);

        $this->eventStore->pipeAllEvents(function (Event $event, StreamId $streamId) use ($expectedEvent, $expectedId) {
            $this->assertSame($event::schema(), $expectedEvent::schema());
            $this->assertSame(StreamId::fromString("role.history:{$expectedId}")->toString(), $streamId->toString());
        });
    }
}
