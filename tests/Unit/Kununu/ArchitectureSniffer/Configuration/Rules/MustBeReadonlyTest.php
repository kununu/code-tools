<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\Rules\MustBeReadonly;
use PHPat\Test\Builder\Rule;
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

        self::assertInstanceOf(Rule::class, $result);
    }
}
