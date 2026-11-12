<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Configuration\Rules;

use Kununu\CodeTools\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Group;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\AssertionType;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\MustBeFinal;
use PHPat\Rule\Assertion\Constraint;
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

        self::assertEquals(Constraint::Should, $result()->constraint);
        self::assertEquals(AssertionType::BeFinal->value, $result()->assertionType);
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
            'services' => [
                'includes' => ['App\\Service\\MyService'],
                'excludes' => ['App\\Service\\Internal\\'],
                'final'    => true,
            ],
        ]);

        $result = MustBeFinal::createRule($group, $library);

        self::assertEquals(Constraint::Should, $result()->constraint);
        self::assertEquals(AssertionType::BeFinal->value, $result()->assertionType);
    }
}
