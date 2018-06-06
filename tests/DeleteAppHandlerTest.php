<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Command\DeleteAppV1;
use Acme\Schemas\Iam\Event\AppDeletedV1;
use Acme\Schemas\Iam\Node\IosAppV1;
use Gdbots\Iam\DeleteAppHandler;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;

final class DeleteAppHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand()
    {
        $node = IosAppV1::fromArray(['_id' => '8695f644-0e7f-11e7-93ae-92361f002671']);
        $this->ncr->putNode($node);
        $nodeRef = NodeRef::fromNode($node);
        $command = DeleteAppV1::create();
        $command->set('node_ref', $nodeRef);

        $expectedEvent = AppDeletedV1::create();
        $expectedId = $nodeRef->getId();

        $handler = new DeleteAppHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        $this->eventStore->pipeAllEvents(function (Event $event, StreamId $streamId) use ($expectedEvent, $expectedId) {
            $this->assertSame($event::schema(), $expectedEvent::schema());
            $this->assertSame(StreamId::fromString("ios-app.history:{$expectedId}")->toString(), $streamId->toString());
        });
    }
}
