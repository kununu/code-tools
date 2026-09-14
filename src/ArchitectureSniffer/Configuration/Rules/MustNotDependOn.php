<?php
declare(strict_types=1);

namespace Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules;

use Kununu\CodeTools\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Group;
use PHPat\Rule\Assertion\Relation\ShouldNotDepend\ShouldNotDepend;
use PHPat\Test\Builder\Rule;

final readonly class MustNotDependOn extends AbstractRule
{
    public static function createRule(
        Group $group,
        ArchitectureLibrary $library,
    ): Rule {
        if ($group->mustNotDependOn === null) {
            throw self::getInvalidCallException(self::class, $group->name, 'mustNotDependOn');
        }

        $targets = $library->resolveTargets($group, $group->mustNotDependOn);

        return self::buildDependencyRule(
            group: $group,
            specificRule: ShouldNotDepend::class,
            because: "$group->name must not depend on forbidden dependencies.",
            targets: $targets,
            targetExcludes: $library->findTargetExcludes($group->mustNotDependOn, $targets),
        );
    }
}
