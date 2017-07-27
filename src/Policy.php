<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Schemas\Iam\Mixin\Role\Role;

final class Policy implements \JsonSerializable
{
    /** @var string[] */
    private $roles = [];

    /** @var string[] */
    private $allowed = [];

    /** @var string[] */
    private $denied = [];

    /**
     * @param Role[] $roles
     */
    public function __construct(array $roles = [])
    {
        foreach ($roles as $role) {
            $this->roles[] = (string)$role->get('_id');
            $this->allowed = array_merge($this->allowed, $role->get('allowed', []));
            $this->denied = array_merge($this->denied, $role->get('denied', []));
        }
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    public function isGranted(string $action): bool
    {
        return false;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'roles'   => $this->roles,
            'allowed' => $this->allowed,
            'denied'  => $this->denied,
        ];
    }
}
