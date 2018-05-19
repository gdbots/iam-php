<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Command\CreateAppV1;
use Acme\Schemas\Iam\Event\AppCreatedV1;
use Acme\Schemas\Iam\Node\AndroidAppV1;
use Gdbots\Iam\CreateAppHandler;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;

final class CreateAppHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand()
    {
        $command = CreateAppV1::create();
        $node = AndroidAppV1::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db']);
        $node->set('title', 'test-android-app');

        $command->set('node', $node);
        $expectedEvent = AppCreatedV1::create();
        $expectedId = $node->get('_id');

        $handler = new CreateAppHandler();
        $handler->handleCommand($command, $this->pbjx);

        $this->eventStore->pipeAllEvents(function (Event $event, StreamId $streamId) use ($expectedEvent, $expectedId) {
            $this->assertSame($event::schema(), $expectedEvent::schema());

            $node = $event->get('node');
            $this->assertSame(NodeStatus::PUBLISHED(), $node->get('status'));
            $this->assertSame('test-android-app', $node->get('title'));
            $this->assertSame(StreamId::fromString("app.history:{$expectedId}")->toString(), $streamId->toString());
            $this->assertSame($event->generateMessageRef()->toString(), (string)$node->get('last_event_ref'));
        });
    }
}
