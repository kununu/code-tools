<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

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
        $interfaces = $library->getTargetByGroup($groupName, Group::INCLUDES_KEY);
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
}
