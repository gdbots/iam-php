<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Node\IosAppV1;
use Gdbots\Iam\AppAggregate;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Iam\Command\GrantRolesToAppV1;
use Gdbots\Schemas\Iam\Event\AppRolesGrantedV1;
use Gdbots\Schemas\Iam\Event\AppRolesRevokedV1;

final class AppAggregateTest extends AbstractPbjxTest
{
    public function testGrantRolesToApp()
    {
        $node = IosAppV1::fromArray([
            '_id'   => '8695f644-0e7f-11e7-93ae-92361f002671',
            'roles' => [
                'acme:role:admin',
            ],
        ]);
        $aggregate = AppAggregate::fromNode($node, $this->pbjx);
        $command = GrantRolesToAppV1::create()
            ->set(GrantRolesToAppV1::NODE_REF_FIELD, $node->generateNodeRef())
            ->addToSet(GrantRolesToAppV1::ROLES_FIELD, [
                NodeRef::fromString('acme:role:admin'),
                NodeRef::fromString('acme:role:tester'),
            ]);
        $aggregate->grantRolesToApp($command);
        $this->assertTrue($aggregate->hasUncommittedEvents());
        $this->assertCount(1, $aggregate->getUncommittedEvents());

        $expectedEvent = AppRolesGrantedV1::SCHEMA_CURIE;
        $actualEvent = $aggregate->getUncommittedEvents()[0];
        $this->assertEquals($expectedEvent, $actualEvent::SCHEMA_CURIE);

        $expectedRoles = [NodeRef::fromString('acme:role:tester')];
        $actualRoles = $actualEvent->get(AppRolesGrantedV1::ROLES_FIELD);
        $this->assertEquals($expectedRoles, $actualRoles);

        $expectedRoles = [
            NodeRef::fromString('acme:role:admin'),
            NodeRef::fromString('acme:role:tester'),
        ];
        $actualRoles = $aggregate->getNode()->get(IosAppV1::ROLES_FIELD);
        $this->assertEquals($expectedRoles, $actualRoles);
    }

    public function testRevokeRolesFromApp()
    {
        $node = IosAppV1::fromArray([
            '_id'   => '8695f644-0e7f-11e7-93ae-92361f002671',
            'roles' => [
                'acme:role:admin',
                'acme:role:tester',
            ],
        ]);
        $aggregate = AppAggregate::fromNode($node, $this->pbjx);
        $command = GrantRolesToAppV1::create()
            ->set(GrantRolesToAppV1::NODE_REF_FIELD, $node->generateNodeRef())
            ->addToSet(GrantRolesToAppV1::ROLES_FIELD, [
                NodeRef::fromString('acme:role:admin'),
            ]);
        $aggregate->revokeRolesFromApp($command);
        $this->assertTrue($aggregate->hasUncommittedEvents());
        $this->assertCount(1, $aggregate->getUncommittedEvents());

        $expectedEvent = AppRolesRevokedV1::SCHEMA_CURIE;
        $actualEvent = $aggregate->getUncommittedEvents()[0];
        $this->assertEquals($expectedEvent, $actualEvent::SCHEMA_CURIE);

        $expectedRoles = [NodeRef::fromString('acme:role:admin')];
        $actualRoles = $actualEvent->get(AppRolesRevokedV1::ROLES_FIELD);
        $this->assertEquals($expectedRoles, $actualRoles);

        $expectedRoles = [
            NodeRef::fromString('acme:role:tester'),
        ];
        $actualRoles = $aggregate->getNode()->get(IosAppV1::ROLES_FIELD);
        $this->assertEquals($expectedRoles, $actualRoles);
    }
}
