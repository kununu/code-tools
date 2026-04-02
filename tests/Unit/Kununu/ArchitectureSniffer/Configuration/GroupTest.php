<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer\Configuration;

use Kununu\ArchitectureSniffer\Configuration\Group;
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

        self::assertSame('TestGroup', $group->name);
        self::assertSame(['App\\Service\\'], $group->flattenedIncludes);
        self::assertSame(['App\\Service\\Internal\\'], $group->flattenedExcludes);
        self::assertSame(['App\\Repository\\'], $group->dependsOn);
        self::assertSame(['App\\Controller\\'], $group->mustNotDependOn);
        self::assertSame('App\\Base\\AbstractService', $group->extends);
        self::assertSame(['App\\Contract\\ServiceInterface'], $group->implements);
        self::assertTrue($group->isFinal);
        self::assertTrue($group->isReadonly);
        self::assertSame('execute', $group->mustOnlyHaveOnePublicMethodName);
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

        self::assertSame('MinimalGroup', $group->name);
        self::assertSame(['App\\Entity\\'], $group->flattenedIncludes);
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

    #[DataProvider('shouldMethodsProvider')]
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
        self::assertSame($shouldBeFinal, $group->shouldBeFinal());
        self::assertSame($shouldBeReadonly, $group->shouldBeReadonly());
        self::assertSame($shouldExtend, $group->shouldExtend());
        self::assertSame($shouldNotDependOn, $group->shouldNotDependOn());
        self::assertSame($shouldDependOn, $group->shouldDependOn());
        self::assertSame($shouldImplement, $group->shouldImplement());
        self::assertSame($shouldOnlyHaveOnePublicMethodNamed, $group->shouldOnlyHaveOnePublicMethodNamed());
    }

    public static function shouldMethodsProvider(): array
    {
        return [
            'all enabled' => [
                new Group(
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
                true, true, true, true, true, true, true,
            ],
            'all disabled' => [
                new Group(
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
                false, false, false, false, false, false, false,
            ],
            'empty arrays are falsy' => [
                new Group(
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
                false, false, false, false, false, false, false,
            ],
        ];
    }
}
