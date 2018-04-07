<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam\Validator;

use Acme\Schemas\Iam\Command\CreateRoleV1;
use Acme\Schemas\Iam\Command\UpdateRoleV1;
use Acme\Schemas\Iam\Event\RoleCreatedV1;
use Acme\Schemas\Iam\Node\RoleV1;
use Acme\Schemas\Iam\Request\GetRoleRequestV1;
use Gdbots\Iam\GetRoleRequestHandler;
use Gdbots\Ncr\Validator\UniqueNodeValidator;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Schemas\Iam\RoleId;
use Gdbots\Schemas\Pbjx\StreamId;
use Gdbots\Tests\Iam\AbstractPbjxTest;

final class UniqueRoleValidatorTest extends AbstractPbjxTest
{
    public function setup()
    {
        parent::setup();

        // prepare request handlers that this test case requires
        PbjxEvent::setPbjx($this->pbjx);
        $this->locator->registerRequestHandler(
            GetRoleRequestV1::schema()->getCurie(),
            new GetRoleRequestHandler($this->ncr)
        );
    }

    public function testValidateCreateRole(): void
    {
        $command = CreateRoleV1::create();
        $node = RoleV1::fromArray(['_id' => RoleId::fromString('super-user')]);
        $command->set('node', $node);

        $validator = new UniqueNodeValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreateNode($pbjxEvent);

        // if it gets here it's a pass
        $this->assertTrue(true);
    }

    /**
     * @expectedException \Gdbots\Ncr\Exception\NodeAlreadyExists
     */
    public function testValidateCreateRoleThatDoesExistById(): void
    {
        $command = CreateRoleV1::create();
        $event = RoleCreatedV1::create();
        $node = RoleV1::fromArray(['_id' => RoleId::fromString('super-user')]);
        $command->set('node', $node);
        $event->set('node', $node);
        $this->eventStore->putEvents(StreamId::fromString("role.history:{$node->get('_id')}"), [$event]);

        $validator = new UniqueNodeValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreateNode($pbjxEvent);
    }

    public function testValidateUpdateRole(): void
    {
        $command = UpdateRoleV1::create();
        $oldNode = RoleV1::fromArray([
            '_id'     => RoleId::fromString('super-user'),
            'allowed' => ['acme:*'],
            'denied'  => [],
        ]);

        $newNode = RoleV1::fromArray([
            '_id'     => RoleId::fromString('super-user'),
            'allowed' => [],
            'denied'  => ['acme:*'],
        ]);

        $command->set('old_node', $oldNode);
        $command->set('new_node', $newNode);

        $validator = new UniqueNodeValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateUpdateNode($pbjxEvent);

        // if it gets here it's a pass
        $this->assertTrue(true);
    }

    /**
     * @expectedException \Gdbots\Pbj\Exception\AssertionFailed
     */
    public function testValidateUpdateRoleWithoutNewNode(): void
    {
        $command = UpdateRoleV1::create();
        $oldNode = RoleV1::fromArray([
            '_id'     => RoleId::fromString('super-user'),
            'allowed' => ['acme:*'],
            'denied'  => [],
        ]);

        $command->set('old_node', $oldNode);

        $validator = new UniqueNodeValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateUpdateNode($pbjxEvent);
    }
}
