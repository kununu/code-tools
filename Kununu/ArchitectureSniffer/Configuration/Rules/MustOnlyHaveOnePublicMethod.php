<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use PHPat\Rule\Assertion\Declaration\ShouldHaveOnlyOnePublicMethodNamed\ShouldHaveOnlyOnePublicMethodNamed;
use PHPat\Test\Builder\Rule;

final readonly class MustOnlyHaveOnePublicMethod extends AbstractRule
{
    public static function createRule(
        Group $group,
        ArchitectureLibrary $library,
    ): Rule {
        return self::buildDependencyRule(
            group: $group,
            specificRule: ShouldHaveOnlyOnePublicMethodNamed::class,
            because: "$group->name should only have one public method named $group->mustOnlyHaveOnePublicMethodName.",
        );
    }
}
