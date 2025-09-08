<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use PHPat\Rule\Assertion\Declaration\ShouldBeReadonly\ShouldBeReadonly;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;

final readonly class MustBeReadonly extends AbstractRule
{
    public static function createRule(
        Group $group,
        ArchitectureLibrary $library,
    ): Rule {
        return self::buildDependencyRule(
            group: $group,
            specificRule: ShouldBeReadonly::class,
            because: "$group->name must be read only.",
            extraExcludeSelectors: [Selector::isInterface()]
        );
    }
}
