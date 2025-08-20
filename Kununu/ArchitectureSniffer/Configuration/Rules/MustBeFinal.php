<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
use Kununu\ArchitectureSniffer\Configuration\SelectorsLibrary;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule as PHPatRule;
use PHPat\Test\PHPat;

final readonly class MustBeFinal extends AbstractRule
{
    public static function createRule(
        string $groupName,
        SelectorsLibrary $library,
    ): PHPatRule {
        $includes = self::checkIfClassSelectors($library->getIncludesByGroup($groupName));
        $excludes = $library->getExcludesByGroup($groupName);

        $rule = PHPat::rule()->classes(...self::getPHPSelectors($includes));

        $excludes = self::getPHPSelectors($excludes);
        $excludes[] = Selector::isInterface();
        $rule = $rule->excluding(...$excludes);

        return $rule->shouldBeFinal()->because("$groupName must be final.");
    }

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
