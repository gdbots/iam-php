<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Ncr\AbstractUpdateNodeHandler;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Iam\Mixin\User\User;
use Gdbots\Schemas\Iam\Mixin\User\UserV1Mixin;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\Mixin\NodeUpdated\NodeUpdated;
use Gdbots\Schemas\Ncr\Mixin\UpdateNode\UpdateNode;

class UpdateUserHandler extends AbstractUpdateNodeHandler
{
    /**
     * {@inheritdoc}
     */
    protected function isNodeSupported(Node $node): bool
    {
        return $node instanceof User;
    }

    /**
     * {@inheritdoc}
     */
    protected function beforePutEvents(NodeUpdated $event, UpdateNode $command, Pbjx $pbjx): void
    {
        parent::beforePutEvents($event, $command, $pbjx);

        /** @var User $oldNode */
        $oldNode = $event->get('old_node');

        /** @var User $newNode */
        $newNode = $event->get('new_node');
        $newNode
            // email SHOULD NOT change during an update, use "change-email"
            ->set('email', $oldNode->get('email'))
            ->set('email_domain', $oldNode->get('email_domain'))
            // roles SHOULD NOT change during an update
            ->clear('roles')
            ->addToSet('roles', $oldNode->get('roles', []));

        if ($newNode->has('email')) {
            $email = strtolower($newNode->get('email'));
            $emailParts = explode('@', $email);
            $newNode->set('email', $email);
            $newNode->set('email_domain', array_pop($emailParts));
        }

        if (!$newNode->has('title')) {
            $newNode->set('title', trim($newNode->get('first_name') . ' ' . $newNode->get('last_name')));
        }

        // we really only have "active" and "deleted" users so we
        // force the status to "published" if not "deleted".
        if (!NodeStatus::DELETED()->equals($newNode->get('status'))) {
            $newNode->set('status', NodeStatus::PUBLISHED());
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        $curie = UserV1Mixin::findOne()->getCurie();
        return [
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:command:update-user"),
        ];
    }
}
