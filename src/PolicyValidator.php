<?php
declare(strict_types=1);

namespace Gdbots\Iam;

final class PolicyValidator
{
    /**
     * @param string $action
     * @param array  $allowed
     * @param array  $denied
     *
     * @return bool
     */
    public static function isGranted(string $action, array $allowed = [], array $denied = []): bool
    {
        return false;
    }
}
