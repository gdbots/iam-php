<?php
declare(strict_types = 1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Command\GrantRolesToUserV1;
use Acme\Schemas\Iam\Event\UserRolesGrantedV1;
use Gdbots\Iam\GrantRolesToUserHandler;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;

class GrantRolesToUserHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand()
    {
        $node_ref = NodeRef::fromString('acme:user:8695f644-0e7f-11e7-93ae-92361f002671');
        $roles = [
            NodeRef::fromString('acme:role:admin-user'),
            NodeRef::fromString('acme:role:tester-user')
        ];

        $command = GrantRolesToUserV1::create()
        ->set('node_ref', $node_ref)
        ->addToSet('roles', $roles);

        $expectedEvent = UserRolesGrantedV1::create();

        $handler = new GrantRolesToUserHandler();
        $handler->handleCommand($command, $this->pbjx);

        $this->eventStore->pipeAllEvents(function(Event $event, StreamId $streamId) use ($expectedEvent, $roles) {
            $this->assertSame($event::schema(), $expectedEvent::schema());
            $this->assertSame(StreamId::fromString("user.history:{$event->get('node_ref')->getId()}")->toString(), $streamId->toString());
            $this->assertSame($roles, $event->get('roles'));
        });
    }

    public function testHandleCommandRolesNotProvided()
    {
        $node_ref = NodeRef::fromString('acme:user:8695f644-0e7f-11e7-93ae-92361f002671');

        $command = GrantRolesToUserV1::create()->set('node_ref', $node_ref);

        $expectedEvent = UserRolesGrantedV1::create();

        $handler = new GrantRolesToUserHandler();
        $handler->handleCommand($command, $this->pbjx);

        $this->eventStore->pipeAllEvents(function(Event $event, StreamId $streamId) use ($expectedEvent) {
            $this->assertSame($event::schema(), $expectedEvent::schema());
            $this->assertSame(StreamId::fromString("user.history:{$event->get('node_ref')->getId()}")->toString(), $streamId->toString());
            $this->assertEmpty($event->get('roles'));
        });
    }
}
