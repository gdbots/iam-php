<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam\Validator;

use Acme\Schemas\Iam\Command\CreateUserV1;
use Acme\Schemas\Iam\Event\UserCreatedV1;
use Acme\Schemas\Iam\Node\UserV1;
use Acme\Schemas\Iam\Request\GetUserRequestV1;
use Gdbots\Iam\GetUserRequestHandler;
use Gdbots\Iam\Validator\UniqueUserValidator;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Schemas\Pbjx\StreamId;
use Gdbots\Tests\Iam\AbstractPbjxTest;

final class UniqueUserValidatorTest extends AbstractPbjxTest
{
    public function setup()
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
        $command = CreateUserV1::create();
        $node = UserV1::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db', 'email' => 'homer@simpson.com']);
        $command->set('node', $node);

        $validator = new UniqueUserValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreateUser($pbjxEvent);

        // if it gets here it's a pass
        $this->assertTrue(true);
    }

    /**
     * @expectedException \Gdbots\Iam\Exception\UserAlreadyExists
     */
    public function testValidateCreateUserThatDoesExistByEmail(): void
    {
        $command = CreateUserV1::create();
        $existingNode = UserV1::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db', 'email' => 'homer@simpson.com']);
        $newNode = UserV1::fromArray(['_id' => '8466a862-2a53-43a4-ade2-25b63e0cab94', 'email' => 'homer@simpson.com']);
        $this->ncr->putNode($existingNode);
        $command->set('node', $newNode);

        $validator = new UniqueUserValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreateUser($pbjxEvent);
    }

    /**
     * @expectedException \Gdbots\Iam\Exception\UserAlreadyExists
     */
    public function testValidateCreateUserThatDoesExistById(): void
    {
        $command = CreateUserV1::create();
        $event = UserCreatedV1::create();
        $node = UserV1::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db', 'email' => 'homer@simpson.com']);
        $command->set('node', $node);
        $event->set('node', $node);
        $this->eventStore->putEvents(StreamId::fromString("user.history:{$node->get('_id')}"), [$event]);

        $validator = new UniqueUserValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreateUser($pbjxEvent);
    }
}
