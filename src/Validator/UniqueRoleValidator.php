<?php
declare(strict_types = 1);

namespace Gdbots\Iam\Validator;

use Gdbots\Iam\Exception\RoleAlreadyExists;
use Gdbots\Pbj\Assertion;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Schemas\Iam\Mixin\CreateRole\CreateRole;
use Gdbots\Schemas\Iam\Mixin\UpdateRole\UpdateRole;
use Gdbots\Schemas\Iam\RoleId;
use Gdbots\Schemas\Pbjx\StreamId;

class UniqueRoleValidator implements EventSubscriber
{
    /**
     * @param PbjxEvent $pbjxEvent
     */
    public function validateCreateRole(PbjxEvent $pbjxEvent): void
    {
        /** @var CreateRole $command */
        $command = $pbjxEvent->getMessage();

        Assertion::true($command->has('node'), 'Field "node" is required.', 'node');
        $node = $command->get('node');

        $this->ensureIdDoesNotExist($pbjxEvent, $node->get('_id'));
    }

    /**
     * @param PbjxEvent $pbjxEvent
     */
    public function validateUpdateRole(PbjxEvent $pbjxEvent): void
    {
        /** @var UpdateRole $command */
        $command = $pbjxEvent->getMessage();

        Assertion::true($command->has('new_node'), 'Field "new_node" is required.', 'new_node');
    }

    /**
     * @param PbjxEvent  $pbjxEvent
     * @param RoleId     $roleId
     *
     * @throws RoleAlreadyExists
     */
    protected function ensureIdDoesNotExist(PbjxEvent $pbjxEvent, RoleId $roleId): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $message = $pbjxEvent->getMessage();

        $streamId = StreamId::fromString("role.history:{$roleId->toString()}");
        $slice = $pbjx->getEventStore()->getStreamSlice($streamId, null, 1, true, true);

        if ($slice->count()) {
            throw new RoleAlreadyExists(
                sprintf(
                    'Role with id [%s] already exists so [%s] cannot continue.',
                    $roleId->toString(),
                    $message->generateMessageRef()
                )
            );
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'gdbots:iam:mixin:create-role.validate' => 'validateCreateRole',
            'gdbots:iam:mixin:update-role.validate' => 'validateUpdateRole',
        ];
    }
}
