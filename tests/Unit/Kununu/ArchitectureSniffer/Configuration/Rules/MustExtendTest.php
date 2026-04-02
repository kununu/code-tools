<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\Rules\MustExtend;
use LogicException;
use PHPat\Test\Builder\Rule;
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

        self::assertInstanceOf(Rule::class, $result);
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

        self::expectException(LogicException::class);

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
            'services' => ['includes' => ['App\\Contract\\ServiceInterface'], 'extends' => 'App\\Base\\AbstractService'],
        ]);

        self::expectException(InvalidArgumentException::class);

        MustExtend::createRule($group, $library);
    }
}
