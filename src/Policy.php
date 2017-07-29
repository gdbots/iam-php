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
        elseif (isset($action, $this->denied)) {
            return false;
        }
        elseif (isset($action, $this->allowed)) {
            return true;
        }
        else {
            return $this->hasPermission($action);
        }
    }

    /**
     * @return array
     */
    public function createPermissionSet()
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

                if ($levels[$i] == '*') {
                    $array = true;
                }
                else {
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
                if (!$array[$levels[$i]] != 1 && !isset($array[$levels[$i]])) {
                    $array[$levels[$i]] = [];
                }
                elseif ($array[$levels[$i]] == 1) {
                    unset($array[$levels[$i]]);
                }
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

    public function hasPermission(string $action)
    {
        $separator = ':';
        $actionParts = explode($separator, $action);
        $permissionSet = $this->createPermissionSet();

        foreach ($actionParts as $segment) {
            $permissionSet = $permissionSet[$segment];
            if ($permissionSet === '1' || !$permissionSet)
                return $permissionSet;
        }

        return $permissionSet;
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
