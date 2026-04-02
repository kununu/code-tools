<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\Rules\MustImplement;
use LogicException;
use PHPat\Test\Builder\Rule;
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

        self::assertInstanceOf(Rule::class, $result);
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

        self::expectException(LogicException::class);

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

        self::expectException(InvalidArgumentException::class);

        MustImplement::createRule($group, $library);
    }
}
