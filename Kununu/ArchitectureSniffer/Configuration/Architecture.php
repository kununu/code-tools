<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration;

use PHPat\Test\Builder\Rule as PHPatRule;

final readonly class Architecture
{
    /**
     * @param array<string, array<string, array<string, string[]|string|bool>>> $data
     *
     * @return iterable<PHPatRule>
     */
    public static function fromArray(array $data): iterable
    {
    }
}
