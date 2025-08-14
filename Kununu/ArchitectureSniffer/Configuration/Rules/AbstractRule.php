<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Generator;
use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Selector\Selectable;

abstract readonly class AbstractRule implements Rule
{
    public static function getPHPSelectors(iterable $selectors): Generator
    {
        foreach ($selectors as $selector) {
            if (!$selector instanceof Selectable) {
                throw new InvalidArgumentException(
                    'Only Selectable instances can be used in rules.'
                );
            }
            yield $selector->getPHPatSelector();
        }
    }
}
