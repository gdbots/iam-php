<?php
declare(strict_types=1);

namespace Gdbots\Iam\Exception;

use Gdbots\Schemas\Pbjx\Enum\Code;

final class InvalidArgumentException extends \InvalidArgumentException implements GdbotsIamException
{
    /**
     * @param string     $message
     * @param \Exception $previous
     */
    public function __construct(string $message = '', ?\Exception $previous = null)
    {
        parent::__construct($message, Code::INVALID_ARGUMENT, $previous);
    }
}
