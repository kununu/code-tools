<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Group;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\AssertionType;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\MustImplement;
use LogicException;
use PHPat\Rule\Assertion\Constraint;
use PHPUnit\Framework\TestCase;

final class MustImplementTest extends TestCase
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
            implements: ['App\\Contract\\ServiceInterface'],
            isFinal: false,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = new ArchitectureLibrary([
            'services' => [
                'includes'   => ['App\\Service\\MyService'],
                'implements' => ['App\\Contract\\ServiceInterface'],
            ],
        ]);

        $result = MustImplement::createRule($group, $library);

        self::assertEquals(Constraint::Should, $result()->constraint);
        self::assertEquals(AssertionType::Implement->value, $result()->assertionType);
    }

    public function testCreateRuleThrowsWhenImplementsIsNull(): void
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

        $this->expectException(LogicException::class);

        MustImplement::createRule($group, $library);
    }

    public function testCreateRuleThrowsWhenTargetIsNotInterface(): void
    {
        $group = new Group(
            name: 'services',
            flattenedIncludes: ['App\\Service\\MyService'],
            flattenedExcludes: null,
            dependsOn: null,
            mustNotDependOn: null,
            extends: null,
            implements: ['App\\Service\\ConcreteClass'],
            isFinal: false,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = new ArchitectureLibrary([
            'services' => ['includes' => ['App\\Service\\MyService'], 'implements' => ['App\\Service\\ConcreteClass']],
        ]);

        $this->expectException(InvalidArgumentException::class);

        MustImplement::createRule($group, $library);
    }
}
