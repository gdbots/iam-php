<?php
declare(strict_types = 1);

namespace Gdbots\Tests\Iam;

use Gdbots\Iam\CreateUserHandler;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;
use Gdbots\Tests\Iam\Fixtures\Command\CreateUser;
use Gdbots\Tests\Iam\Fixtures\Event\UserCreated;
use Gdbots\Tests\Iam\Fixtures\Node\User;

class CreateUserHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand()
    {
        $command = CreateUser::create();
        $node = User::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db']);
        $node
            ->set('first_name', 'Homer')
            ->set('email', 'homer@simpson.com')
            ->set('is_staff', true)
            ->addToMap('networks', 'twitter', 'homer')
            ->addToSet('roles', [NodeRef::fromString('gdbots:role:polly-shouldnt-be')]);

        $command->set('node', $node);
        $expectedEvent = UserCreated::create();
        $expectedId = $node->get('_id');

        $handler = new CreateUserHandler();
        $handler->handleCommand($command, $this->pbjx);

        $this->eventStore->pipeAllEvents(function(Event $event, StreamId $streamId) use ($expectedEvent, $expectedId) {
            $this->assertSame($event::schema(), $expectedEvent::schema());

            $node = $event->get('node');
            $this->assertSame(NodeStatus::PUBLISHED(), $node->get('status'));
            $this->assertSame(true, $node->get('is_staff'));
            $this->assertSame(false, $node->get('is_blocked'));
            $this->assertSame('Homer', $node->get('title'));
            $this->assertSame('Homer', $node->get('first_name'));
            $this->assertSame('homer@simpson.com', $node->get('email'));
            $this->assertSame('simpson.com', $node->get('email_domain'));
            $this->assertSame(['twitter' => 'homer'], $node->get('networks'));
            $this->assertSame(null, $node->get('roles')); // you can't set roles on "create"
            $this->assertSame(StreamId::fromString("user.history:{$expectedId}")->toString(), $streamId->toString());
            $this->assertSame($event->generateMessageRef()->toString(), (string)$node->get('last_event_ref'));
        });
    }
}
