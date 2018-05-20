<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Event\AppCreatedV1;
use Acme\Schemas\Iam\Event\AppDeletedV1;
use Acme\Schemas\Iam\Event\AppRolesGrantedV1;
use Acme\Schemas\Iam\Event\AppRolesRevokedV1;
use Acme\Schemas\Iam\Event\AppUpdatedV1;
use Acme\Schemas\Iam\Node\RoleV1;
use Acme\Schemas\Iam\Node\BrowserAppV1;
use Gdbots\Iam\NcrAppProjector;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Schemas\Iam\RoleId;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\NodeRef;

final class AppProjectorTest extends AbstractPbjxTest
{
    /** @var NcrAppProjector */
    protected $appProjector;

    /** @var NcrSearch|\PHPUnit_Framework_MockObject_MockObject */
    protected $ncrSearch;

    public function setup()
    {
        parent::setup();
        $this->ncrSearch = $this->getMockBuilder(MockNcrSearch::class)->getMock();
        $this->appProjector = new NcrAppProjector($this->ncr, $this->ncrSearch);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->appProjector = null;
    }

    public function testOnAppCreated(): void
    {
        $app = $this->createappById('7afcc2f1-9654-46d1-8fc1-b0511df257db');
        $nodeRef = NodeRef::fromNode($app);
        $event = AppCreatedV1::create()->set('node', $app);

        $this->ncrSearch->expects($this->once())->method('indexNodes');

        $this->appProjector->onAppCreated($event, $this->pbjx);
        $getApp = $this->ncr->getNode($nodeRef);

        $this->assertTrue($app->equals($getApp));
    }

    public function testOnAppCreatedIsReplay(): void
    {
        $app = $this->createappById('7afcc2f1-9654-46d1-8fc1-b0511df257db');
        $nodeRef = NodeRef::fromNode($app);
        $event = AppCreatedV1::create()->set('node', $app);
        $event->isReplay(true);

        $this->ncrSearch->expects($this->never())->method('indexNodes');

        $this->appProjector->onAppCreated($event, $this->pbjx);
        $getApp = $this->ncr->getNode($nodeRef);

        $this->assertTrue($app->equals($getApp));
    }

    /**
     * testOnAppUpdated
     */
    public function testOnAppUpdated(): void
    {
        $oldApp = $this->createAppById('7afcc2f1-9654-46d1-8fc1-b0511df257db');
        $nodeRef = NodeRef::fromNode($oldApp);
        $this->ncr->putNode($oldApp, null);

        $newApp = $this->createAppById('7afcc2f1-9654-46d1-8fc1-b0511df257db')->set('title', 'A new App');
        $newApp->set('etag', $newApp->generateEtag(['etag', 'updated_at']));

        $event = AppUpdatedV1::create()
            ->set('old_node', $oldApp)
            ->set('new_node', $newApp)
            ->set('old_etag', $oldApp->get('etag'))
            ->set('new_etag', $newApp->get('etag'))
            ->set('node_ref', $nodeRef);

        $this->ncrSearch->expects($this->once())->method('indexNodes');

        $this->appProjector->onAppUpdated($event, $this->pbjx);
        $getApp = $this->ncr->getNode($nodeRef);

        $this->assertEquals($newApp->get('title'), $getApp->get('title'));
        $this->assertSame($event->get('new_etag'), $getApp->get('etag'));
    }

    /**
     * testOnAppUpdatedIsReplay
     */
    public function testOnAppUpdatedIsReplay(): void
    {
        $oldApp = $this->createAppById('7afcc2f1-9654-46d1-8fc1-b0511df257db');
        $nodeRef = NodeRef::fromNode($oldApp);
        $this->ncr->putNode($oldApp, null);

        $newApp = $this->createAppById('7afcc2f1-9654-46d1-8fc1-b0511df257db')
            ->set('title', 'A new App');
        $newApp->set('etag', $newApp->generateEtag(['etag', 'updated_at']));

        $event = AppUpdatedV1::create()
            ->set('old_node', $oldApp)
            ->set('new_node', $newApp)
            ->set('old_etag', $oldApp->get('etag'))
            ->set('new_etag', $newApp->get('etag'))
            ->set('node_ref', $nodeRef);
        $event->isReplay(true);

        $this->ncrSearch->expects($this->never())->method('indexNodes');

        $this->appProjector->onAppUpdated($event, $this->pbjx);
        $getApp = $this->ncr->getNode($nodeRef);

        $this->assertEquals($newApp->get('title'), $getApp->get('title'));
        $this->assertSame($event->get('new_etag'), $getApp->get('etag'));
    }

