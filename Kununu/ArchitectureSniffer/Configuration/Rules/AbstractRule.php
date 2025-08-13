<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Generator;

abstract readonly class AbstractRule implements Rule
{
    public function getPHPSelectors(Generator $selectors): Generator
    {
        foreach ($selectors as $selector) {
            yield $selector->getPHPatSelector();
        }
    }
}
