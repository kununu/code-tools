<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use PHPat\Rule\Assertion\Declaration\ShouldHaveOnlyOnePublicMethodNamed\ShouldHaveOnlyOnePublicMethodNamed;
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
            specificRule: ShouldHaveOnlyOnePublicMethodNamed::class,
            because: "$group->name should only have one public method named $group->mustOnlyHaveOnePublicMethodName.",
            ruleParams: ['name' => $group->mustOnlyHaveOnePublicMethodName, 'isRegex' => false],
        );
    }
}
