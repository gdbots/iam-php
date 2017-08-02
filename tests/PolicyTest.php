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
    public function testIsGranted(string $name, string $action, array $roles = [], bool $expected)
    {
        $policy = new Policy($roles);
        echo PHP_EOL . 'action - ' . $action . PHP_EOL;
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
                        ->addToSet('allowed', ['acme:article:command:create-article', 'acme:article:command:edit-article'])
                ],
                'expected' => true,
            ],

            [
                'name'     => 'simple exact match deny',
                'action'   => 'acme:article:command:create-article',
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['acme:article:command:create-article', 'acme:article:command:edit-article'])
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

            [
                'name'     => 'category level wildcard with set of denies on commands',
                'action'   => 'acme:article:command:delete-article',
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['acme:article:*', 'acme:blog:*'])
                        ->addToSet('denied', ['acme:article:command:create-article',  'acme:article:command:edit-article']),
                ],
                'expected' => true,
            ],

            [
                'name'      => 'top level wildcard allowed',
                'action'    => 'acme:article:create-article',
                'roles'     => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['*'])
                        ->addToSet('denied', []),
                ],
                'expected'  => true,
            ],

            [
                'name'      => 'top level wildcard allowed with deny on package level',
                'action'    => 'acme:article:request:get-userid',
                'roles'     => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['*'])
                        ->addToSet('denied', ['acme:article:*'])
                ],
                'expected'  => false,
            ],
        ];
    }
}
