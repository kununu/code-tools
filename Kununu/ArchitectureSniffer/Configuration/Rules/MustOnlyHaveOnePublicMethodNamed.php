<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\SelectorsLibrary;
use PHPat\Test\Builder\Rule as PHPatRule;
use PHPat\Test\PHPat;

final readonly class MustOnlyHaveOnePublicMethodNamed extends AbstractRule
{
    public static function createRule(
        string $groupName,
        SelectorsLibrary $library,
    ): PHPatRule {
        $includes = $library->getIncludesByGroup($groupName);
        $excludes = $library->getExcludesByGroup($groupName);
        $functionName = $library->getOnlyPublicFunctionByGroup($groupName);

        $rule = PHPat::rule()
            ->classes(...self::getPHPSelectors($includes));

        $excludes = self::getPHPSelectors($excludes);
        if ($excludes !== []) {
            $rule = $rule->excluding(...$excludes);
        }

        return $rule
            ->shouldHaveOnlyOnePublicMethodNamed($functionName)
            ->because("$groupName should only have one public method named $functionName");
    }
}
