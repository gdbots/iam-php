<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Gdbots\Iam\PolicyValidator;
use PHPUnit\Framework\TestCase;

class PolicyValidatorTest extends TestCase
{
    /**
     * @dataProvider getSamples
     *
     * @param string $name
     * @param string $action
     * @param array  $allowed
     * @param array  $denied
     * @param bool   $expected
     */
    public function testIsGranted(string $name, string $action, array $allowed, array $denied, bool $expected)
    {
        $actual = PolicyValidator::isGranted($action, $allowed, $denied);
        $this->assertEquals($expected, $actual, "Test policy [{$name}] failed.");
    }

    /**
     * @return array
     */
    public function getSamples(): array
    {
        return [
            [
                'name'     => 'simple exact match allow',
                'action'   => 'acme:article:command:create-article',
                'allowed'  => [
                    'acme:article:command:create-article',
                ],
                'denied'   => [
                ],
                'expected' => true,
            ],

            [
                'name'     => 'simple exact match deny',
                'action'   => 'acme:article:command:create-article',
                'allowed'  => [
                    'acme:article:command:create-article',
                ],
                'denied'   => [
                    'acme:article:command:create-article',
                ],
                'expected' => false,
            ],

            [
                'name'     => 'message level wildcard',
                'action'   => 'acme:article:command:create-article',
                'allowed'  => [
                    'acme:article:command:*',
                ],
                'denied'   => [
                ],
                'expected' => true,
            ],

            [
                'name'     => 'category level wildcard',
                'action'   => 'acme:article:command:create-article',
                'allowed'  => [
                    'acme:article:*',
                ],
                'denied'   => [
                ],
                'expected' => true,
            ],

            [
                'name'     => 'category level wildcard with deny on commands',
                'action'   => 'acme:article:command:create-article',
                'allowed'  => [
                    'acme:article:*',
                ],
                'denied'   => [
                    'acme:article:command:*'
                ],
                'expected' => true,
            ],
        ];
    }
}
