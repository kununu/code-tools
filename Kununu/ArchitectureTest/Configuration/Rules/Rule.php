<?php
declare(strict_types=1);

namespace Kununu\ArchitectureTest\Configuration\Rules;

interface Rule
{
    public function getPHPatRule(): \PHPat\Test\Builder\Rule;
}
