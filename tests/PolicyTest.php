<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Node\RoleV1;
use Gdbots\Iam\Policy;
use Gdbots\Schemas\Iam\Mixin\Role\Role;
use Gdbots\Schemas\Iam\RoleId;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Report\PHP;

class PolicyTest extends TestCase
{
    /**
     * @dataProvider getSamples
     *
     * @param string $name
     * @param string $action
     * @param Role[] $roles
     * @param bool   $expected
     */
    public function testIsGranted(string $name, string $action, array $roles, bool $expected)
    {
        $policy = new Policy($roles);

        echo json_encode($policy, JSON_PRETTY_PRINT).PHP_EOL;
        $this->assertEquals($expected, $policy->isGranted($action), "Test policy [{$name}] failed.");
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
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['acme:article:command:create-article']),
                ],
                'expected' => true,
            ],

            [
                'name'     => 'simple exact match deny',
                'action'   => 'acme:article:command:create-article',
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['acme:article:command:create-article'])
                        ->addToSet('denied', ['acme:article:command:create-article']),
                ],
                'expected' => false,
            ],

            [
                'name'     => 'message level wildcard',
                'action'   => 'acme:article:command:create-article',
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['acme:article:command:*']),
                ],
                'expected' => true,
            ],

            [
                'name'     => 'category level wildcard',
                'action'   => 'acme:article:command:create-article',
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['acme:article:*']),
                ],
                'expected' => true,
            ],

            [
                'name'     => 'category level wildcard with deny on commands',
                'action'   => 'acme:article:command:create-article',
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['acme:article:*'])
                        ->addToSet('denied', ['acme:article:command:*']),
                ],
                'expected' => false,
            ],
        ];
    }
}
