<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Node\RoleV1;
use Gdbots\Ncr\Exception\NodeAlreadyExists;
use Gdbots\Ncr\GetNodeRequestHandler;
use Gdbots\Ncr\UniqueNodeValidator;
use Gdbots\Pbj\Exception\AssertionFailed;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Schemas\Iam\RoleId;
use Gdbots\Schemas\Ncr\Command\CreateNodeV1;
use Gdbots\Schemas\Ncr\Command\UpdateNodeV1;
use Gdbots\Schemas\Ncr\Event\NodeCreatedV1;
use Gdbots\Schemas\Ncr\Request\GetNodeRequestV1;
use Gdbots\Schemas\Pbjx\StreamId;

final class UniqueRoleValidatorTest extends AbstractPbjxTest
{
    public function setUp(): void
    {
        parent::setUp();

        // prepare request handlers that this test case requires
        PbjxEvent::setPbjx($this->pbjx);
        $this->locator->registerRequestHandler(
            GetNodeRequestV1::schema()->getCurie(),
            new GetNodeRequestHandler($this->ncr)
        );
    }

    public function testValidateCreateRole(): void
    {
        $command = CreateNodeV1::create();
        $node = RoleV1::fromArray(['_id' => RoleId::fromString('super-user')]);
        $command->set('node', $node);

        $validator = new UniqueNodeValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreateNode($pbjxEvent);

        // if it gets here it's a pass
        $this->assertTrue(true);
    }

    public function testValidateCreateRoleThatDoesExistById(): void
    {
        $this->expectException(NodeAlreadyExists::class);
        $command = CreateNodeV1::create();
        $event = NodeCreatedV1::create();
        $node = RoleV1::fromArray(['_id' => RoleId::fromString('super-user')]);
        $command->set('node', $node);
        $event->set('node', $node);
        $this->eventStore->putEvents(StreamId::fromString("acme:role:{$node->get('_id')}"), [$event]);

        $validator = new UniqueNodeValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateCreateNode($pbjxEvent);
    }

    public function testValidateUpdateRole(): void
    {
        $command = UpdateNodeV1::create();
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

    public function testValidateUpdateRoleWithoutNewNode(): void
    {
        $this->expectException(AssertionFailed::class);
        $command = UpdateNodeV1::create();
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
