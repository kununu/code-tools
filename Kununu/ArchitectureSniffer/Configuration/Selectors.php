<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration;

use InvalidArgumentException;
use JsonException;
use Kununu\ArchitectureSniffer\Configuration\Selector\ClassSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\NamespaceSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\Selectable;

enum Selectors: string
{
    case ClassSelector = ClassSelector::KEY;
    case InterfaceSelector = InterfaceClassSelector::KEY;
    case NamespaceSelector = NamespaceSelector::KEY;

    public static function getValidTypes(): array
    {
        return [
            self::ClassSelector->value,
            self::InterfaceSelector->value,
            self::NamespaceSelector->value,
        ];
    }

    /**
     * @throws JsonException
     */
    public static function findSelector(array $data, ?string $nameKey = null): Selectable
    {
        foreach (self::getValidTypes() as $type) {
            if (array_key_exists($type, $data)) {
                return self::createSelector($type, $data[$nameKey ?? $type], $data[$type]);
            }
        }

        throw new InvalidArgumentException($nameKey !== null ? "Missing selector for $nameKey" :
            'Missing selector in data ' . json_encode($data, JSON_THROW_ON_ERROR));
    }

    private static function createSelector(string $type, string $name, string $selection): Selectable
    {
        return match ($type) {
            self::ClassSelector->value     => new ClassSelector($name, $selection),
            self::InterfaceSelector->value => new InterfaceClassSelector($name, $selection),
            self::NamespaceSelector->value => new NamespaceSelector($name, $selection),
        };
    }
}
