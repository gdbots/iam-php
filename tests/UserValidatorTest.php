<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Node\UserV1;
use Gdbots\Iam\Exception\UserAlreadyExists;
use Gdbots\Iam\GetUserRequestHandler;
use Gdbots\Iam\UserValidator;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Schemas\Iam\Request\GetUserRequestV1;
use Gdbots\Schemas\Ncr\Command\CreateNodeV1;

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

    public function testValidateCreateUserThatDoesExistByEmail(): void
    {
        $this->expectException(UserAlreadyExists::class);
        $command = CreateNodeV1::create();
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
        $validator->validate($pbjxEvent);
    }

    public function testValidateCreateUserThatDoesNotExistByEmail(): void
    {
        $command = CreateNodeV1::create();
        $newNode = UserV1::fromArray([
            '_id'   => '8466a862-2a53-43a4-ade2-25b63e0cab94',
            'email' => 'homer@simpson.com',
        ]);
        $command->set('node', $newNode);

        $validator = new UserValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validate($pbjxEvent);
        $validator->validate($pbjxEvent->createChildEvent($newNode));

        // if it gets here it's a pass
        $this->assertTrue(true);
    }
}
