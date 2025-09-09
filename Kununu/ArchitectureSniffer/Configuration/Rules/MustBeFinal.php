<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use PHPat\Rule\Assertion\Declaration\ShouldBeFinal\ShouldBeFinal;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;

final readonly class MustBeFinal extends AbstractRule
{
    public static function createRule(
        Group $group,
        ArchitectureLibrary $library,
    ): Rule {
        return self::buildDependencyRule(
            group: $group,
            specificRule: ShouldBeFinal::class,
            because: "$group->name must be final.",
            extraExcludeSelectors: [Selector::isInterface()]
        );
    }
}
