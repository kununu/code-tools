<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
use Kununu\ArchitectureSniffer\Helper\SelectorBuilder;
use PHPat\Rule\Assertion\Relation\ShouldExtend\ShouldExtend;
use PHPat\Test\Builder\Rule;

final readonly class MustExtend extends AbstractRule
{
    public static function createRule(
        Group $group,
        ArchitectureLibrary $library,
    ): Rule {
        if ($group->extends === null) {
            throw self::getInvalidCallException(self::class, $group->name, 'extends');
        }

        self::checkIfNotInterfaceSelectors($group->flattenedIncludes);

        $targets = $library->resolveTargets($group, [$group->extends]);

        return self::buildDependencyRule(
            group: $group,
            specificRule: ShouldExtend::class,
            because: "$group->name should extend class.",
            targets: $targets,
            targetExcludes: $library->findTargetExcludes([$group->extends], $targets),
        );
    }

    /**
     * @param string[] $selectors
     */
    private static function checkIfNotInterfaceSelectors(array $selectors): void
    {
        foreach ($selectors as $selector) {
            if (SelectorBuilder::createSelectable($selector) instanceof InterfaceClassSelector) {
                throw new InvalidArgumentException(
                    "$selector cannot be used in the MustExtend rule, as it is an interface."
                );
            }
        }
    }
}
