<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam\Validator;

use Acme\Schemas\Iam\Command\CreateUserV1;
use Acme\Schemas\Iam\Node\UserV1;
use Acme\Schemas\Iam\Request\GetUserRequestV1;
use Gdbots\Iam\GetUserRequestHandler;
use Gdbots\Iam\Validator\UserValidator;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Tests\Iam\AbstractPbjxTest;

final class UserValidatorTest extends AbstractPbjxTest
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

    /**
     * @expectedException \Gdbots\Iam\Exception\UserAlreadyExists
     */
    public function testValidateCreateUserThatDoesExistByEmail(): void
    {
        $command = CreateUserV1::create();
        $existingNode = UserV1::fromArray([
            '_id'    => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'email'  => 'homer@simpson.com',
            'status' => 'published',
        ]);
        $newNode = UserV1::fromArray([
            '_id'   => '8466a862-2a53-43a4-ade2-25b63e0cab94',
            'email' => 'homer@simpson.com',
        ]);
        $this->ncr->putNode($existingNode);
        $command->set('node', $newNode);

        $validator = new UserValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreateUser($pbjxEvent);
    }

    public function testValidateCreateUserThatDoesNotExistByEmail(): void
    {
        $command = CreateUserV1::create();
        $newNode = UserV1::fromArray([
            '_id'   => '8466a862-2a53-43a4-ade2-25b63e0cab94',
            'email' => 'homer@simpson.com',
        ]);
        $command->set('node', $newNode);

        $validator = new UserValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreateUser($pbjxEvent);

        // if it gets here it's a pass
        $this->assertTrue(true);
    }
}
