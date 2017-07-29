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

        echo PHP_EOL . 'allowed: ';
        print_r($policy->allowed);
        echo PHP_EOL . 'denied: ';
        print_r($policy->denied);
        echo PHP_EOL . 'action ' . $action . PHP_EOL;
        print_r($policy->createPermissionSet());
        echo PHP_EOL . 'Result Value: ';
        print_r($policy->isGranted($action));
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
                'expected' => 1,
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
                'expected' => 0,
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
