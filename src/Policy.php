<?php
declare(strict_types=1);

namespace Gdbots\Iam;

use Gdbots\Pbj\Message;
use Gdbots\Schemas\Iam\RoleId;

final class Policy implements \JsonSerializable
{
    private array $roles = [];
    private array $allowed = [];
    private array $denied = [];

    /** delimiter used for action */
    private const DELIMITER = ':';

    /** wildcard symbol used in allowed, denied rules */
    private const WILDCARD = '*';

    /**
     * @param Message[] $roles
     */
    public function __construct(array $roles = [])
    {
        foreach ($roles as $role) {
            $this->roles[$role->fget('_id')] = true;
            $this->allowed = array_merge($this->allowed, $role->fget('allowed', []));
            $this->denied = array_merge($this->denied, $role->fget('denied', []));
        }

        $this->allowed = array_flip($this->allowed);
        $this->denied = array_flip($this->denied);
    }

    /**
     * Returns true if the policy include the provided role.
     *
     * @param RoleId|string $id
     *
     * @return bool
     */
    public function hasRole($id): bool
    {
        return isset($this->roles[(string)$id]);
    }

    /**
     * Returns true if the policy should allow the action
     * to be carried out.
     *
     * @param string $action
     *
     * @return bool
     */
    public function isGranted(string $action): bool
    {
        if (empty($this->allowed)) {
            return false;
        }

        if (isset($this->denied[$action]) || isset($this->denied[self::WILDCARD])) {
            return false;
        }

        $rules = $this->getRules($action);

        foreach ($rules as $rule) {
            if (isset($this->denied[$rule])) {
                return false;
            }
        }

        foreach ($rules as $rule) {
            if (isset($this->allowed[$rule])) {
                return true;
            }
        }

        return false;
    }

    public function jsonSerialize()
    {
        return [
            'roles'   => array_keys($this->roles),
            'allowed' => array_keys($this->allowed),
            'denied'  => array_keys($this->denied),
        ];
    }

    public function __toString()
    {
        return json_encode($this);
    }

    /**
     * Converts an action with potentially colon delimiters
     * into a set of permissions to check for.
     *
     * @param string $action
     *
     * @return string[]
     * @example
     *   An action of "acme:blog:command:publish-article" becomes
     *   an array of:
     *   [
     *   '*',
     *   'acme:*',
     *   'acme:blog:*',
     *   'acme:blog:command:*',
     *   'acme:blog:command:publish-article',
     *   ]
     *
     */
    private function getRules(string $action): array
    {
        $rules = [];
        $parts = explode(self::DELIMITER, $action);

        while (array_pop($parts)) {
            $rules[] = implode(self::DELIMITER, $parts) . self::DELIMITER . self::WILDCARD;
        }

        $rules = array_reverse($rules);
        $rules[0] = trim($rules[0], self::DELIMITER);
        $rules[] = $action;

        return $rules;
    }
}
