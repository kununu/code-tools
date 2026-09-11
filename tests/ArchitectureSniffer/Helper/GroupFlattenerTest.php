<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Helper;

use InvalidArgumentException;
use Kununu\CodeTools\ArchitectureSniffer\Helper\GroupFlattener;
use PHPUnit\Framework\TestCase;

final class GroupFlattenerTest extends TestCase
{
    public function testFlattenIncludesWithDirectFqcns(): void
    {
        GroupFlattener::$groups = [
            'services' => [
                'includes' => ['App\\Service\\UserService', 'App\\Service\\OrderService'],
            ],
        ];

        $result = GroupFlattener::flattenIncludes(
            'services',
            ['App\\Service\\UserService', 'App\\Service\\OrderService']
        );

        self::assertEquals(['App\\Service\\UserService', 'App\\Service\\OrderService'], $result);
    }

    public function testFlattenIncludesWithNestedGroupReference(): void
    {
        GroupFlattener::$groups = [
            'parent' => [
                'includes' => ['child'],
            ],
            'child' => [
                'includes' => ['App\\Entity\\User', 'App\\Entity\\Order'],
            ],
        ];

        $result = GroupFlattener::flattenIncludes('parent', ['child']);

        self::assertEquals(['App\\Entity\\User', 'App\\Entity\\Order'], $result);
    }

    public function testFlattenIncludesSkipsCircularReferences(): void
    {
        GroupFlattener::$groups = [
            'groupA' => [
                'includes' => ['groupB'],
            ],
            'groupB' => [
                'includes' => ['groupA'],
            ],
        ];

        $result = GroupFlattener::flattenIncludes('groupA', ['groupB']);

        self::assertEquals([], $result);
    }

    public function testFlattenIncludesThrowsOnNonArrayKey(): void
    {
        GroupFlattener::$groups = [
            'parent' => [
                'includes' => ['child'],
            ],
            'child' => [
                'includes' => 'not-an-array',
            ],
        ];

        $this->expectException(InvalidArgumentException::class);

        GroupFlattener::flattenIncludes('parent', ['child']);
    }

    public function testFlattenExcludesWithDirectFqcns(): void
    {
        GroupFlattener::$groups = [
            'services' => [
                'includes' => ['App\\Service\\'],
                'excludes' => ['App\\Service\\Internal\\'],
            ],
        ];

        $result = GroupFlattener::flattenExcludes('services', ['App\\Service\\Internal\\'], ['App\\Service\\']);

        self::assertEquals(['App\\Service\\Internal\\'], $result);
    }

    public function testFlattenExcludesReturnsNullWhenEmpty(): void
    {
        GroupFlattener::$groups = [
            'services' => [
                'includes' => ['App\\Service\\'],
            ],
        ];

        $result = GroupFlattener::flattenExcludes('services', [], ['App\\Service\\']);

        self::assertNull($result);
    }

    public function testFlattenExcludesReturnsNullWhenAllExcludesOverlapIncludes(): void
    {
        GroupFlattener::$groups = [
            'services' => [
                'includes' => ['App\\Service\\UserService'],
                'excludes' => ['App\\Service\\UserService'],
            ],
        ];

        $result = GroupFlattener::flattenExcludes(
            'services',
            ['App\\Service\\UserService'],
            ['App\\Service\\UserService']
        );

        self::assertNull($result);
    }

    public function testFlattenExcludesWithNestedGroupReference(): void
    {
        GroupFlattener::$groups = [
            'parent' => [
                'includes' => ['App\\'],
                'excludes' => ['child'],
            ],
            'child' => [
                'excludes' => ['App\\Internal\\Secret'],
            ],
        ];

        $result = GroupFlattener::flattenExcludes('parent', ['child'], ['App\\']);

        self::assertEquals(['App\\Internal\\Secret'], $result);
    }

    protected function setUp(): void
    {
        GroupFlattener::$groups = [];
        GroupFlattener::$passedGroups = [];
    }
}
