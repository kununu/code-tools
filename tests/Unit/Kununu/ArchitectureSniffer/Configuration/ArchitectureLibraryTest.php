<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer\Configuration;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use PHPUnit\Framework\TestCase;

final class ArchitectureLibraryTest extends TestCase
{
    public function testConstructorBuildsGroupsSuccessfully(): void
    {
        $library = $this->createLibrary();

        $group = $library->getGroupBy('services');

        self::assertSame('services', $group->name);
        self::assertSame(['App\\Service\\UserService'], $group->flattenedIncludes);
    }

    public function testConstructorThrowsOnNonStringIncludes(): void
    {
        self::expectException(InvalidArgumentException::class);

        new ArchitectureLibrary([
            'broken' => [
                'includes' => [1, 2, 3],
            ],
        ]);
    }

    public function testGetGroupByThrowsOnMissingGroup(): void
    {
        $library = $this->createLibrary();

        self::expectException(InvalidArgumentException::class);

        $library->getGroupBy('nonexistent');
    }

    public function testResolveTargetsWithoutDependsOnRule(): void
    {
        $library = $this->createLibrary();
        $group = $library->getGroupBy('services');

        $result = $library->resolveTargets($group, ['App\\Repository\\UserRepository']);

        self::assertSame(['App\\Repository\\UserRepository'], $result);
    }

    public function testResolveTargetsWithDependsOnRuleIncludesGroupIncludes(): void
    {
        $library = $this->createLibraryWithDeps();
        $group = $library->getGroupBy('services');

        $result = $library->resolveTargets($group, ['App\\Repository\\'], true);

        self::assertContains('App\\Service\\UserService', $result);
        self::assertContains('App\\Repository\\', $result);
    }

    public function testResolveTargetsWithExtendsAndImplements(): void
    {
        $library = new ArchitectureLibrary([
            'handlers' => [
                'includes'   => ['App\\Handler\\CreateHandler'],
                'extends'    => 'App\\Handler\\AbstractHandler',
                'implements' => ['App\\Contract\\HandlerInterface'],
                'depends_on' => ['App\\Service\\'],
            ],
        ]);
        $group = $library->getGroupBy('handlers');

        $result = $library->resolveTargets($group, $group->dependsOn, true);

        self::assertContains('App\\Handler\\CreateHandler', $result);
        self::assertContains('App\\Handler\\AbstractHandler', $result);
        self::assertContains('App\\Contract\\HandlerInterface', $result);
        self::assertContains('App\\Service\\', $result);
    }

    public function testResolveTargetsWithGroupReference(): void
    {
        $library = $this->createLibraryWithDeps();
        $group = $library->getGroupBy('services');

        $result = $library->resolveTargets($group, ['repositories']);

        self::assertContains('App\\Repository\\UserRepository', $result);
    }

    public function testFindTargetExcludesReturnsExcludesFromGroup(): void
    {
        $library = new ArchitectureLibrary([
            'services' => [
                'includes' => ['App\\Service\\UserService'],
                'excludes' => ['App\\Service\\Internal\\'],
            ],
        ]);

        $group = $library->getGroupBy('services');
        $targets = ['App\\Service\\UserService'];
        $result = $library->findTargetExcludes(['services'], $targets);

        self::assertSame(['App\\Service\\Internal\\'], $result);
    }

    public function testFindTargetExcludesReturnsEmptyForNonGroupTargets(): void
    {
        $library = $this->createLibrary();

        $result = $library->findTargetExcludes(['App\\Unknown\\Class'], ['App\\Unknown\\Class']);

        self::assertSame([], $result);
    }

    public function testFindTargetExcludesFiltersOutAlreadyIncludedTargets(): void
    {
        $library = new ArchitectureLibrary([
            'services' => [
                'includes' => ['App\\Service\\UserService'],
                'excludes' => ['App\\Service\\Internal\\', 'App\\Service\\UserService'],
            ],
        ]);

        $result = $library->findTargetExcludes(['services'], ['App\\Service\\UserService']);

        self::assertNotContains('App\\Service\\UserService', $result);
        self::assertContains('App\\Service\\Internal\\', $result);
    }

    public function testConstructorHandlesExcludesAsNonStringArray(): void
    {
        $library = new ArchitectureLibrary([
            'services' => [
                'includes' => ['App\\Service\\UserService'],
                'excludes' => [1, 2],
            ],
        ]);

        $group = $library->getGroupBy('services');

        self::assertNull($group->flattenedExcludes);
    }

    private function createLibrary(): ArchitectureLibrary
    {
        return new ArchitectureLibrary([
            'services' => [
                'includes' => ['App\\Service\\UserService'],
            ],
        ]);
    }

    private function createLibraryWithDeps(): ArchitectureLibrary
    {
        return new ArchitectureLibrary([
            'services' => [
                'includes'   => ['App\\Service\\UserService'],
                'depends_on' => ['repositories'],
            ],
            'repositories' => [
                'includes' => ['App\\Repository\\UserRepository'],
            ],
        ]);
    }
}
