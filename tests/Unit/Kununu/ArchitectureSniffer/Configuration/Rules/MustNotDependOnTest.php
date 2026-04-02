<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\Rules\MustNotDependOn;
use LogicException;
use PHPat\Test\Builder\Rule;
use PHPUnit\Framework\TestCase;

final class MustNotDependOnTest extends TestCase
{
    public function testCreateRuleReturnsRule(): void
    {
        $group = new Group(
            name: 'services',
            flattenedIncludes: ['App\\Service\\MyService'],
            flattenedExcludes: null,
            dependsOn: null,
            mustNotDependOn: ['App\\Controller\\'],
            extends: null,
            implements: null,
            isFinal: false,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = new ArchitectureLibrary([
            'services' => ['includes' => ['App\\Service\\MyService'], 'must_not_depend_on' => ['App\\Controller\\']],
        ]);

        $result = MustNotDependOn::createRule($group, $library);

        self::assertInstanceOf(Rule::class, $result);
    }

    public function testCreateRuleThrowsWhenMustNotDependOnIsNull(): void
    {
        $group = new Group(
            name: 'services',
            flattenedIncludes: ['App\\Service\\MyService'],
            flattenedExcludes: null,
            dependsOn: null,
            mustNotDependOn: null,
            extends: null,
            implements: null,
            isFinal: false,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = new ArchitectureLibrary([
            'services' => ['includes' => ['App\\Service\\MyService']],
        ]);

        self::expectException(LogicException::class);

        MustNotDependOn::createRule($group, $library);
    }
}
