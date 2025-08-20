<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

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
        $excludes = self::getPHPSelectors($excludes);
        if ($excludes !== []) {
            $rule = $rule->excluding(...$excludes);
        }

        $rule = $rule->canOnlyDependOn()->classes(...self::getPHPSelectors($onlyDependOn));

        $onlyDependOnExcludes = self::getPHPSelectors($onlyDependOnExcludes);
        if ($onlyDependOnExcludes !== []) {
            $rule = $rule->excluding(...$onlyDependOnExcludes);
        }

        return $rule->because("$groupName must only depend on allowed dependencies.");
    }
}
