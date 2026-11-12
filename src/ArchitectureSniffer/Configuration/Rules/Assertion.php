<?php
declare(strict_types=1);

namespace Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules;

use PHPat\Rule\Assertion\Constraint;

// The PHPAT assertions the Architecture Sniffer builds rules for.
//
// PHPAT identifies an assertion by a constraint plus a plain string type and validates neither:
// a rule with an unknown type, or with a constraint its type does not support, is silently never
// checked. Each case here fixes a pair PHPAT does support, so an invalid one cannot be written.
enum Assertion
{
    case CanOnlyDependOn;
    case ShouldBeFinal;
    case ShouldBeReadonly;
    case ShouldExtend;
    case ShouldHaveOnlyOnePublicMethodNamed;
    case ShouldImplement;
    case ShouldNotDependOn;

    public function constraint(): Constraint
    {
        return match ($this) {
            self::CanOnlyDependOn   => Constraint::CanOnly,
            self::ShouldNotDependOn => Constraint::ShouldNot,
            self::ShouldBeFinal,
            self::ShouldBeReadonly,
            self::ShouldExtend,
            self::ShouldHaveOnlyOnePublicMethodNamed,
            self::ShouldImplement   => Constraint::Should,
        };
    }

    public function type(): string
    {
        return match ($this) {
            self::CanOnlyDependOn, self::ShouldNotDependOn => 'depend',
            self::ShouldBeFinal                            => 'beFinal',
            self::ShouldBeReadonly                         => 'beReadonly',
            self::ShouldExtend                             => 'extend',
            self::ShouldHaveOnlyOnePublicMethodNamed       => 'haveOnlyOnePublicMethodNamed',
            self::ShouldImplement                          => 'implement',
        };
    }
}
