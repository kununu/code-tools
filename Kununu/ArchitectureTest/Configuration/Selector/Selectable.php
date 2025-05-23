<?php
declare(strict_types=1);

namespace Kununu\ArchitectureTest\Configuration\Selector;

use PHPat\Selector\SelectorInterface;

interface Selectable
{
    public function getPHPatSelector(): SelectorInterface;

    public function getName(): string;
}