    /**
     * testOnAppDeleted
     */
    public function testOnAppDeleted(): void
    {
        $app = $this->createAppById('7afcc2f1-9654-46d1-8fc1-b0511df257db');
        $nodeRef = NodeRef::fromNode($app);
        $this->ncr->putNode($app, null);

        $event = AppDeletedV1::create()->set('node_ref', $nodeRef);

        $this->appProjector->onAppDeleted($event, $this->pbjx);

        $deletedapp = $this->ncr->getNode($nodeRef);
        $this->assertEquals(NodeStatus::DELETED(), $deletedapp->get('status'));
    }

    public function testOnAppDeletedNodeRefNotExists(): void
    {
        $nodeRef = NodeRef::fromString('acme:app:7afcc2f1-9654-46d1-8fc1-b0511df257db');
        $event = AppDeletedV1::create()->set('node_ref', $nodeRef);

        $this->appProjector->onAppDeleted($event, $this->pbjx);
        $this->assertFalse($this->ncr->hasNode($nodeRef));
    }

    /**
     * testOnAppRolesGranted
     */
    public function testOnAppRolesGranted(): void
    {
        $app = $this->createAppById('7afcc2f1-9654-46d1-8fc1-b0511df257db');
        $nodeRef = NodeRef::fromNode($app);
        $this->ncr->putNode($app, null);

        $role = $this->createRoleById('super-app');

        $event = AppRolesGrantedV1::create()
            ->set('node_ref', $nodeRef)
            ->addToSet('roles', [NodeRef::fromNode($role)]);

        $this->appProjector->onAppRolesGranted($event, $this->pbjx);

        $app = $this->ncr->getNode($nodeRef);

        $this->assertEquals(1, count($app->get('roles')));
        $this->assertEquals(NodeRef::fromNode($role), $app->get('roles')[0]);
    }

    /**
     * @expectedException \Gdbots\Ncr\Exception\NodeNotFound
     */
    public function testOnAppRolesGrantedNodeRefNotFound(): void
    {
        $role = $this->createRoleById('super-app');

        $event = AppRolesGrantedV1::create()
            ->set('node_ref', NodeRef::fromString('acme:app:7afcc2f1-9654-46d1-8fc1-b0511df257db'))
            ->addToSet('roles', [NodeRef::fromNode($role)]);

        $this->appProjector->onAppRolesGranted($event, $this->pbjx);
    }

    public function testOnAppRolesRevoked(): void
    {
        $superRole = $this->createRoleById('super-app');
        $testRole = $this->createRoleById('test-app');

        $app = $this->createAppById('7afcc2f1-9654-46d1-8fc1-b0511df257db')
            ->addToSet('roles', [NodeRef::fromNode($superRole), NodeRef::fromNode($testRole)]);
        $nodeRef = NodeRef::fromNode($app);
        $this->ncr->putNode($app, null);

        $oldApp = $this->ncr->getNode($nodeRef);
        $oldAppRoles = array_flip(array_map('strval', $oldApp->get('roles')));

        // comfirm the old roles are there before revoke
        $this->assertArrayHasKey(NodeRef::fromNode($superRole)->toString(), $oldAppRoles);
        $this->assertArrayHasKey(NodeRef::fromNode($testRole)->toString(), $oldAppRoles);

        $event = AppRolesRevokedV1::create()
            ->set('node_ref', $nodeRef)
            ->addToSet('roles', [NodeRef::fromNode($superRole)]);

        $this->appProjector->onAppRolesRevoked($event, $this->pbjx);
        $updatedApp = $this->ncr->getNode($nodeRef);
        $updatedAppRoles = array_flip(array_map('strval', $updatedApp->get('roles')));

        // comfirm the old roles are updated after revoke some of those
        $this->assertArrayNotHasKey(NodeRef::fromNode($superRole)->toString(), $updatedAppRoles);
        $this->assertArrayHasKey(NodeRef::fromNode($testRole)->toString(), $updatedAppRoles);
    }

    /**
     * @expectedException \Gdbots\Ncr\Exception\NodeNotFound
     */
    public function testOnAppRolesRevokedNodeRefNotFound(): void
    {
        $role = RoleV1::fromArray(['_id' => RoleId::fromString('super-app')]);

        $event = AppRolesRevokedV1::create()
            ->set('node_ref', NodeRef::fromString('acme:app:7afcc2f1-9654-46d1-8fc1-b0511df257db'))
            ->addToSet('roles', [NodeRef::fromNode($role)]);

        $this->appProjector->onAppRolesRevoked($event, $this->pbjx);
    }

    /**
     * @param string $id
     *
     * @return BrowserAppV1
     */
    private function createAppById(string $id): BrowserAppV1
    {
        return BrowserAppV1::fromArray(['_id' => $id]);
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
