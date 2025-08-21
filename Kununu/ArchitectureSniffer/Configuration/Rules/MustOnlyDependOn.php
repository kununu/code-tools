<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use PHPat\Rule\Assertion\Relation\CanOnlyDepend\CanOnlyDepend;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;

final readonly class MustOnlyDependOn extends AbstractRule
{
    public static function createRule(
        Group $group,
        ArchitectureLibrary $library,
    ): Rule {
        if ($group->dependsOn === null) {
            throw self::getInvalidCallException(self::class, $group->name, 'dependsOn');
        }

        $targets = $library->resolveTargets($group, $group->dependsOn);

        return self::buildDependencyRule(
            group: $group,
            specificRule: CanOnlyDepend::class,
            because: "$group->name must only depend on allowed dependencies.",
            targets: $targets,
            targetExcludes: $library->findTargetExcludes($group->dependsOn, $targets),
            extraTargetSelectors: [Selector::classname('/^\\\\*[^\\\\]+$/', true)],
        );
    }
}
