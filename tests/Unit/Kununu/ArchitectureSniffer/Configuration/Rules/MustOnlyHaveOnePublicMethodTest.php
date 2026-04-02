<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\Rules\MustOnlyHaveOnePublicMethod;
use PHPat\Test\Builder\Rule;
use PHPUnit\Framework\TestCase;

final class MustOnlyHaveOnePublicMethodTest extends TestCase
{
    public function testCreateRuleReturnsRule(): void
    {
        $group = new Group(
            name: 'handlers',
            flattenedIncludes: ['App\\Handler\\MyHandler'],
            flattenedExcludes: null,
            dependsOn: null,
            mustNotDependOn: null,
            extends: null,
            implements: null,
            isFinal: false,
            isReadonly: false,
            mustOnlyHaveOnePublicMethodName: 'handle',
        );
        $library = new ArchitectureLibrary([
            'handlers' => ['includes' => ['App\\Handler\\MyHandler'], 'must_only_have_one_public_method_named' => 'handle'],
        ]);

        $result = MustOnlyHaveOnePublicMethod::createRule($group, $library);

        self::assertInstanceOf(Rule::class, $result);
    }
}
