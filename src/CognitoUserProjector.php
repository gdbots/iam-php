<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Schemas\Iam\Mixin\UserCreated\UserCreated;
use Gdbots\Schemas\Iam\Mixin\UserDeleted\UserDeleted;
use Gdbots\Schemas\Iam\Mixin\UserUpdated\UserUpdated;

class CognitoUserProjector
{
    use EventSubscriberTrait;

    /** @var CognitoIdentityProviderClient */
    protected $client;

    /** @var string $poolId */
    protected $poolId;

    /** @var CognitoIdentityProviderClient $cognitoIdentityProviderClient */
    protected $cognitoIdentityProviderClient;

    /**
     * @param CognitoIdentityProviderClient $cognitoIdentityProviderClient
     */
    public function __construct
    (
        CognitoIdentityProviderClient $cognitoIdentityProviderClient,
        string $poolId
    )
    {
        $this->client = $cognitoIdentityProviderClient;
        $this->poolId = $poolId;
    }

    /**
     * @param UserCreated $event
     */
    public function onUserCreated(UserCreated $event): void
    {
        $id = $event->get('node')->get('_id');
        $schema = $event->get('node')->get('_schema');
        // remove pbj: from start
        $schema = str_replace('pbj:', '', $schema);
        // remove version from end
        $schema = preg_replace('/:[^:]+$/', '', $schema);
        $username = $schema . ':' . $id;

        $this->client->adminCreateUser([
            'UserPoolId' => $this->poolId,
            'Username' => $username,
            'UserAttributes' => [
                [
                    'Name' => 'email',
                    'Value' => $event->get('node')->get('email')
                ],
                [
                    'Name' => 'custom:is_staff',
                    'Value' => $event->get('node')->get('is_staff') ? '1' : '0'
                ],
            ],
        ]);
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
                ]
            ],
            'UserPoolId' => $this->poolId,
            'Username' => 'tmz:iam:node:user:' . $event->get('node_ref')->getId()
        ]);
    }

    /**
     * @param UserDeleted $event
     */
    public function onUserDeleted(UserDeleted $event): void
    {
        $this->client->adminDisableUser([
            'UserPoolId' => $this->poolId,
            'Username' => 'tmz:iam:node:user:' . $event->get('node_ref')->getId()
        ]);
    }
}
