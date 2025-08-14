<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Generator;
use PHPat\Selector\Selector;
use PHPat\Test\PHPat;

final readonly class MustOnlyDependOn extends AbstractRule
{
    public function __construct(
        public Generator $selectables,
        public Generator $dependencies,
    ) {
    }

    public function getPHPatRule(string $groupName): \PHPat\Test\Builder\Rule
    {
        return PHPat::rule()
            ->classes(...self::getPHPSelectors($this->selectables))
            ->canOnlyDependOn()
            ->classes(
                Selector::classname('/^\\\\*[^\\\\]+$/', true),
                ...self::getPHPSelectors($this->dependencies)
            )
            ->because("$groupName has dependencies outside the allowed list.");
    }
}
