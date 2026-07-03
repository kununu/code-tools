<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Helper;

use InvalidArgumentException;

final readonly class TypeChecker
{
    public static function isArrayKeysOfStrings(mixed $arr): bool
    {
        if (!is_array($arr)) {
            return false;
        }

        return array_all(array_keys($arr), static fn($key) => is_string($key));
    }

    public static function isArrayOfStrings(mixed $arr): bool
    {
        if (!is_array($arr)) {
            return false;
        }

        return array_all($arr, static fn($item) => is_string($item));
    }

    /**
     * @return string[]
     */
    public static function castArrayOfStrings(mixed $arrayOfStrings): array
    {
        if (self::isArrayOfStrings($arrayOfStrings) === false) {
            throw new InvalidArgumentException('Input must be an array of strings.');
        }

        return $arrayOfStrings;
    }
}
