<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\Rules\MustBeFinal;
use PHPat\Test\Builder\Rule;
use PHPUnit\Framework\TestCase;

final class MustBeFinalTest extends TestCase
{
    public function testCreateRuleReturnsRule(): void
    {
        $group = new Group(
            name: 'services',
            flattenedIncludes: ['App\\Service\\MyService'],
            flattenedExcludes: null,
            dependsOn: null,
            mustNotDependOn: null,
            extends: null,
            implements: null,
            isFinal: true,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = new ArchitectureLibrary([
            'services' => ['includes' => ['App\\Service\\MyService'], 'final' => true],
        ]);

        $result = MustBeFinal::createRule($group, $library);

        self::assertInstanceOf(Rule::class, $result);
    }

    public function testCreateRuleWithExcludes(): void
    {
        $group = new Group(
            name: 'services',
            flattenedIncludes: ['App\\Service\\MyService'],
            flattenedExcludes: ['App\\Service\\Internal\\'],
            dependsOn: null,
            mustNotDependOn: null,
            extends: null,
            implements: null,
            isFinal: true,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = new ArchitectureLibrary([
            'services' => ['includes' => ['App\\Service\\MyService'], 'excludes' => ['App\\Service\\Internal\\'], 'final' => true],
        ]);

        $result = MustBeFinal::createRule($group, $library);

        self::assertInstanceOf(Rule::class, $result);
    }
}
