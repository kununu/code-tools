<?php
declare(strict_types=1);

namespace Kununu\CodeTools\ArchitectureSniffer\Helper;

use Kununu\CodeTools\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Group;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\MustBeFinal;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\MustBeReadonly;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\MustExtend;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\MustImplement;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\MustNotDependOn;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\MustOnlyDependOn;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\MustOnlyHaveOnePublicMethod;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\MustOnlyHaveOnePublicMethodNamed;
use PHPat\Test\Builder\Rule as PHPatRule;

final readonly class RuleBuilder
{
    /** @return iterable<PHPatRule> */
    public static function getRules(Group $group, ArchitectureLibrary $library): iterable
    {
        if ($group->shouldExtend()) {
            yield MustExtend::createRule(
                $group,
                $library
            );
        }

        if ($group->shouldImplement()) {
            yield MustImplement::createRule(
                $group,
                $library
            );
        }

        if ($group->shouldBeFinal()) {
            yield MustBeFinal::createRule(
                $group,
                $library
            );
        }

        if ($group->shouldBeReadonly()) {
            yield MustBeReadonly::createRule(
                $group,
                $library
            );
        }

        if ($group->shouldDependOn()) {
            yield MustOnlyDependOn::createRule(
                $group,
                $library
            );
        }

        if ($group->shouldNotDependOn()) {
            yield MustNotDependOn::createRule(
                $group,
                $library
            );
        }

        if ($group->shouldOnlyHaveOnePublicMethodNamed()) {
            yield MustOnlyHaveOnePublicMethodNamed::createRule(
                $group,
                $library
            );
            yield MustOnlyHaveOnePublicMethod::createRule(
                $group,
                $library
            );
        }
    }
}
