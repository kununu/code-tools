<?php
declare(strict_types=1);

namespace Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules;

use Kununu\CodeTools\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Group;
use PHPat\Test\Builder\Rule;

final readonly class MustOnlyHaveOnePublicMethodNamed extends AbstractRule
{
    public static function createRule(
        Group $group,
        ArchitectureLibrary $library,
    ): Rule {
        if ($group->mustOnlyHaveOnePublicMethodName === null) {
            throw self::getInvalidCallException(self::class, $group->name, 'mustOnlyHaveOnePublicMethodName');
        }

        return self::buildDependencyRule(
            group: $group,
            assertion: Assertion::ShouldHaveOnlyOnePublicMethodNamed,
            because: "$group->name should only have one public method named $group->mustOnlyHaveOnePublicMethodName.",
            ruleParams: ['name' => $group->mustOnlyHaveOnePublicMethodName, 'isRegex' => false],
        );
    }
}
