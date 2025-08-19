<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Selector\ClassSelector;
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
        $includes = $library->getIncludesByGroup($groupName);
        $excludes = $library->getExcludesByGroup($groupName);

        foreach ($includes as $selectable) {
            if (!$selectable instanceof ClassSelector) {
                throw new InvalidArgumentException('Only classes can be final.');
            }
        }

        $rule = PHPat::rule()->classes(...self::getPHPSelectors($includes));

        $excludes = $excludes ? self::getPHPSelectors($excludes) : [];
        $excludes[] = Selector::isInterface();
        $rule = $rule->excluding(...$excludes);

        return $rule->shouldBeFinal()->because("$groupName must be final.");
    }
}
