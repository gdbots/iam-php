<?php
declare(strict_types = 1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Event\UserCreatedV1;
use Acme\Schemas\Iam\Event\UserDeletedV1;
use Acme\Schemas\Iam\Event\UserRolesGrantedV1;
use Acme\Schemas\Iam\Event\UserRolesRevokedV1;
use Acme\Schemas\Iam\Event\UserUpdatedV1;
use Acme\Schemas\Iam\Node\RoleV1;
use Acme\Schemas\Iam\Node\UserV1;
use Gdbots\Iam\UserProjector;
use Gdbots\Schemas\Iam\RoleId;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\NodeRef;

class UserProjectorTest extends AbstractPbjxTest
{
    /** @var UserProjector */
    protected $userProjector;

    protected $ncrSearch;

    public function setup()
    {
        parent::setup();

        $this->ncrSearch = $this->getMockBuilder('Gdbots\Tests\Iam\TestUtils\DummyNcrSearch')->getMock();

        $this->userProjector = new UserProjector($this->ncr, $this->ncrSearch);
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->userProjector = null;
    }

    /**
     * testOnUserCreated
     */
    public function testOnUserCreated(): void
    {
        $user = $this->createuserById('7afcc2f1-9654-46d1-8fc1-b0511df257db');
        $nodeRef = NodeRef::fromNode($user);
        $event = UserCreatedV1::create()->set('node', $user);

        $this->ncrSearch->expects($this->once())->method('indexNodes');

        $this->userProjector->onUserCreated($event, $this->pbjx);
        $getUser = $this->ncr->getNode($nodeRef);

        $this->assertTrue($user->equals($getUser));
    }

    /**
     * testOnUserCreatedIsReplay
     */
    public function testOnUserCreatedIsReplay(): void
    {
        $user = $this->createuserById('7afcc2f1-9654-46d1-8fc1-b0511df257db');
        $nodeRef = NodeRef::fromNode($user);
        $event = UserCreatedV1::create()->set('node', $user);
        $event->isReplay(true);

        $this->ncrSearch->expects($this->never())->method('indexNodes');

        $this->userProjector->onUserCreated($event, $this->pbjx);
        $getUser = $this->ncr->getNode($nodeRef);

        $this->assertTrue($user->equals($getUser));
    }

    /**
     *
     */
    public function testOnUserUpdated(): void
    {
        $oldUser = $this->createUserById('7afcc2f1-9654-46d1-8fc1-b0511df257db');
        $nodeRef = NodeRef::fromNode($oldUser);
        $this->ncr->putNode($oldUser, null);

        $newUser = $this->createUserById('7afcc2f1-9654-46d1-8fc1-b0511df257db')->set('title', 'A new User');
        $newUser->set('etag', $newUser->generateEtag(['etag', 'updated_at']));

        $event = UserUpdatedV1::create()
            ->set('old_node', $oldUser)
            ->set('new_node', $newUser)
            ->set('old_etag', $oldUser->get('etag'))
            ->set('new_etag', $newUser->get('etag'))
            ->set('node_ref', $nodeRef);

        $this->ncrSearch->expects($this->once())->method('indexNodes');

        $this->userProjector->onUserUpdated($event, $this->pbjx);
        $getUser = $this->ncr->getNode($nodeRef);

        $this->assertEquals($newUser->get('title'), $getUser->get('title'));
        $this->assertSame($event->get('new_etag'), $getUser->get('etag'));
    }

    /**
     *
     */
    public function testOnUserUpdatedIsReplay(): void
    {
        $oldUser = $this->createUserById('7afcc2f1-9654-46d1-8fc1-b0511df257db');
        $nodeRef = NodeRef::fromNode($oldUser);
        $this->ncr->putNode($oldUser, null);

        $newUser = $this->createUserById('7afcc2f1-9654-46d1-8fc1-b0511df257db')
            ->set('title', 'A new User');
        $newUser->set('etag', $newUser->generateEtag(['etag', 'updated_at']));

        $event = UserUpdatedV1::create()
            ->set('old_node', $oldUser)
            ->set('new_node', $newUser)
            ->set('old_etag', $oldUser->get('etag'))
            ->set('new_etag', $newUser->get('etag'))
            ->set('node_ref', $nodeRef);
        $event->isReplay(true);

        $this->ncrSearch->expects($this->never())->method('indexNodes');

        $this->userProjector->onUserUpdated($event, $this->pbjx);
        $getUser = $this->ncr->getNode($nodeRef);

        $this->assertEquals($newUser->get('title'), $getUser->get('title'));
        $this->assertSame($event->get('new_etag'), $getUser->get('etag'));
    }

    /**
     *
     */
    public function testOnUserDeleted(): void
    {
        $user = $this->createUserById('7afcc2f1-9654-46d1-8fc1-b0511df257db');
        $nodeRef = NodeRef::fromNode($user);
        $this->ncr->putNode($user, null);

        $event = UserDeletedV1::create()->set('node_ref', $nodeRef);

        $this->userProjector->onUserDeleted($event, $this->pbjx);

        $deleteduser = $this->ncr->getNode($nodeRef);
        $this->assertEquals(NodeStatus::DELETED(), $deleteduser->get('status'));
    }

    /**
     *
     */
    public function testOnUserRolesGranted(): void
    {
        $user = $this->createUserById('7afcc2f1-9654-46d1-8fc1-b0511df257db');
        $nodeRef = NodeRef::fromNode($user);
        $this->ncr->putNode($user, null);

        $role = $this->createRoleById('super-user');

        $event = UserRolesGrantedV1::create()
            ->set('node_ref', $nodeRef)
            ->addToSet('roles', [NodeRef::fromNode($role)]);

        $this->userProjector->onUserRolesGranted($event, $this->pbjx);

        $user = $this->ncr->getNode($nodeRef);

        $this->assertEquals(1, count($user->get('roles')));
        $this->assertEquals(NodeRef::fromNode($role), $user->get('roles')[0]);
    }

    /**
     * testOnUserRolesRevoked
     */
    public function testOnUserRolesRevoked(): void
    {
        $superRole = $this->createRoleById('super-user');
        $testRole = $this->createRoleById('test-user');

        $user = $this->createUserById('7afcc2f1-9654-46d1-8fc1-b0511df257db')
            ->addToSet('roles', [NodeRef::fromNode($superRole), NodeRef::fromNode($testRole)]);
        $nodeRef = NodeRef::fromNode($user);
        $this->ncr->putNode($user, null);

        $oldUser = $this->ncr->getNode($nodeRef);
        $oldUserRoles = array_flip(array_map('strval', $oldUser->get('roles')));
        // comfirm the old roles are there before revoke
        $this->assertArrayHasKey(NodeRef::fromNode($superRole)->toString(), $oldUserRoles);
        $this->assertArrayHasKey(NodeRef::fromNode($testRole)->toString(), $oldUserRoles);

        $event = UserRolesRevokedV1::create()
            ->set('node_ref', $nodeRef)
            ->addToSet('roles', [NodeRef::fromNode($superRole)]);

        $this->userProjector->onUserRolesRevoked($event, $this->pbjx);
        $updatedUser = $this->ncr->getNode($nodeRef);
        $updatedUserRoles = array_flip(array_map('strval', $updatedUser->get('roles')));
        // comfirm the old roles are updated after revoke some of those
        $this->assertArrayNotHasKey(NodeRef::fromNode($superRole)->toString(), $updatedUserRoles);
        $this->assertArrayHasKey(NodeRef::fromNode($testRole)->toString(), $updatedUserRoles);
    }

    /**
     * @param string $id
     * @return UserV1
     */
    private function createUserById(string $id): UserV1
    {
        return UserV1::fromArray(['_id' => $id]);
    }

    /**
     * @param string $id
     * @return RoleV1
     */
    private function createRoleById(string $id): RoleV1
    {
        return RoleV1::fromArray(['_id' => RoleId::fromString($id)]);
    }
}