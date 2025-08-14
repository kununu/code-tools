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
        foreach ($groups as $groupName => $attributes) {
            $this->passedGroups = [$groupName];
            $resolvedIncludes = [];
            foreach ($attributes[Group::INCLUDES_KEY] as $include) {
                foreach ($this->resolveInclude($include) as $selectable) {
                    $resolvedIncludes[] = $selectable;
                }
            }
            $this->flattenedGroups[$groupName] = $resolvedIncludes;
        }
    }

    private function resolveInclude(string $fqcnOrGroupName): Generator
    {
        if (array_key_exists($fqcnOrGroupName, $this->groups)) {
            if (in_array($fqcnOrGroupName, $this->passedGroups, true)) {
                return;
            }

            $this->passedGroups[] = $fqcnOrGroupName;

            foreach ($this->groups[$fqcnOrGroupName] as $subFqcnOrGroupName) {
                yield from $this->resolveInclude($subFqcnOrGroupName);
            }

            return;
        }

        yield $fqcnOrGroupName;
    }

    public function getSelector(string $fqcnOrGroup): Generator
    {
        if (array_key_exists($fqcnOrGroup, $this->flattenedGroups)) {
            foreach ($this->flattenedGroups[$fqcnOrGroup] as $fqcn) {
                yield $this->createSelectable($fqcn);
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
            yield $this->createSelectable($fqcn);
        }
    }

    public function getSelectors(array $values): Generator
    {
        foreach ($values as $fqcnOrGroup) {
            yield from $this->getSelector($fqcnOrGroup);
        }
    }

    private function createSelectable(string $fqcn): Selectable
    {
        return match (true) {
            str_ends_with($fqcn, '\\')                        => new NamespaceSelector($fqcn),
            str_ends_with($fqcn, 'Interface')                 => new InterfaceClassSelector($fqcn),
            default                                           => new ClassSelector($fqcn),
        };
    }
}
