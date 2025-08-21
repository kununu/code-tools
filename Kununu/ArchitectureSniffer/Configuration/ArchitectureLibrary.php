<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Helper\GroupFlattener;
use Kununu\ArchitectureSniffer\Helper\TypeChecker;

final class ArchitectureLibrary
{
    /** @var array<string, Group> */
    private array $groups = [];

    /**
     * @param array<string, mixed> $groups
     */
    public function __construct(array $groups)
    {
        GroupFlattener::$groups = $groups;

        foreach ($groups as $groupName => $attributes) {
            if (!TypeChecker::isArrayOfStrings($attributes[Group::INCLUDES_KEY])) {
                throw new InvalidArgumentException(
                    "Group '$groupName' includes must be an array of strings."
                );
            }

            $flattenedIncludes = GroupFlattener::flattenIncludes($groupName, $attributes[Group::INCLUDES_KEY]);
            $flattenedExcludes = GroupFlattener::flattenExcludes(
                groupName: $groupName,
                excludes: $attributes[Group::EXCLUDES_KEY]
                    && TypeChecker::isArrayOfStrings($attributes[Group::EXCLUDES_KEY]) ?
                    $attributes[Group::EXCLUDES_KEY] : [],
                flattenedIncludes: $flattenedIncludes
            );

            $this->groups[$groupName] = Group::buildFrom(
                groupName: $groupName,
                flattenedIncludes: $flattenedIncludes,
                targetAttributes: $attributes,
                flattenedExcludes: $flattenedExcludes,
            );
        }
    }

    public function getGroupBy(string $groupName): Group
    {
        if (!array_key_exists($groupName, $this->groups)) {
            throw new InvalidArgumentException("Group '$groupName' does not exist.");
        }

        return $this->groups[$groupName];
    }

    /**
     * @param string[] $potentialGroups
     *
     * @return string[]
     */
    private function resolvePotentialGroups(array $potentialGroups): array
    {
        $groupsIncludes = [];
        foreach ($potentialGroups as $potentialGroup) {
            if (array_key_exists($potentialGroup, $this->groups)) {
                foreach ($this->getGroupBy($potentialGroup)->flattenedIncludes as $fqcn) {
                    $groupsIncludes[] = $fqcn;
                }
            } else {
                $groupsIncludes[] = $potentialGroup;
            }
        }

        return $groupsIncludes;
    }

    /**
     * @param string[] $targets
     *
     * @return string[]
     */
    public function resolveTargets(Group $group, array $targets, bool $dependsOnRule = false): array
    {
        $resolvedTargets = [];
        if ($dependsOnRule) {
            $resolvedTargets = $this->resolvePotentialGroups($group->flattenedIncludes);

            if ($group->extends !== null) {
                $resolvedTargets = array_merge($resolvedTargets, $this->resolvePotentialGroups([$group->extends]));
            }

            if ($group->implements !== null) {
                $resolvedTargets = array_merge($resolvedTargets, $this->resolvePotentialGroups($group->implements));
            }
        }

        return array_unique(array_merge($this->resolvePotentialGroups($targets), $resolvedTargets));
    }

    /**
     * @param string[] $unresolvedTargets
     * @param string[] $targets
     *
     * @return string[]
     */
    public function findTargetExcludes(array $unresolvedTargets, array $targets): array
    {
        $targetExcludes = [];
        foreach ($unresolvedTargets as $potentialGroup) {
            if (array_key_exists($potentialGroup, $this->groups)) {
                $group = $this->getGroupBy($potentialGroup);

                foreach ($group->flattenedExcludes ?? [] as $exclude) {
                    $targetExcludes[] = $exclude;
                }
            }
        }

        return array_unique(array_diff($targetExcludes, $targets));
    }
}
