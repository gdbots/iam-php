<?php
declare(strict_types=1);

namespace Gdbots\Iam\Exception;

use Gdbots\Schemas\Pbjx\Enum\Code;

final class UserAlreadyExists extends \RuntimeException implements GdbotsIamException
{
    public function __construct(string $message = 'User already exists.')
    {
        parent::__construct($message, Code::ALREADY_EXISTS);
    }
}
