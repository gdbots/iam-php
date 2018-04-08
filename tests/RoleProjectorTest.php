<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Event\RoleCreatedV1;
use Acme\Schemas\Iam\Event\RoleDeletedV1;
use Acme\Schemas\Iam\Event\RoleUpdatedV1;
use Acme\Schemas\Iam\Node\RoleV1;
use Gdbots\Iam\NcrRoleProjector;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Schemas\Iam\RoleId;
use Gdbots\Schemas\Ncr\NodeRef;

final class RoleProjectorTest extends AbstractPbjxTest
{
    /** @var NcrRoleProjector */
    protected $roleProjecter;

    /** @var NcrSearch|\PHPUnit_Framework_MockObject_MockObject */
    protected $ncrSearch;

    public function setup()
    {
        parent::setup();
        $this->ncrSearch = $this->getMockBuilder(MockNcrSearch::class)->getMock();
        $this->roleProjecter = new NcrRoleProjector($this->ncr, $this->ncrSearch);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->roleProjecter = null;
    }

    /**
     * testOnRoleCreated
     */
    public function testOnRoleCreated(): void
    {
        $role = $this->createRoleById('super-user');
        $event = RoleCreatedV1::create()->set('node', $role);

        $this->roleProjecter->onRoleCreated($event, $this->pbjx);
        $getRole = $this->ncr->getNode(NodeRef::fromString('acme:role:super-user'));

        $this->assertTrue($role->equals($getRole));
    }

    /**
     * testOnRoleCreatedIsReplay
     */
    public function testOnRoleCreatedIsReplay(): void
    {
        $role = $this->createRoleById('super-user');
        $event = RoleCreatedV1::create()->set('node', $role);
        $event->isReplay(true);

        $this->roleProjecter->onRoleCreated($event, $this->pbjx);

        $actualRole = $this->ncr->getNode(NodeRef::fromString('acme:role:super-user'));
        $this->assertTrue($role->equals($actualRole));
    }

    /**
     * testOnRoleUpdated
     */
    public function testOnRoleUpdated(): void
    {
        $oldRole = $this->createRoleById('super-user');
        $nodeRef = NodeRef::fromNode($oldRole);
        $this->ncr->putNode($oldRole, null);

        $newRole = $this->createRoleById('super-user')
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

        $actualRole = $this->ncr->getNode($nodeRef);
        $this->assertTrue($newRole->equals($actualRole));
    }

    /**
     * @expectedException \Gdbots\Ncr\Exception\NodeNotFound
     */
    public function testOnRoleDeleted(): void
    {
        $role = $this->createRoleById('test-user');
        $nodeRef = NodeRef::fromNode($role);
        $this->ncr->putNode($role);

        $event = RoleDeletedV1::create()->set('node_ref', $nodeRef);
        $this->roleProjecter->onRoleDeleted($event, $this->pbjx);

        $this->ncr->getNode($nodeRef);
    }

    public function testOnRoleDeletedNodeRefNotExists(): void
    {
        $nodeRef = NodeRef::fromString('acme:role:role-not-exists');
        $event = RoleDeletedV1::create()->set('node_ref', $nodeRef);
        $this->roleProjecter->onRoleDeleted($event, $this->pbjx);
        $this->assertFalse($this->ncr->hasNode($nodeRef));
    }

    /**
     * @param string $id
     *
     * @return RoleV1
     */
    private function createRoleById(string $id): RoleV1
    {
        return RoleV1::fromArray(['_id' => RoleId::fromString($id)]);
    }
}
