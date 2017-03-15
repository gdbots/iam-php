<?php
declare(strict_types = 1);

namespace Gdbots\Tests\Iam\Validator;

use Gdbots\Iam\GetUserRequestHandler;
use Gdbots\Iam\Validator\UniqueUserValidator;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Tests\Iam\AbstractPbjxTest;
use Gdbots\Tests\Iam\Fixtures\Command\CreateUser;
use Gdbots\Tests\Iam\Fixtures\Node\User;
use Gdbots\Tests\Iam\Fixtures\Request\GetUserRequest;
use Gdbots\Tests\Iam\Fixtures\Request\GetUserResponse;

class UniqueUserValidatorTest extends AbstractPbjxTest
{
    public function setup()
    {
        parent::setup();

        // ensure schemas are registered with the resolver
        GetUserRequest::schema();
        GetUserResponse::schema();

        // prepare request handlers that this test case requires
        PbjxEvent::setPbjx($this->pbjx);
        $this->locator->registerRequestHandler(
            GetUserRequest::schema()->getCurie(),
            new GetUserRequestHandler($this->ncr)
        );
    }

    public function testValidateCreateUserThatDoesNotExist()
    {
        $command = CreateUser::create();
        $node = User::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db', 'email' => 'homer@simpson.com']);
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
        $command = CreateUser::create();
        $node = User::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db', 'email' => 'homer@simpson.com']);
        $this->ncr->putNode($node);
        $command->set('node', clone $node);

        $validator = new UniqueUserValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreateUser($pbjxEvent);
    }
}
