<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Node\UserV1;
use Gdbots\Iam\GetUserRequestHandler;
use Gdbots\Ncr\Exception\NodeAlreadyExists;
use Gdbots\Ncr\UniqueNodeValidator;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Schemas\Iam\Request\GetUserRequestV1;
use Gdbots\Schemas\Ncr\Command\CreateNodeV1;
use Gdbots\Schemas\Ncr\Event\NodeCreatedV1;
use Gdbots\Schemas\Pbjx\StreamId;

final class UniqueUserValidatorTest extends AbstractPbjxTest
{
    public function setUp(): void
    {
        parent::setup();

        // prepare request handlers that this test case requires
        PbjxEvent::setPbjx($this->pbjx);
        $this->locator->registerRequestHandler(
            GetUserRequestV1::schema()->getCurie(),
            new GetUserRequestHandler($this->ncr)
        );
    }

    public function testValidateCreateUserThatDoesNotExist(): void
    {
        $command = CreateNodeV1::create();
        $node = UserV1::fromArray([
            '_id'   => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'email' => 'homer@simpson.com',
        ]);
        $command->set('node', $node);

        $validator = new UniqueNodeValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreateNode($pbjxEvent);

        // if it gets here it's a pass
        $this->assertTrue(true);
    }

    public function testValidateCreateUserThatDoesExistById(): void
    {
        $this->expectException(NodeAlreadyExists::class);
        $command = CreateNodeV1::create();
        $event = NodeCreatedV1::create();
        $node = UserV1::fromArray([
            '_id'   => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'email' => 'homer@simpson.com',
        ]);
        $command->set('node', $node);
        $event->set('node', $node);
        $this->eventStore->putEvents(StreamId::fromString("acme:user:{$node->get('_id')}"), [$event]);

        $validator = new UniqueNodeValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreateNode($pbjxEvent);
    }
}
