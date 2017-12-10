<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Command\CreateRoleV1;
use Acme\Schemas\Iam\Event\RoleCreatedV1;
use Acme\Schemas\Iam\Node\RoleV1;
use Gdbots\Iam\CreateRoleHandler;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;

final class CreateRoleHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand()
    {
        $command = CreateRoleV1::create();
        $node = RoleV1::fromArray(['_id' => 'super-user']);
        $node
            ->addToSet('allowed', ['acme:blog:command:*', 'acme:video:command:*'])
            ->addToSet('denied', ['acme:blog:command:publish-article']);

        $command->set('node', $node);
        $expectedEvent = RoleCreatedV1::create();
        $expectedId = $node->get('_id');

        $handler = new CreateRoleHandler();
        $handler->handleCommand($command, $this->pbjx);

        $this->eventStore->pipeAllEvents(function (Event $event, StreamId $streamId) use ($expectedEvent, $expectedId) {
            $this->assertSame($event::schema(), $expectedEvent::schema());

            $node = $event->get('node');
            $this->assertSame(NodeStatus::PUBLISHED(), $node->get('status'));
            $this->assertSame(['acme:blog:command:*', 'acme:video:command:*'], $node->get('allowed'));
            $this->assertSame(['acme:blog:command:publish-article'], $node->get('denied'));
            $this->assertSame(StreamId::fromString("role.history:{$expectedId}")->toString(), $streamId->toString());
            $this->assertSame($event->generateMessageRef()->toString(), (string)$node->get('last_event_ref'));
        });
    }
}
