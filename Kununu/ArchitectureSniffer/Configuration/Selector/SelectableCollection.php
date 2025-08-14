<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Selector;

use Generator;
use RuntimeException;

final class SelectableCollection
{
    private static ?self $singleton = null;

    /**
     * @param array<string, array<string>> $groups
     */
    public function __construct(private array $groups = [])
    {
    }

    private static function getSelf(): self
    {
        if (!isset(self::$singleton)) {
            self::$singleton = new self();
        }

        return self::$singleton;
    }

    /**
     * @param string[] $data
     */
    public static function fromArray(array $data, string $groupName): self
    {
        $collection = self::getSelf();

        $collection->groups[$groupName] = $data;

        return $collection;
    }

    public function getSelectablesByGroup(string $groupName): Generator
    {
        return self::toSelectable($this->groups[$groupName]);
    }

    /**
     * @param string|array<string> $fqcnListable
     */
    public static function toSelectable(string|array $fqcnListable): Generator
    {
        if (is_string($fqcnListable)) {
            return self::generateSelectable($fqcnListable);
        }

        foreach ($fqcnListable as $fqcn) {
            yield self::toSelectable($fqcn);
        }
    }

    public static function generateSelectable(string $fqcn): Generator
    {
        if (self::$singleton === null) {
            throw new RuntimeException('SelectableCollection is not initialized.');
        }

        return match (true) {
            array_key_exists($fqcn, self::$singleton->groups) => self::$singleton->getSelectablesByGroup($fqcn),
            str_ends_with($fqcn, '\\')                        => yield new NamespaceSelector($fqcn),
            str_ends_with($fqcn, 'Interface')                 => yield new InterfaceClassSelector($fqcn),
            default                                           => yield new ClassSelector($fqcn),
        };
    }
}
