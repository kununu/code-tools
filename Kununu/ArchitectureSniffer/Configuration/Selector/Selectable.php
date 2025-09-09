<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Selector;

use PHPat\Selector\SelectorInterface;

interface Selectable
{
    public function getPHPatSelector(): SelectorInterface;
}
