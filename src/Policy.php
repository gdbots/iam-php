<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Schemas\Iam\Mixin\Role\Role;

final class Policy implements \JsonSerializable
{
    /** @var string[] */
    private $roles = [];

    /** @var string[] */
    public $allowed = [];

    /** @var string[] */
    public $denied = [];

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
        if (empty($this->allowed)) {
            return false;
        }
        elseif (in_array($action, $this->denied)) {
            return false;
        }
        elseif (in_array($action, $this->allowed)) {
            return true;
        }
        else {
            return $this->hasPermission($action);
        }
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    public function hasPermission(string $action): bool
    {
        $separator = ':';
        $actionParts = explode($separator, $action);
        $permissionSet = $this->createPermissionSet();

        // iterate through all the levels of nested array
        foreach ($actionParts as $segment) {
            if (isset($permissionSet[$segment])) {
                $permissionSet = $permissionSet[$segment];
            }
            else {
                return false;
            }

            if ($permissionSet == 1)
                return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function createPermissionSet(): array
    {
        $separator = ':';
        $nestedArray = [];

        // Convert all the allowed rules into a nested array
        foreach ($this->allowed as $str) {
            $array = &$nestedArray;
            $levels = explode($separator, $str);

            for ($i = 0; $i < count($levels); $i++) {
                if (!isset($array[$levels[$i]])) {
                    $array[$levels[$i]] = [];
                }

                // set to true when wildcard is hit
                if ($levels[$i] == '*') {
                    $array = true;
                }
                else {
                    // assign the associative array back to $array
                    $array = &$array[$levels[$i]];
                }
            }
            $array = true;
        }

        // Stripping out items in the array based on deny rules
        foreach ($this->denied as $str) {
            $array = &$nestedArray;
            $levels = explode($separator, $str);

            for ($i = 0; $i < count($levels); $i++) {
                if ($levels[$i] != '*' && !isset($array[$levels[$i]])) {
                    $array[$levels[$i]] = [];
                }
                elseif ($levels[$i] != '*' && isset($array[$levels[$i]])) {
                    unset($array[$levels[$i]]);
                }

                // set to false when wildcard is hit
                if ($levels[$i] == '*') {
                    $array = false;
                }
                else {
                    $array = &$array[$levels[$i]];
                }
            }
            $array = false;
        }

        return $nestedArray;
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
