<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Group;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\AssertionType;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\MustExtend;
use LogicException;
use PHPat\Rule\Assertion\Constraint;
use PHPUnit\Framework\TestCase;

final class MustExtendTest extends TestCase
{
    public function testCreateRuleReturnsRule(): void
    {
        $group = new Group(
            name: 'services',
            flattenedIncludes: ['App\\Service\\MyService'],
            flattenedExcludes: null,
            dependsOn: null,
            mustNotDependOn: null,
            extends: 'App\\Base\\AbstractService',
            implements: null,
            isFinal: false,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = new ArchitectureLibrary([
            'services' => ['includes' => ['App\\Service\\MyService'], 'extends' => 'App\\Base\\AbstractService'],
        ]);

        $result = MustExtend::createRule($group, $library);

        self::assertEquals(Constraint::Should, $result()->constraint);
        self::assertEquals(AssertionType::Extend->value, $result()->assertionType);
    }

    public function testCreateRuleThrowsWhenExtendsIsNull(): void
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

        MustExtend::createRule($group, $library);
    }

    public function testCreateRuleThrowsWhenIncludesContainInterface(): void
    {
        $group = new Group(
            name: 'services',
            flattenedIncludes: ['App\\Contract\\ServiceInterface'],
            flattenedExcludes: null,
            dependsOn: null,
            mustNotDependOn: null,
            extends: 'App\\Base\\AbstractService',
            implements: null,
            isFinal: false,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = new ArchitectureLibrary([
            'services' => [
                'includes' => ['App\\Contract\\ServiceInterface'],
                'extends'  => 'App\\Base\\AbstractService',
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);

        MustExtend::createRule($group, $library);
    }
}
