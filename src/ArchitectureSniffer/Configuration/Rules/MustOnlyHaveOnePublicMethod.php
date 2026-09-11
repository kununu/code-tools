<?php
declare(strict_types=1);

namespace Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules;

use Kununu\CodeTools\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Group;
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
