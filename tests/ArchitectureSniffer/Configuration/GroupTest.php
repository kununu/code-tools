<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Configuration;

use Kununu\CodeTools\ArchitectureSniffer\Configuration\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GroupTest extends TestCase
{
    public function testBuildFromWithAllAttributes(): void
    {
        $group = Group::buildFrom(
            groupName: 'TestGroup',
            flattenedIncludes: ['App\\Service\\'],
            targetAttributes: [
                Group::DEPENDS_ON_KEY                             => ['App\\Repository\\'],
                Group::MUST_NOT_DEPEND_ON_KEY                     => ['App\\Controller\\'],
                Group::EXTENDS_KEY                                => 'App\\Base\\AbstractService',
                Group::IMPLEMENTS_KEY                             => ['App\\Contract\\ServiceInterface'],
                Group::FINAL_KEY                                  => true,
                Group::READONLY_KEY                               => true,
                Group::MUST_ONLY_HAVE_ONE_PUBLIC_METHOD_NAMED_KEY => 'execute',
            ],
            flattenedExcludes: ['App\\Service\\Internal\\'],
        );

        self::assertEquals('TestGroup', $group->name);
        self::assertEquals(['App\\Service\\'], $group->flattenedIncludes);
        self::assertEquals(['App\\Service\\Internal\\'], $group->flattenedExcludes);
        self::assertEquals(['App\\Repository\\'], $group->dependsOn);
        self::assertEquals(['App\\Controller\\'], $group->mustNotDependOn);
        self::assertEquals('App\\Base\\AbstractService', $group->extends);
        self::assertEquals(['App\\Contract\\ServiceInterface'], $group->implements);
        self::assertTrue($group->isFinal);
        self::assertTrue($group->isReadonly);
        self::assertEquals('execute', $group->mustOnlyHaveOnePublicMethodName);
    }

    public function testBuildFromWithMinimalAttributes(): void
    {
        $group = Group::buildFrom(
            groupName: 'MinimalGroup',
            flattenedIncludes: ['App\\Entity\\'],
            targetAttributes: [
                Group::INCLUDES_KEY => ['App\\Entity\\'],
            ],
            flattenedExcludes: null,
        );

        self::assertEquals('MinimalGroup', $group->name);
        self::assertEquals(['App\\Entity\\'], $group->flattenedIncludes);
        self::assertNull($group->flattenedExcludes);
        self::assertNull($group->dependsOn);
        self::assertNull($group->mustNotDependOn);
        self::assertNull($group->extends);
        self::assertNull($group->implements);
        self::assertFalse($group->isFinal);
        self::assertFalse($group->isReadonly);
        self::assertNull($group->mustOnlyHaveOnePublicMethodName);
    }

    public function testBuildFromWithFinalFalse(): void
    {
        $group = Group::buildFrom(
            groupName: 'NotFinal',
            flattenedIncludes: ['App\\'],
            targetAttributes: [
                Group::FINAL_KEY    => false,
                Group::READONLY_KEY => false,
            ],
            flattenedExcludes: null,
        );

        self::assertFalse($group->isFinal);
        self::assertFalse($group->isReadonly);
    }

    public function testBuildFromWithNonStringExtends(): void
    {
        $group = Group::buildFrom(
            groupName: 'BadExtends',
            flattenedIncludes: ['App\\'],
            targetAttributes: [
                Group::EXTENDS_KEY => 123,
            ],
            flattenedExcludes: null,
        );

        self::assertNull($group->extends);
    }

    public function testBuildFromWithNonStringMethodName(): void
    {
        $group = Group::buildFrom(
            groupName: 'BadMethod',
            flattenedIncludes: ['App\\'],
            targetAttributes: [
                Group::MUST_ONLY_HAVE_ONE_PUBLIC_METHOD_NAMED_KEY => true,
            ],
            flattenedExcludes: null,
        );

        self::assertNull($group->mustOnlyHaveOnePublicMethodName);
    }

    #[DataProvider('shouldMethodsDataProvider')]
    public function testShouldMethods(
        Group $group,
        bool $shouldBeFinal,
        bool $shouldBeReadonly,
        bool $shouldExtend,
        bool $shouldNotDependOn,
        bool $shouldDependOn,
        bool $shouldImplement,
        bool $shouldOnlyHaveOnePublicMethodNamed,
    ): void {
        self::assertEquals($shouldBeFinal, $group->shouldBeFinal());
        self::assertEquals($shouldBeReadonly, $group->shouldBeReadonly());
        self::assertEquals($shouldExtend, $group->shouldExtend());
        self::assertEquals($shouldNotDependOn, $group->shouldNotDependOn());
        self::assertEquals($shouldDependOn, $group->shouldDependOn());
        self::assertEquals($shouldImplement, $group->shouldImplement());
        self::assertEquals($shouldOnlyHaveOnePublicMethodNamed, $group->shouldOnlyHaveOnePublicMethodNamed());
    }

    /**
     * @return array<string, array{
     *     group: Group,
     *     shouldBeFinal: bool,
     *     shouldBeReadonly: bool,
     *     shouldExtend: bool,
     *     shouldNotDependOn: bool,
     *     shouldDependOn: bool,
     *     shouldImplement: bool,
     *     shouldOnlyHaveOnePublicMethodNamed: bool
     * }>
     */
    public static function shouldMethodsDataProvider(): array
    {
        return [
            'all_enabled' => [
                'group'                              => new Group(
                    name: 'Full',
                    flattenedIncludes: ['App\\'],
                    flattenedExcludes: null,
                    dependsOn: ['Dep\\'],
                    mustNotDependOn: ['Bad\\'],
                    extends: 'App\\Base',
                    implements: ['App\\ContractInterface'],
                    isFinal: true,
                    isReadonly: true,
                    mustOnlyHaveOnePublicMethodName: 'run',
                ),
                'shouldBeFinal'                      => true,
                'shouldBeReadonly'                   => true,
                'shouldExtend'                       => true,
                'shouldNotDependOn'                  => true,
                'shouldDependOn'                     => true,
                'shouldImplement'                    => true,
                'shouldOnlyHaveOnePublicMethodNamed' => true,
            ],
            'all_disabled' => [
                'group'                              => new Group(
                    name: 'Empty',
                    flattenedIncludes: ['App\\'],
                    flattenedExcludes: null,
                    dependsOn: null,
                    mustNotDependOn: null,
                    extends: null,
                    implements: null,
                    isFinal: false,
                    isReadonly: false,
                    mustOnlyHaveOnePublicMethodName: null,
                ),
                'shouldBeFinal'                      => false,
                'shouldBeReadonly'                   => false,
                'shouldExtend'                       => false,
                'shouldNotDependOn'                  => false,
                'shouldDependOn'                     => false,
                'shouldImplement'                    => false,
                'shouldOnlyHaveOnePublicMethodNamed' => false,
            ],
            'empty_arrays_are_falsy' => [
                'group'                              => new Group(
                    name: 'EmptyArrays',
                    flattenedIncludes: ['App\\'],
                    flattenedExcludes: null,
                    dependsOn: [],
                    mustNotDependOn: [],
                    extends: null,
                    implements: [],
                    isFinal: false,
                    isReadonly: false,
                    mustOnlyHaveOnePublicMethodName: '',
                ),
                'shouldBeFinal'                      => false,
                'shouldBeReadonly'                   => false,
                'shouldExtend'                       => false,
                'shouldNotDependOn'                  => false,
                'shouldDependOn'                     => false,
                'shouldImplement'                    => false,
                'shouldOnlyHaveOnePublicMethodNamed' => false,
            ],
        ];
    }
}
