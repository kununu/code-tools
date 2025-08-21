<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
use Kununu\ArchitectureSniffer\Helper\SelectorBuilder;
use PHPat\Rule\Assertion\Declaration\ShouldBeFinal\ShouldBeFinal;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;

final readonly class MustBeFinal extends AbstractRule
{
    public static function createRule(
        Group $group,
        ArchitectureLibrary $library,
    ): Rule {
        self::checkIfClassSelectors($group->flattenedIncludes);

        return self::buildDependencyRule(
            group: $group,
            specificRule: ShouldBeFinal::class,
            because: "$group->name must be final.",
            extraExcludeSelectors: [Selector::isInterface()]
        );
    }

    /**
     * @param string[] $selectors
     */
    private static function checkIfClassSelectors(array $selectors): void
    {
        foreach ($selectors as $selector) {
            if (SelectorBuilder::createSelectable($selector) instanceof InterfaceClassSelector) {
                throw new InvalidArgumentException("$selector must be a class selector for rule MustBeFinal.");
            }
        }
    }
}
