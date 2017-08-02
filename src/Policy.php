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
        $allowedSet = array_flip($this->allowed);
        $deniedSet = array_flip($this->denied);
        $wildCard = '*';

        if (empty($allowedSet)) {
            return false;
        }
        elseif (isset($deniedSet[$action]) || isset($deniedSet[$wildCard])) {
            return false;
        }
        elseif (isset($allowedSet[$action])) {
            return true;
        }
        else {
            return $this->hasPermission($action, $allowedSet, $deniedSet);
        }
    }

    /**
     * @param string $action
     * @param array $allowedSet
     * @param array $deniedSet
     *
     * @return bool
     */
    public function hasPermission(string $action, array $allowedSet = [], array $deniedSet = []): bool
    {
        $separator = ':';
        $wildCard = '*';
        $actionParts = explode($separator, $action);
        $level = '';
        $actionLevels = [];

        // iterate through all the levels of nested array
        foreach ($actionParts as $key => $segment) {
            if ($key < count($actionParts) - 1) {
                $level .= $segment . ':';
                $rule = $level . $wildCard;

                if (isset($deniedSet[$rule])) {
                    return false;
                }
                else {
                    // create array with all possible permission levels
                    array_push($actionLevels, $rule);
                }
            }
        }

        // true if $allowedSet has wildcard in it
        if (isset($wildCard, $allowedSet)) {
            return true;
        }

        // check if any of the permission level set exists in $allowedSet
        foreach ($actionLevels as $rule) {
            if (isset($allowedSet[$rule])) {
                return true;
            }
        }

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
