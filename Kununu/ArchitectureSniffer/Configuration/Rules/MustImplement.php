<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Generator;
use PHPat\Selector\Selector;
use PHPat\Test\PHPat;

final readonly class MustImplement extends AbstractRule
{
    public function __construct(
        public Generator $selectables,
        public Generator $interfaces,
    ) {
    }

    public function getPHPatRule(string $groupName): \PHPat\Test\Builder\Rule
    {
        return PHPat::rule()
            ->classes(...self::getPHPSelectors($this->selectables))
            ->excluding(Selector::isInterface())
            ->shouldImplement()
            ->classes(...self::getPHPSelectors($this->interfaces))
            ->because("$groupName must implement interface.");
    }
}
