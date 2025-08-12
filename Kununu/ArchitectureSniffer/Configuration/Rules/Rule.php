<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

interface Rule
{
    public function getPHPatRule(): \PHPat\Test\Builder\Rule;
}
