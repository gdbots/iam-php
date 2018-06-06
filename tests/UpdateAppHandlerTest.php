<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Command\UpdateAppV1;
use Acme\Schemas\Iam\Event\AppUpdatedV1;
use Acme\Schemas\Iam\Node\SmsAppV1;
use Gdbots\Iam\UpdateAppHandler;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;

final class UpdateAppHandlerTest extends AbstractPbjxTest
{
    public function testUpdateApp()
    {
        $command = UpdateAppV1::create();

        $oldNode = SmsAppV1::fromArray([
            '_id'   => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'title' => 'Homer Test',
        ]);
        $this->ncr->putNode($oldNode);

        $newNode = SmsAppV1::fromArray([
            '_id'   => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'title' => 'updated',
            'roles' => ['acme:role:tester'],
        ]);

        $command->set('node_ref', NodeRef::fromNode($oldNode));
        //$command->set('old_node', $oldNode);
        $command->set('new_node', $newNode);

        $handler = new UpdateAppHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        $expectedEvent = AppUpdatedV1::create();
        $expectedId = $oldNode->get('_id');
        $expectedRoles = $oldNode->get('roles');

        $this->eventStore->pipeAllEvents(
            function (Event $event, StreamId $streamId) use ($expectedEvent, $expectedId, $expectedRoles) {
                $this->assertSame($event::schema(), $expectedEvent::schema());
                $this->assertTrue($event->has('old_node'));
                $this->assertTrue($event->has('new_node'));

                $newNodeFromEvent = $event->get('new_node');

                $this->assertSame(NodeStatus::PUBLISHED(), $newNodeFromEvent->get('status'));
                $this->assertEquals('updated', $newNodeFromEvent->get('title'));
                $this->assertNull($expectedRoles);
                $this->assertSame(StreamId::fromString("sms-app.history:{$expectedId}")->toString(), $streamId->toString());
                $this->assertSame($event->generateMessageRef()->toString(), (string)$newNodeFromEvent->get('last_event_ref'));
            });
    }
}
