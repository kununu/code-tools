<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Selector\Selectable;

abstract readonly class AbstractRule implements Rule
{
    public static function getPHPSelectors(iterable $selectors): array
    {
        $result = [];
        foreach ($selectors as $selector) {
            if (!$selector instanceof Selectable) {
                throw new InvalidArgumentException(
                    'Only Selectable instances can be used in rules.'
                );
            }
            $result[] = $selector->getPHPatSelector();
        }

        return $result;
    }
}
