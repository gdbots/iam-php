<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Schemas\Iam\Mixin\Role\Role;
use Gdbots\Schemas\Iam\Mixin\Role\RoleV1;
use Gdbots\Schemas\Iam\RoleId;

final class Policy implements \JsonSerializable
{
    /** @var string[] */
    private $roles = [];

    /** @var string[] */
    private $allowed = [];

    /** @var string[] */
    private $denied = [];

    /** @var string[] */
    private $allowedSet = [];

    /** @var string[] */
    private $deniedSet = [];

    /** @var mixed[] */
    private $roleSet = [];

    /**
     * delimiter used for action
     */
    const DELIMITER = ':';

    /**
     * wildcard symbol used in allowed, denied rules
     */
    const WILDCARD = '*';

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

        $this->roleSet = array_flip($this->roles);
        $this->allowedSet = array_flip($this->allowed);
        $this->deniedSet = array_flip($this->denied);
    }

    /**
     * @param string $role
     *
     * @return bool
     */
    public function hasRoles(string $role): bool
    {
        if (isset($this->roleSet[$role])) {
            return true;
        }

        return false;
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    public function isGranted(string $action): bool
    {
        if (empty($this->allowedSet)) {
            return false;
        }

        if (isset($this->deniedSet[$action]) || isset($this->deniedSet[self::WILDCARD])) {
            return false;
        }

        $actionLevels = $this->getActionLevels($action);

        // check if a combination exists in $this->deniedSet
        foreach ($actionLevels as $rule) {
            if (isset($this->deniedSet[$rule])) {
                return false;
            }
        }

        // check if a combination exists in $this->allowedSet
        foreach ($actionLevels as $rule) {
            if (isset($this->allowedSet[$rule])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $action
     *
     * @return array
     */
    public function getActionLevels(string $action): array
    {
        $actionLevels = [self::WILDCARD];
        $level = '';
        $actionParts = explode(self::DELIMITER, $action);

        // push all possible match combinations to an array
        foreach ($actionParts as $key => $segment) {
            if ($key < count($actionParts) - 1) {
                $level .= $segment . ':';
                $rule = $level . self::WILDCARD;

                // create array with all possible permission levels
                array_push($actionLevels, $rule);
            }
        }

        // pushing the action itself
        array_push($actionLevels, $action);

        return $actionLevels;
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
