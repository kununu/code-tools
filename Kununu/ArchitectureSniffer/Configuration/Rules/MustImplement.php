<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\Selector\ClassSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\NamespaceSelector;
use Kununu\ArchitectureSniffer\Helper\SelectorBuilder;
use PHPat\Rule\Assertion\Relation\ShouldImplement\ShouldImplement;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;

final readonly class MustImplement extends AbstractRule
{
    public static function createRule(
        Group $group,
        ArchitectureLibrary $library,
    ): Rule {
        if ($group->implements === null) {
            throw self::getInvalidCallException(self::class, $group->name, 'implements');
        }

        $targets = $library->resolveTargets($group, $group->implements);
        self::checkIfInterfaceSelectors($targets);

        return self::buildDependencyRule(
            group: $group,
            specificRule: ShouldImplement::class,
            because: "$group->name must implement interface.",
            targets: $targets,
            targetExcludes: $library->findTargetExcludes($group->implements, $targets),
            extraExcludeSelectors: [Selector::isInterface()],
        );
    }

    /**
     * @param string[] $selectors
     */
    private static function checkIfInterfaceSelectors(iterable $selectors): void
    {
        foreach ($selectors as $selector) {
            if (SelectorBuilder::createSelectable($selector) instanceof ClassSelector
                || SelectorBuilder::createSelectable($selector) instanceof NamespaceSelector) {
                throw new InvalidArgumentException(
                    "$selector cannot be used in the MustImplement rule, as it is not an interface."
                );
            }
        }
    }
}
