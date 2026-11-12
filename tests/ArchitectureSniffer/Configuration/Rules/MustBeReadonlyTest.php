<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Configuration\Rules;

use Kununu\CodeTools\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Group;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\AssertionType;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\MustBeReadonly;
use PHPat\Rule\Assertion\Constraint;
use PHPUnit\Framework\TestCase;

final class MustBeReadonlyTest extends TestCase
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
            isFinal: false,
            isReadonly: true,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = new ArchitectureLibrary([
            'services' => ['includes' => ['App\\Service\\MyService'], 'readonly' => true],
        ]);

        $result = MustBeReadonly::createRule($group, $library);

        self::assertEquals(Constraint::Should, $result()->constraint);
        self::assertEquals(AssertionType::BeReadonly->value, $result()->assertionType);
    }
}
