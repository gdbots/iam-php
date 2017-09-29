<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Schemas\Iam\Mixin\UserCreated\UserCreated;
use Gdbots\Schemas\Iam\Mixin\UserDeleted\UserDeleted;
use Gdbots\Schemas\Iam\Mixin\UserRolesGranted\UserRolesGranted;
use Gdbots\Schemas\Iam\Mixin\UserRolesRevoked\UserRolesRevoked;
use Gdbots\Schemas\Iam\Mixin\UserUpdated\UserUpdated;

class CognitoUserProjector
{
    use EventSubscriberTrait;

    /** @var CognitoIdentityProviderClient */
    protected $client;

    /**
     *
     */
    public function __construct()
    {
        $args = array(
            'credentials' => [
                'key' => '',
                'secret' => ''
            ],
            'region' => 'us-east-2',
            'version' => '2016-04-18'
        );
        $this->client = new CognitoIdentityProviderClient($args);
        // make the aws client here
    }

    /**
     * @param UserCreated $event
     */
    public function onUserCreated(UserCreated $event): void
    {
        // stuff
    }

    /**
     * @param UserUpdated $event
     */
    public function onUserUpdated(UserUpdated $event): void
    {
        $this->client->adminUpdateUserAttributes([
            'UserAttributes' => [
                [
                    'Name' => 'custom:is_staff',
                    'Value' => $event->get('new_node')->get('is_staff') ? '1' : '0'
                ],
                [
                    'Name' => 'custom:is_blocked',
                    'Value' => $event->get('new_node')->get('is_blocked') ? '1' : '0'
                ],
                [
                    'Name' => 'custom:test',
                    'Value' => $event->get('node_ref')->toString()
                ]
            ],
            'UserPoolId' => 'us-east-2_mZNBwnzZW',
            'Username' => 'tmz:iam:node:user:' . $event->get('node_ref')->getId()
        ]);
    }

    /**
     * @param UserDeleted $event
     */
    public function onUserDeleted(UserDeleted $event): void
    {
        // stuff
    }

    /**
     * @param UserRolesGranted $event
     */
    public function onUserRolesGranted(UserRolesGranted $event): void
    {
        // stuff
    }

    /**
     * @param UserRolesRevoked $event
     */
    public function onUserRolesRevoked(UserRolesRevoked $event): void
    {
        // stuff
    }
}
