<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration;

use Generator;
use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Selector\ClassSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\NamespaceSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\Selectable;

final class SelectorsLibrary
{
    private array $flattenedGroups = [];
    private array $passedGroups = [];

    public function __construct(private array $groups)
    {
        foreach ($groups as $groupName => $includes) {
            $this->passedGroups = [$groupName];
            $resolvedIncludes = [];
            foreach ($includes as $include) {
                foreach ($this->resolveGroup($include) as $selectable) {
                    $resolvedIncludes[] = $selectable;
                }
            }
            $this->flattenedGroups[$groupName] = $resolvedIncludes;
        }
    }

    public function getSelector(string $fqcnOrGroup): Generator
    {
        if (array_key_exists($fqcnOrGroup, $this->flattenedGroups)) {
            foreach ($this->flattenedGroups[$fqcnOrGroup] as $fqcn) {
                yield from $this->createSelectable($fqcn);
            }
        }

        return [$this->createSelectable($fqcnOrGroup)];
    }

    public function getSelectorsFromGroup(string $groupName): Generator
    {
        if (!array_key_exists($groupName, $this->flattenedGroups)) {
            throw new InvalidArgumentException("Group '$groupName' does not exist.");
        }

        foreach ($this->flattenedGroups[$groupName] as $fqcn) {
            yield from $this->createSelectable($fqcn);
        }
    }

    public function getSelectors(array $values): Generator
    {
        foreach ($values as $fqcnOrGroup) {
            yield from $this->getSelector($fqcnOrGroup);
        }
    }

    private function resolveGroup(string $fqcnOrGroupName): Generator
    {
        if (array_key_exists($fqcnOrGroupName, $this->groups)) {
            if (in_array($fqcnOrGroupName, $this->passedGroups, true)) {
                return;
            }

            foreach ($this->groups[$fqcnOrGroupName] as $subFqcnOrGroupName) {
                yield from $this->resolveGroup($subFqcnOrGroupName);
            }
        }

        yield $this->createSelectable($fqcnOrGroupName);
    }

    private function createSelectable($fqcn): Selectable
    {
        return match (true) {
            str_ends_with($fqcn, '\\')                        => new NamespaceSelector($fqcn),
            str_ends_with($fqcn, 'Interface')                 => new InterfaceClassSelector($fqcn),
            default                                           => new ClassSelector($fqcn),
        };
    }
}
