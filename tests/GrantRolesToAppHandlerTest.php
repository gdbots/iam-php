<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Command\GrantRolesToAppV1;
use Acme\Schemas\Iam\Event\AppRolesGrantedV1;
use Acme\Schemas\Iam\Node\IosAppV1;
use Gdbots\Iam\GrantRolesToAppHandler;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;

final class GrantRolesToAppHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand()
    {
        $node = IosAppV1::fromArray(['_id' => '8695f644-0e7f-11e7-93ae-92361f002671']);
        $this->ncr->putNode($node);
        $nodeRef = NodeRef::fromNode($node);

        $roles = [
            NodeRef::fromString('acme:role:admin-app'),
            NodeRef::fromString('acme:role:tester-app'),
        ];

        $command = GrantRolesToAppV1::create()
            ->set('node_ref', $nodeRef)
            ->addToSet('roles', $roles);

        $expectedEvent = AppRolesGrantedV1::create();

        $handler = new GrantRolesToAppHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        $this->eventStore->pipeAllEvents(function (Event $event, StreamId $streamId) use ($expectedEvent, $roles) {
            $this->assertSame($event::schema(), $expectedEvent::schema());
            $this->assertSame(StreamId::fromString("ios-app.history:{$event->get('node_ref')->getId()}")->toString(), $streamId->toString());
            $this->assertSame($roles, $event->get('roles'));
        });
    }

    public function testHandleCommandRolesNotProvided()
    {
        $node = IosAppV1::fromArray(['_id' => '8695f644-0e7f-11e7-93ae-92361f002671']);
        $this->ncr->putNode($node);
        $nodeRef = NodeRef::fromNode($node);

        $command = GrantRolesToAppV1::create()->set('node_ref', $nodeRef);

        $expectedEvent = AppRolesGrantedV1::create();

        $handler = new GrantRolesToAppHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        $this->eventStore->pipeAllEvents(function (Event $event, StreamId $streamId) use ($expectedEvent) {
            $this->assertSame($event::schema(), $expectedEvent::schema());
            $this->assertSame(StreamId::fromString("ios-app.history:{$event->get('node_ref')->getId()}")->toString(), $streamId->toString());
            $this->assertEmpty($event->get('roles'));
        });
    }
}
