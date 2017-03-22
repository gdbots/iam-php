<?php
declare(strict_types = 1);

namespace Gdbots\Iam\Exception;

use Gdbots\Pbj\Exception\HasEndUserMessage;
use Gdbots\Schemas\Pbjx\Enum\Code;

final class RoleAlreadyExists extends \RuntimeException implements GdbotsIamException, HasEndUserMessage
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'Role already exists.')
    {
        parent::__construct($message, Code::ALREADY_EXISTS);
    }

    /**
     * {@inheritdoc}
     */
    public function getEndUserMessage()
    {
        return $this->getMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function getEndUserHelpLink()
    {
        return null;
    }
}
