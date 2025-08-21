<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
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
