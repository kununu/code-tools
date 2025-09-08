<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Helper;

use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\Rules;
use PHPat\Test\Builder\Rule as PHPatRule;

final readonly class RuleBuilder
{
    /**
     * @return iterable<PHPatRule>
     **/
    public static function getRules(Group $group, ArchitectureLibrary $library): iterable
    {
        if ($group->shouldExtend()) {
            yield Rules\MustExtend::createRule(
                $group,
                $library
            );
        }

        if ($group->shouldImplement()) {
            yield Rules\MustImplement::createRule(
                $group,
                $library
            );
        }

        if ($group->shouldBeFinal()) {
            yield Rules\MustBeFinal::createRule(
                $group,
                $library
            );
        }

        if ($group->shouldBeReadonly()) {
            yield Rules\MustBeReadonly::createRule(
                $group,
                $library
            );
        }

        if ($group->shouldDependOn()) {
            yield Rules\MustOnlyDependOn::createRule(
                $group,
                $library
            );
        }

        if ($group->shouldNotDependOn()) {
            yield Rules\MustNotDependOn::createRule(
                $group,
                $library
            );
        }

        if ($group->shouldOnlyHaveOnePublicMethodNamed()) {
            yield Rules\MustOnlyHaveOnePublicMethodNamed::createRule(
                $group,
                $library
            );
        }
    }
}
