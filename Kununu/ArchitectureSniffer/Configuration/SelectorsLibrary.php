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
                foreach ($this->resolveGroup($include, Group::INCLUDES_KEY) as $selectable) {
                    $resolvedIncludes[] = $selectable;
                }
            }
            $this->passedGroups = [$groupName];
            $resolvedExcludes = [];
            foreach ($attributes[Group::EXCLUDES_KEY] as $include) {
                foreach ($this->resolveGroup($include, Group::EXCLUDES_KEY) as $selectable) {
                    $resolvedIncludes[] = $selectable;
                }
            }
            $this->flattenedGroups[$groupName][Group::INCLUDES_KEY] = $resolvedIncludes;
            $this->flattenedGroups[$groupName][Group::EXCLUDES_KEY] = array_diff($resolvedExcludes, $resolvedIncludes);
            $this->flattenedGroups[$groupName][Group::DEPENDS_ON_KEY] = $attributes[Group::DEPENDS_ON_KEY] ?? null;
            $this->flattenedGroups[$groupName][Group::MUST_NOT_DEPEND_ON_KEY] = $attributes[Group::MUST_NOT_DEPEND_ON_KEY] ?? null;
            $this->flattenedGroups[$groupName][Group::FINAL_KEY] = $attributes[Group::FINAL_KEY] ?? null;
            $this->flattenedGroups[$groupName][Group::EXTENDS_KEY] = is_string($attributes[Group::EXTENDS_KEY]) ? [$attributes[Group::EXTENDS_KEY]] : null;
            $this->flattenedGroups[$groupName][Group::IMPLEMENTS_KEY] = $attributes[Group::IMPLEMENTS_KEY] ?? null;
            $this->flattenedGroups[$groupName][Group::MUST_ONLY_HAVE_ONE_PUBLIC_METHOD_NAMED_KEY] = $attributes[Group::MUST_ONLY_HAVE_ONE_PUBLIC_METHOD_NAMED_KEY] ?? null;
        }
    }

    private function resolveGroup(string $fqcnOrGroupName, string $key): Generator
    {
        if (array_key_exists($fqcnOrGroupName, $this->groups)) {
            if (in_array($fqcnOrGroupName, $this->passedGroups, true)) {
                return;
            }

            $this->passedGroups[] = $fqcnOrGroupName;

            foreach ($this->groups[$fqcnOrGroupName][$key] as $subFqcnOrGroupName) {
                yield from $this->resolveGroup($subFqcnOrGroupName, $key);
            }

            return;
        }

        yield $fqcnOrGroupName;
    }

    public function getOnlyPublicFunctionByGroup(string $groupName): ?string
    {
        if (!array_key_exists($groupName, $this->flattenedGroups)) {
            throw new InvalidArgumentException("Group '$groupName' does not exist.");
        }

        return $this->flattenedGroups[$groupName][Group::MUST_ONLY_HAVE_ONE_PUBLIC_METHOD_NAMED_KEY];
    }

    public function getIncludesByGroup(string $groupName): Generator
    {
        if (!array_key_exists($groupName, $this->flattenedGroups)) {
            throw new InvalidArgumentException("Group '$groupName' does not exist.");
        }

        foreach ($this->flattenedGroups[$groupName][Group::INCLUDES_KEY] as $fqcn) {
            yield $this->createSelectable($fqcn);
        }
    }

    public function getExcludesByGroup(string $groupName): Generator
    {
        if (!array_key_exists($groupName, $this->flattenedGroups)) {
            throw new InvalidArgumentException("Group '$groupName' does not exist.");
        }

        foreach ($this->flattenedGroups[$groupName][Group::EXCLUDES_KEY] as $fqcn) {
            yield $this->createSelectable($fqcn);
        }
    }

    public function getTargetByGroup(string $groupName, string $key): Generator
    {
        if (!array_key_exists($groupName, $this->flattenedGroups)) {
            throw new InvalidArgumentException("Group '$groupName' does not exist.");
        }

        yield from $this->getSelectors($this->flattenedGroups[$groupName][$key]);
    }

    public function groupHasKey(string $groupName, string $key): bool
    {
        if (!array_key_exists($groupName, $this->flattenedGroups)) {
            throw new InvalidArgumentException("Group '$groupName' does not exist.");
        }

        return array_key_exists($key, $this->flattenedGroups[$groupName]) && $this->flattenedGroups[$groupName][$key] !== null;
    }

    public function getTargetExcludesByGroup(string $groupName, string $key): Generator
    {
        if (!array_key_exists($groupName, $this->flattenedGroups)) {
            throw new InvalidArgumentException("Group '$groupName' does not exist.");
        }

        $includes = iterator_to_array($this->getTargetByGroup($groupName, $key));
        foreach ($this->getPotentialExcludesBy($this->flattenedGroups[$groupName][$key]) as $exclude) {
            if (in_array($exclude, $includes, true)) {
                continue;
            }
            yield $exclude;
        }
    }

    private function getSelector(string $fqcnOrGroup, string $key): Generator
    {
        if (array_key_exists($fqcnOrGroup, $this->flattenedGroups)) {
            foreach ($this->flattenedGroups[$fqcnOrGroup][$key] as $fqcn) {
                yield $this->createSelectable($fqcn);
            }

            return;
        }

        yield $this->createSelectable($fqcnOrGroup);
    }

    private function getSelectors(array $values): Generator
    {
        foreach ($values as $fqcnOrGroup) {
            yield from $this->getSelector($fqcnOrGroup, Group::INCLUDES_KEY);
        }
    }

    private function getPotentialExcludesBy(array $groups): array
    {
        $result = [];
        foreach ($groups as $groupName) {
            if (is_string($groupName) && array_key_exists($groupName, $this->flattenedGroups)) {
                if (!empty($this->flattenedGroups[$groupName][Group::EXCLUDES_KEY])) {
                    foreach ($this->flattenedGroups[$groupName][Group::EXCLUDES_KEY] as $exclude) {
                        $result[] = $exclude;
                    }
                }
            }
        }

        return array_unique($result);
    }

    private function createSelectable(string $fqcn): Selectable
    {
        return match (true) {
            interface_exists($fqcn) || str_ends_with($fqcn, 'Interface') => new InterfaceClassSelector($fqcn),
            str_ends_with($fqcn, '\\')                                   => new NamespaceSelector($fqcn),
            default                                                      => new ClassSelector($fqcn),
        };
    }
}
