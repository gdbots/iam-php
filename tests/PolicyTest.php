<?php
declare(strict_types=1);

namespace Gdbots\Tests\Iam;

use Acme\Schemas\Iam\Node\RoleV1;
use Gdbots\Iam\Policy;
use Gdbots\Schemas\Iam\Mixin\Role\Role;
use Gdbots\Schemas\Iam\RoleId;
use PHPUnit\Framework\TestCase;

class PolicyTest extends TestCase
{
    /**
     * @dataProvider getIsGrantedSamples
     *
     * @param string $name
     * @param string $action
     * @param Role[] $roles
     * @param bool   $expected
     */
    public function testIsGranted(string $name, string $action, array $roles = [], bool $expected)
    {
        $policy = new Policy($roles);
        $this->assertEquals($expected, $policy->isGranted($action), "Test policy [{$name}] failed.");
    }

    /**
     * @dataProvider getHasRoleSamples
     *
     * @param string $name
     * @param array  $roles
     * @param string $role
     * @param bool   $expected
     */
    public function testHasRoles(string $name, array $roles = [], string $role, bool $expected)
    {
        $policy = new Policy($roles);
        $this->assertEquals($expected, $policy->hasRole(RoleId::create($role)), "Test policy [{$name}] failed");
    }

    /**
     * @return array
     */
    public function getIsGrantedSamples(): array
    {
        return [
            [
                'name'     => 'simple exact match allow',
                'action'   => 'acme:blog:command:create-article',
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['acme:blog:command:create-article', 'acme:blog:command:edit-article']),
                ],
                'expected' => true,
            ],

            [
                'name'     => 'simple exact match deny',
                'action'   => 'acme:blog:command:create-article',
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['acme:blog:command:create-article', 'acme:blog:command:edit-article'])
                        ->addToSet('denied', ['acme:blog:command:create-article']),
                ],
                'expected' => false,
            ],

            [
                'name'     => 'message level wildcard',
                'action'   => 'acme:blog:command:create-article',
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['acme:blog:command:*']),
                ],
                'expected' => true,
            ],

            [
                'name'     => 'category level wildcard',
                'action'   => 'acme:blog:command:create-article',
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['acme:blog:*']),
                ],
                'expected' => true,
            ],

            [
                'name'     => 'category level wildcard with deny on commands',
                'action'   => 'acme:blog:command:create-article',
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['acme:blog:*'])
                        ->addToSet('denied', ['acme:blog:command:*']),
                ],
                'expected' => false,
            ],

            [
                'name'     => 'category level wildcard with set of denies on commands',
                'action'   => 'acme:blog:command:delete-article',
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['acme:blog:*', 'acme:blog:*'])
                        ->addToSet('denied', ['acme:blog:command:create-article', 'acme:blog:command:edit-article']),
                ],
                'expected' => true,
            ],

            [
                'name'     => 'top level wildcard allowed',
                'action'   => 'acme:blog:create-article',
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['*'])
                        ->addToSet('denied', []),
                ],
                'expected' => true,
            ],

            [
                'name'     => 'top level wildcard allowed with deny on package level',
                'action'   => 'acme:blog:request:get-userid',
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['*'])
                        ->addToSet('denied', ['acme:blog:*']),
                ],
                'expected' => false,
            ],

            [
                'name'     => 'action allowed with deny on command level',
                'action'   => 'acme:blog:command:create-article',
                'roles'    => [
                    RoleV1::create()
                        ->set('_id', RoleId::fromString('test1'))
                        ->addToSet('allowed', ['acme:blog:command:create-article'])
                        ->addToSet('denied', ['acme:blog:command:*']),
                ],
                'expected' => false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getHasRoleSamples(): array
    {
        return [
            [
                'name'     => 'simple role exists',
                'roles'    => [
                    RoleV1::fromArray(['_id' => 'administrator']),
                ],
                'role'     => 'administrator',
                'expected' => true,
            ],

            [
                'name'     => 'simple role not exist',
                'roles'    => [
                    RoleV1::fromArray(['_id' => 'administrator']),
                ],
                'role'     => 'editor',
                'expected' => false,
            ],

            [
                'name'     => 'multiple roles',
                'roles'    => [
                    RoleV1::fromArray(['_id' => 'administrator']),
                    RoleV1::fromArray(['_id' => 'editor']),
                ],
                'role'     => 'editor',
                'expected' => true,
            ],
        ];
    }

}
