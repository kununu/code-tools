<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Configuration\Rules;

use Kununu\CodeTools\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Group;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\AssertionType;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\MustOnlyHaveOnePublicMethodNamed;
use LogicException;
use PHPat\Rule\Assertion\Constraint;
use PHPUnit\Framework\TestCase;

final class MustOnlyHaveOnePublicMethodNamedTest extends TestCase
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
            'handlers' => [
                'includes'                               => ['App\\Handler\\MyHandler'],
                'must_only_have_one_public_method_named' => 'handle',
            ],
        ]);

        $result = MustOnlyHaveOnePublicMethodNamed::createRule($group, $library);

        self::assertEquals(Constraint::Should, $result()->constraint);
        self::assertEquals(AssertionType::HaveOnlyOnePublicMethodNamed->value, $result()->assertionType);
    }

    public function testCreateRuleThrowsWhenMethodNameIsNull(): void
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
            mustOnlyHaveOnePublicMethodName: null,
        );
        $library = new ArchitectureLibrary([
            'handlers' => ['includes' => ['App\\Handler\\MyHandler']],
        ]);

        $this->expectException(LogicException::class);

        MustOnlyHaveOnePublicMethodNamed::createRule($group, $library);
    }
}
