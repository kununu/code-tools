<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration;

use Generator;
use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Selector\ClassSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\NamespaceSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\Selectable;

final class ArchitectureLibrary
{
    /** @var array<string, array<string, string[]|string|bool|null>> */
    private array $flattenedGroups = [];
    /** @var array<string> */
    private array $passedGroups = [];

    /**
     * @param array<string, array<string, string[]|string|bool>> $groups
     */
    public function __construct(private readonly array $groups)
    {
        foreach ($groups as $groupName => $attributes) {
            $this->flattenedGroups[$groupName] = $attributes;

            if (array_key_exists(Group::EXTENDS_KEY, $attributes)) {
                if (!is_string($attributes[Group::EXTENDS_KEY])) {
                    throw new InvalidArgumentException("Group '$groupName' 'extends' key must be a string.");
                }

                $this->flattenedGroups[$groupName][Group::EXTENDS_KEY] = [$attributes[Group::EXTENDS_KEY]];
            }

            $this->passedGroups = [$groupName];
            $resolvedIncludes = [];
            if (!array_key_exists(Group::INCLUDES_KEY, $attributes)) {
                throw new InvalidArgumentException("Group '$groupName' must have an 'includes' key.");
            }
            if (!is_array($attributes[Group::INCLUDES_KEY])) {
                throw new InvalidArgumentException("Group '$groupName' 'includes' key must be an array.");
            }
            foreach ($attributes[Group::INCLUDES_KEY] as $include) {
                foreach ($this->resolveGroup($include, Group::INCLUDES_KEY) as $selectable) {
                    $resolvedIncludes[] = $selectable;
                }
            }
            $this->flattenedGroups[$groupName][Group::INCLUDES_KEY] = $resolvedIncludes;

            $this->passedGroups = [$groupName];
            $resolvedExcludes = [];
            if (array_key_exists(Group::EXCLUDES_KEY, $attributes)) {
                if (!is_array($attributes[Group::EXCLUDES_KEY])) {
                    throw new InvalidArgumentException("Group '$groupName' 'excludes' key must be an array.");
                }
                foreach ($attributes[Group::EXCLUDES_KEY] as $excludes) {
                    foreach ($this->resolveGroup($excludes, Group::EXCLUDES_KEY) as $selectable) {
                        $resolvedIncludes[] = $selectable;
                    }
                }
                $this->flattenedGroups[$groupName][Group::EXCLUDES_KEY]
                    = array_diff($resolvedExcludes, $resolvedIncludes);
            }
        }
    }

