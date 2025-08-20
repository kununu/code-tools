<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\SelectorsLibrary;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule as PHPatRule;
use PHPat\Test\PHPat;

final readonly class MustImplement extends AbstractRule
{
    public static function createRule(
        string $groupName,
        SelectorsLibrary $library,
    ): PHPatRule {
        $includes = $library->getIncludesByGroup($groupName);
        $excludes = $library->getExcludesByGroup($groupName);
        $interfaces = self::checkIfInterfaceSelectors($library->getTargetByGroup($groupName, Group::INCLUDES_KEY));
        $interfacesExcludes = $library->getTargetExcludesByGroup($groupName, Group::INCLUDES_KEY);

        $rule = PHPat::rule()->classes(...self::getPHPSelectors($includes));

        $excludeSelectors = $excludes ? self::getPHPSelectors($excludes) : [];
        $excludeSelectors[] = Selector::isInterface();
        $rule = $rule->excluding(...$excludeSelectors);

        $rule = $rule->shouldImplement()->classes(...self::getPHPSelectors($interfaces));
        if ($interfacesExcludes !== null) {
            $rule = $rule->excluding(...self::getPHPSelectors($interfacesExcludes));
        }

        return $rule->because("$groupName must implement interface.");
    }

    private static function checkIfInterfaceSelectors(iterable $selectors): iterable
    {
        foreach ($selectors as $selector) {
            if (!str_ends_with($selector, 'Interface')) {
                throw new InvalidArgumentException("$selector cannot be used in the MustImplement rule, as it is not an interface.");
            }
            yield $selector;
        }
    }
}
