<?php
declare(strict_types = 1);

namespace Gdbots\Tests\Iam\Validator;

use Acme\Schemas\Iam\Command\CreateUserV1;
use Acme\Schemas\Iam\Node\UserV1;
use Acme\Schemas\Iam\Request\GetUserRequestV1;
use Gdbots\Iam\GetUserRequestHandler;
use Gdbots\Iam\Validator\UniqueUserValidator;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Tests\Iam\AbstractPbjxTest;

class UniqueUserValidatorTest extends AbstractPbjxTest
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

    public function testValidateCreateUserThatDoesNotExist()
    {
        $command = CreateUserV1::create();
        $node = UserV1::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db', 'email' => 'homer@simpson.com']);
        $command->set('node', $node);

        $validator = new UniqueUserValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreateUser($pbjxEvent);
    }

    /**
     * expectedException \Gdbots\Iam\Exception\UserAlreadyExists
     */
    public function testValidateCreateUserThatDoesExist()
    {
        $command = CreateUserV1::create();
        $node = UserV1::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db', 'email' => 'homer@simpson.com']);
        $this->ncr->putNode($node);
        $command->set('node', clone $node);

        $validator = new UniqueUserValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreateUser($pbjxEvent);
    }
}