    private function resolveGroup(string $fqcnOrGroupName, string $key): Generator
    {
        if (array_key_exists($fqcnOrGroupName, $this->groups)) {
            if (in_array($fqcnOrGroupName, $this->passedGroups, true)) {
                return;
            }

            $this->passedGroups[] = $fqcnOrGroupName;

            if (!is_array($this->groups[$fqcnOrGroupName][$key])) {
                throw new InvalidArgumentException(
                    "Group '$fqcnOrGroupName' must have a non-empty '$key' key."
                );
            }
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

        $funtionName = $this->flattenedGroups[$groupName][Group::MUST_ONLY_HAVE_ONE_PUBLIC_METHOD_NAMED_KEY];

        if (!is_string($funtionName) && $funtionName !== null) {
            $key = Group::MUST_ONLY_HAVE_ONE_PUBLIC_METHOD_NAMED_KEY;
            throw new InvalidArgumentException(
                "Group '$groupName' must have a string value for '$key' key."
            );
        }

        return $funtionName;
    }

    public function getIncludesByGroup(string $groupName): Generator
    {
        if (!array_key_exists($groupName, $this->flattenedGroups)) {
            throw new InvalidArgumentException("Group '$groupName' does not exist.");
        }

        if (!is_array($this->flattenedGroups[$groupName][Group::INCLUDES_KEY])) {
            throw new InvalidArgumentException("Group '$groupName' 'includes' key must be an array.");
        }

        yield from $this->getSelectors($this->flattenedGroups[$groupName][Group::INCLUDES_KEY]);
    }

    public function getExcludesByGroup(string $groupName): Generator
    {
        if (!array_key_exists($groupName, $this->flattenedGroups)) {
            throw new InvalidArgumentException("Group '$groupName' does not exist.");
        }

        if (array_key_exists(Group::EXCLUDES_KEY, $this->flattenedGroups[$groupName])
            && !is_array($this->flattenedGroups[$groupName][Group::EXCLUDES_KEY])) {
            throw new InvalidArgumentException("Group '$groupName' 'excludes' key must be an array.");
        }

        yield from $this->getSelectors($this->flattenedGroups[$groupName][Group::EXCLUDES_KEY] ?? []);
    }

    public function getTargetByGroup(string $groupName, string $key): Generator
    {
        if (!array_key_exists($groupName, $this->flattenedGroups)) {
            throw new InvalidArgumentException("Group '$groupName' does not exist.");
        }

        if ($key === Group::DEPENDS_ON_KEY) {
            if (!is_array($this->flattenedGroups[$groupName][Group::INCLUDES_KEY])) {
                throw new InvalidArgumentException("Group '$groupName' 'includes' key must be an array.");
            }

            yield from $this->getSelectors($this->flattenedGroups[$groupName][Group::INCLUDES_KEY]);

            if (
                array_key_exists(Group::EXTENDS_KEY, $this->flattenedGroups[$groupName])
                && is_array($this->flattenedGroups[$groupName][Group::EXTENDS_KEY])
            ) {
                yield from $this->getSelectors($this->flattenedGroups[$groupName][Group::EXTENDS_KEY]);
            }

            if (
                array_key_exists(Group::IMPLEMENTS_KEY, $this->flattenedGroups[$groupName])
                && is_array($this->flattenedGroups[$groupName][Group::IMPLEMENTS_KEY])
            ) {
                yield from $this->getSelectors($this->flattenedGroups[$groupName][Group::IMPLEMENTS_KEY]);
            }
        }

        if (!is_array($this->flattenedGroups[$groupName][$key])) {
            throw new InvalidArgumentException(
                "Property '$key' of group '$groupName' must be an array."
            );
        }

        yield from $this->getSelectors($this->flattenedGroups[$groupName][$key]);
    }

    public function groupHasKey(string $groupName, string $key): bool
    {
        if (!array_key_exists($groupName, $this->flattenedGroups)) {
            throw new InvalidArgumentException("Group '$groupName' does not exist.");
        }

        return array_key_exists($key, $this->flattenedGroups[$groupName]);
    }

    public function getTargetExcludesByGroup(string $groupName, string $key): Generator
    {
        if (!array_key_exists($groupName, $this->flattenedGroups)) {
            throw new InvalidArgumentException("Group '$groupName' does not exist.");
        }

        $includes = iterator_to_array($this->getTargetByGroup($groupName, $key));
        $target = $this->flattenedGroups[$groupName][$key] ?? [];

        if (!is_array($target)) {
            throw new InvalidArgumentException(
                "Property '$key' of group '$groupName' must be an array."
            );
        }

        foreach ($this->getPotentialExcludesBy($target) as $exclude) {
            if (in_array($exclude, $includes, true)) {
                continue;
            }
            yield $exclude;
        }
    }

    private function getSelectorFromIncludes(string $fqcnOrGroup): Generator
    {
        if (array_key_exists($fqcnOrGroup, $this->flattenedGroups)) {
            if (!is_array($this->flattenedGroups[$fqcnOrGroup][Group::INCLUDES_KEY])) {
                throw new InvalidArgumentException(
                    "Group '$fqcnOrGroup' 'includes' key must be an array."
                );
            }

            foreach ($this->flattenedGroups[$fqcnOrGroup][Group::INCLUDES_KEY] as $fqcn) {
                yield $this->createSelectable($fqcn);
            }

            return;
        }

        yield $this->createSelectable($fqcnOrGroup);
    }

    /**
     * @param string[] $values
     *
     * @return Generator<Selectable>
     */
    private function getSelectors(array $values): Generator
    {
        foreach ($values as $fqcnOrGroup) {
            yield from $this->getSelectorFromIncludes($fqcnOrGroup);
        }
    }

    /**
     * @param string[] $groups
     *
     * @return string[]
     */
    private function getPotentialExcludesBy(array $groups): array
    {
        $result = [];
        foreach ($groups as $groupName) {
            if (array_key_exists($groupName, $this->flattenedGroups)) {
                if (array_key_exists(Group::EXCLUDES_KEY, $this->flattenedGroups[$groupName])
                    && !is_array($this->flattenedGroups[$groupName][Group::EXCLUDES_KEY])) {
                    throw new InvalidArgumentException(
                        "Group '$groupName' 'excludes' key must be an array."
                    );
                }
                foreach ($this->flattenedGroups[$groupName][Group::EXCLUDES_KEY] ?? [] as $exclude) {
                    $result[] = $exclude;
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
