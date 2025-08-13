<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Generator;

interface Rule
{
    public function getPHPatRule(string $groupName): \PHPat\Test\Builder\Rule;

    public function getPHPSelectors(Generator $selectors): Generator;
}
