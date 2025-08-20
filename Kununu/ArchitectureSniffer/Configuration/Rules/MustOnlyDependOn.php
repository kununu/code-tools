<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use iterable;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\SelectorsLibrary;
use PHPat\Test\Builder\Rule as PHPatRule;
use PHPat\Test\PHPat;

final readonly class MustOnlyDependOn extends AbstractRule
{
    public static function createRule(
        string $groupName,
        SelectorsLibrary $library,
    ): PHPatRule {
        $includes = $library->getIncludesByGroup($groupName);
        $excludes = $library->getExcludesByGroup($groupName);
        $onlyDependOn = $library->getTargetByGroup($groupName, Group::DEPENDS_ON_KEY);
        $onlyDependOnExcludes = $library->getTargetExcludesByGroup($groupName, Group::DEPENDS_ON_KEY);

        $rule = PHPat::rule()->classes(...self::getPHPSelectors($includes));
        if ($excludes instanceof iterable) {
            $rule = $rule->excluding(...self::getPHPSelectors($excludes));
        }

        $rule = $rule->canOnlyDependOn()->classes(...self::getPHPSelectors($onlyDependOn));
        if ($onlyDependOnExcludes instanceof iterable) {
            $rule = $rule->excluding(...self::getPHPSelectors($onlyDependOnExcludes));
        }

        return $rule->because("$groupName must only depend on allowed dependencies.");
    }
}
