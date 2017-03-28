<?php
declare(strict_types = 1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Event\RoleCreatedV1;
use Acme\Schemas\Iam\Event\RoleDeletedV1;
use Acme\Schemas\Iam\Event\RoleUpdatedV1;
use Acme\Schemas\Iam\Node\RoleV1;
use Gdbots\Iam\RoleProjector;
use Gdbots\Schemas\Iam\RoleId;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\NodeRef;

class RoleProjectorTest extends AbstractPbjxTest
{
    /** @var RoleProjector */
    protected $roleProjecter;

    public function setup()
    {
        parent::setup();

        $this->roleProjecter = new RoleProjector($this->ncr);
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->roleProjecter = null;
    }

    public function testOnRoleCreated()
    {
        $role = $this->createRoleForTest('super-user');
        $event = RoleCreatedV1::create()->set('node', $role);

        $this->roleProjecter->onRoleCreated($event, $this->pbjx);
        $getRole = $this->ncr->getNode(NodeRef::fromString('acme:role:super-user'));

        $this->assertTrue($role->equals($getRole));
    }

    public function testOnRoleUpdated()
    {
        $oldRole = $this->createRoleForTest('super-user');
        $nodeRef = NodeRef::fromNode($oldRole);
        $this->ncr->putNode($oldRole, null);

        $newRole = $this->createRoleForTest('super-user')
            ->addToSet('allowed', ['acme:user:command:*'])
            ->addToSet('denied', ['acme:video:command:*', 'acme:plugin:command:*']);

        $newRole->set('etag', $newRole->generateEtag(['etag', 'updated_at']));

        $event = RoleUpdatedV1::create()
            ->set('old_node', $oldRole)
            ->set('new_node', $newRole)
            ->set('old_etag', $oldRole->get('etag'))
            ->set('new_etag', $newRole->get('etag'))
            ->set('node_ref', $nodeRef);

        $this->roleProjecter->onRoleUpdated($event, $this->pbjx);
        $getRole = $this->ncr->getNode($nodeRef);

        $this->assertSame($newRole->get('allowed'), $getRole->get('allowed'));
        $this->assertSame($newRole->get('denied'), $getRole->get('denied'));
        $this->assertSame($event->get('new_etag'), $getRole->get('etag'));
    }

    public function testOnRoleDeleted()
    {
        $role = $this->createRoleForTest('test-user');
        $nodeRef = NodeRef::fromNode($role);
        $this->ncr->putNode($role, null);

        $event = RoleDeletedV1::create()
            ->set('node_ref', $nodeRef);

        $this->roleProjecter->onRoleDeleted($event, $this->pbjx);

        $deletedRole = $this->ncr->getNode($nodeRef);
        $this->assertEquals(NodeStatus::DELETED(), $deletedRole->get('status'));
    }

    /**
     * @param string $id
     * @return RoleV1
     */
    private function createRoleForTest(string $id): RoleV1
    {
        return RoleV1::fromArray(['_id' => RoleId::fromString($id)]);
    }
}
