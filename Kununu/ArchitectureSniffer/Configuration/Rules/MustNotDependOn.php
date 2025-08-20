<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use PHPat\Test\Builder\AssertionStep;
use PHPat\Test\Builder\Rule as PHPatRule;
use PHPat\Test\Builder\TargetStep;

final readonly class MustNotDependOn extends AbstractRule
{
    public static function createRule(
        string $groupName,
        ArchitectureLibrary $library,
    ): PHPatRule {
        return self::buildDependencyRule(
            $groupName,
            $library,
            static function(AssertionStep $rule): TargetStep {
                return $rule->shouldNotDependOn();
            },
            Group::MUST_NOT_DEPEND_ON_KEY,
            "$groupName must not depend on forbidden dependencies.",
        );
    }
}
