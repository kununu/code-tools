<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\SelectorsLibrary;
use PHPat\Test\Builder\Rule as PHPatRule;
use PHPat\Test\PHPat;

final readonly class MustNotDependOn extends AbstractRule
{
    public static function createRule(
        string $groupName,
        SelectorsLibrary $library,
    ): PHPatRule {
        $includes = $library->getIncludesByGroup($groupName);
        $excludes = $library->getExcludesByGroup($groupName);
        $mostNotDependOn = $library->getTargetByGroup($groupName, Group::MUST_NOT_DEPEND_ON_KEY);
        $mostNotDependOnExcludes = $library->getTargetExcludesByGroup($groupName, Group::MUST_NOT_DEPEND_ON_KEY);

        $rule = PHPat::rule()->classes(...self::getPHPSelectors($includes));

        if ($excludes !== null) {
            $rule = $rule->excluding(...self::getPHPSelectors($excludes));
        }

        $rule = $rule->shouldNotDependOn()->classes(...self::getPHPSelectors($mostNotDependOn));

        if ($mostNotDependOnExcludes !== null) {
            $rule = $rule->excluding(...self::getPHPSelectors($mostNotDependOnExcludes));
        }

        return $rule->because("$groupName must not depend on forbidden dependencies.");
    }
}
