<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\SelectorsLibrary;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\AssertionStep;
use PHPat\Test\Builder\Rule as PHPatRule;
use PHPat\Test\Builder\TargetStep;

final readonly class MustOnlyDependOn extends AbstractRule
{
    public static function createRule(
        string $groupName,
        SelectorsLibrary $library,
    ): PHPatRule {
        return self::buildDependencyRule(
            $groupName,
            $library,
            static function(AssertionStep $rule): TargetStep {
                return $rule->canOnlyDependOn();
            },
            "$groupName must only depend on allowed dependencies.",
            Group::DEPENDS_ON_KEY,
            [Selector::classname('/^\\*[^\\]+$/', true)],
        );
    }
}
