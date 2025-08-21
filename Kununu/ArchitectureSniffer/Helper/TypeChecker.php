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

        foreach (array_keys($arr) as $key) {
            if (!is_string($key)) {
                return false;
            }
        }

        return true;
    }

    public static function isArrayOfStrings(mixed $arr): bool
    {
        if (!is_array($arr)) {
            return false;
        }

        foreach ($arr as $item) {
            if (!is_string($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $arrayOfStrings
     *
     * @throws InvalidArgumentException if any element is not a string
     *
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
