<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Command\UpdateRoleV1;
use Acme\Schemas\Iam\Event\RoleUpdatedV1;
use Acme\Schemas\Iam\Node\RoleV1;
use Gdbots\Iam\UpdateRoleHandler;
use Gdbots\Schemas\Iam\RoleId;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;

final class UpdateRoleHandlerTest extends AbstractPbjxTest
{
    public function testUpdateRole(): void
    {
        $command = UpdateRoleV1::create();

        $oldNode = RoleV1::create()
            ->set('_id', RoleId::fromString('super-user'))
            ->addToSet('allowed', ['acme:blog:command:*', 'acme:video:command:*'])
            ->addToSet('denied', ['acme:blog:command:publish-article'])
            ->set('status', NodeStatus::PUBLISHED());
        $this->ncr->putNode($oldNode);

        $newNode = RoleV1::create()
            ->set('_id', RoleId::fromString('super-user'))
            ->addToSet('allowed', ['acme:video:command:*'])
            ->addToSet('denied', ['acme:blog:command:*']);

        $command->set('node_ref', NodeRef::fromNode($oldNode));
        $command->set('old_node', $oldNode);
        $command->set('new_node', $newNode);

        $handler = new UpdateRoleHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        $expectedEvent = RoleUpdatedV1::create();
        $expectedId = $oldNode->get('_id');

        $this->eventStore->pipeAllEvents(function (Event $event, StreamId $streamId) use ($expectedEvent, $expectedId) {
            $this->assertSame($event::schema(), $expectedEvent::schema());
            $this->assertTrue($event->has('old_node'));
            $this->assertTrue($event->has('new_node'));

            $newNode = $event->get('new_node');

            $this->assertSame(NodeStatus::PUBLISHED(), $newNode->get('status'));
            $this->assertSame(['acme:video:command:*'], $newNode->get('allowed'));
            $this->assertSame(['acme:blog:command:*'], $newNode->get('denied'));
            $this->assertSame(StreamId::fromString("role.history:{$expectedId}")->toString(), $streamId->toString());
            $this->assertSame($event->generateMessageRef()->toString(), (string)$newNode->get('last_event_ref'));
        });
    }

    public function testUpdateRoleWithoutOldNode()
    {
        $command = UpdateRoleV1::create();

        $oldNode = RoleV1::create()
            ->set('_id', RoleId::fromString('super-user'))
            ->set('status', NodeStatus::PUBLISHED());
        $this->ncr->putNode($oldNode);

        $newNode = RoleV1::create()
            ->set('_id', RoleId::fromString('super-user'))
            ->addToSet('allowed', ['acme:video:command:*'])
            ->addToSet('denied', ['acme:blog:command:*']);

        $command->set('node_ref', NodeRef::fromNode($oldNode));
        $command->set('new_node', $newNode);

        $handler = new UpdateRoleHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        $expectedEvent = RoleUpdatedV1::create();
        $expectedId = $newNode->get('_id');

        $this->eventStore->pipeAllEvents(function (Event $event, StreamId $streamId) use ($expectedEvent, $expectedId) {
            $this->assertSame($event::schema(), $expectedEvent::schema());
            $this->assertTrue($event->has('old_node'));
            $this->assertTrue($event->has('new_node'));

            $newNode = $event->get('new_node');

            $this->assertSame(NodeStatus::PUBLISHED(), $newNode->get('status'));
            $this->assertSame(['acme:video:command:*'], $newNode->get('allowed'));
            $this->assertSame(['acme:blog:command:*'], $newNode->get('denied'));
            $this->assertSame(StreamId::fromString("role.history:{$expectedId}")->toString(), $streamId->toString());
            $this->assertSame($event->generateMessageRef()->toString(), (string)$newNode->get('last_event_ref'));
        });
    }

    public function testUpdateRoleWithNewNode()
    {
        $command = UpdateRoleV1::create();

        $oldNode = RoleV1::create()
            ->set('_id', RoleId::fromString('super-user'))
            ->addToSet('allowed', ['acme:blog:command:*', 'acme:video:command:*'])
            ->addToSet('denied', ['acme:blog:command:publish-article'])
            ->set('status', NodeStatus::PUBLISHED());
        $this->ncr->putNode($oldNode);

        $newNode = RoleV1::create()
            ->set('_id', RoleId::fromString('super-user'));

        $command->set('node_ref', NodeRef::fromNode($oldNode));
        $command->set('old_node', $oldNode);
        $command->set('new_node', $newNode);

        $handler = new UpdateRoleHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        $expectedEvent = RoleUpdatedV1::create();
        $expectedId = $oldNode->get('_id');

        $this->eventStore->pipeAllEvents(function (Event $event, StreamId $streamId) use ($expectedEvent, $expectedId) {
            $this->assertSame($event::schema(), $expectedEvent::schema());
            $this->assertTrue($event->has('old_node'));
            $this->assertTrue($event->has('new_node'));

            $newNode = $event->get('new_node');

            $this->assertSame(NodeStatus::PUBLISHED(), $newNode->get('status'));
            $this->assertNull($newNode->get('allowed'));
            $this->assertNull($newNode->get('denied'));
            $this->assertSame(StreamId::fromString("role.history:{$expectedId}")->toString(), $streamId->toString());
            $this->assertSame($event->generateMessageRef()->toString(), (string)$newNode->get('last_event_ref'));
        });
    }
}
