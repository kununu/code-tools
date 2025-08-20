<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\Selectable;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule as PHPatRule;
use PHPat\Test\PHPat;

final readonly class MustBeFinal extends AbstractRule
{
    public static function createRule(
        string $groupName,
        ArchitectureLibrary $library,
    ): PHPatRule {
        $includes = self::checkIfClassSelectors($library->getIncludesByGroup($groupName));
        $excludes = $library->getExcludesByGroup($groupName);

        $rule = PHPat::rule()->classes(...self::getPHPSelectors($includes));

        $excludes = self::getPHPSelectors($excludes);
        $excludes[] = Selector::isInterface();
        $rule = $rule->excluding(...$excludes);

        return $rule->shouldBeFinal()->because("$groupName must be final.");
    }

    /**
     * @param iterable<Selectable> $selectors
     *
     * @return iterable<Selectable>
     */
    private static function checkIfClassSelectors(iterable $selectors): iterable
    {
        foreach ($selectors as $selector) {
            if ($selector instanceof InterfaceClassSelector) {
                $name = $selector->interface;
                throw new InvalidArgumentException("$name must be a class selector for rule MustBeFinal.");
            }
            yield $selector;
        }
    }
}
