<?php
declare(strict_types=1);

namespace Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules;

use Kununu\CodeTools\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Group;
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
