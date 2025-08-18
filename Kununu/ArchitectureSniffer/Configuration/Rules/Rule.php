<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

interface Rule
{
    public function getPHPatRule(string $groupName): \PHPat\Test\Builder\Rule;

    public static function getPHPSelectors(array $selectors): array;
}
