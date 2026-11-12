<?php
declare(strict_types=1);

namespace Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules;

/**
 * The PHPAT assertion types the Architecture Sniffer builds rules for.
 *
 * PHPAT identifies an assertion by a plain string and does not validate it: a rule with an unknown
 * type is silently never checked. Keeping the values here means a typo cannot reach PHPAT.
 */
enum AssertionType: string
{
    case BeFinal = 'beFinal';
    case BeReadonly = 'beReadonly';
    case Depend = 'depend';
    case Extend = 'extend';
    case HaveOnlyOnePublicMethodNamed = 'haveOnlyOnePublicMethodNamed';
    case Implement = 'implement';
}
