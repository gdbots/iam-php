<?php
declare(strict_types = 1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Command\UpdateUserV1;
use Acme\Schemas\Iam\Event\UserUpdatedV1;
use Acme\Schemas\Iam\Node\UserV1;
use Gdbots\Iam\UpdateUserHandler;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;


class UpdateUserHandlerTest extends AbstractPbjxTest
{
    public function testUpdateUser()
    {
        $command = UpdateUserV1::create();

        $oldNode = UserV1::fromArray([
            '_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'email' => 'homer@simpson.com',
            'title' => 'Homer Test',
            'roles' => ['acme:role:super-user']
        ]);

        $newNode = UserV1::fromArray([
            '_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'email' => 'homer-updated@simpson.com',
            'first_name' => 'Update',
            'last_name' => 'Title',
            'roles' => ['acme:role:tester']
        ]);

        $command->set('old_node', $oldNode);
        $command->set('new_node', $newNode);

        $handler = new UpdateUserHandler();
        $handler->handleCommand($command, $this->pbjx);

        $expectedEvent = UserUpdatedV1::create();
        $expectedId = $oldNode->get('_id');
        $expectedEmail = $oldNode->get('email');
        $expectedRoles = $oldNode->get('roles');

        $this->eventStore->pipeAllEvents(
            function(Event $event, StreamId $streamId) use ($expectedEvent, $expectedId, $expectedEmail, $expectedRoles)
            {
                $this->assertSame($event::schema(), $expectedEvent::schema());
                $this->assertTrue($event->has('old_node'));
                $this->assertTrue($event->has('new_node'));

                $newNodeFromEvent = $event->get('new_node');

                $this->assertSame(NodeStatus::PUBLISHED(), $newNodeFromEvent->get('status'));
                $this->assertEquals('Update Title', $newNodeFromEvent->get('title'));
                $this->assertEquals($expectedEmail, $newNodeFromEvent->get('email'));
                $this->assertSame($expectedRoles, $newNodeFromEvent->get('roles'));
                $this->assertSame(StreamId::fromString("user.history:{$expectedId}")->toString(), $streamId->toString());
                $this->assertSame($event->generateMessageRef()->toString(), (string)$newNodeFromEvent->get('last_event_ref'));
            });
    }
}
