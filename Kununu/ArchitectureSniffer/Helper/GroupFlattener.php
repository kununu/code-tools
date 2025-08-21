<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Helper;

use Generator;
use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Group;

final class GroupFlattener
{
    /**
     * @var array<string, array<string, string[]|string|bool>>
     */
    public static array $groups;

    /**
     * @var string[]
     */
    public static array $passedGroups = [];

    /**
     * @param string[] $includes
     *
     * @return string[]
     */
    public static function flattenIncludes(string $groupName, array $includes): array
    {
        self::$passedGroups = [$groupName];
        $flattenedIncludes = [];

        foreach ($includes as $include) {
            foreach (self::resolveGroup($include, Group::INCLUDES_KEY) as $selectable) {
                $flattenedIncludes[] = $selectable;
            }
        }

        return $flattenedIncludes;
    }

    /**
     * @param string[] $excludes
     * @param string[] $flattenedIncludes
     *
     * @return string[]|null
     */
    public static function flattenExcludes(string $groupName, array $excludes, array $flattenedIncludes): ?array
    {
        self::$passedGroups = [$groupName];

        $flattenedExcludes = [];
        foreach ($excludes as $exclude) {
            foreach (self::resolveGroup($exclude, Group::EXCLUDES_KEY) as $selectable) {
                $flattenedExcludes[] = $selectable;
            }
        }

        $flattenedExcludes = array_diff($flattenedExcludes, $flattenedIncludes);

        return $flattenedExcludes !== [] ? $flattenedExcludes : null;
    }

    /**
     * @return Generator<string>
     */
    private static function resolveGroup(string $fqcnOrGroupName, string $key): Generator
    {
        if (array_key_exists($fqcnOrGroupName, self::$groups)) {
            if (in_array($fqcnOrGroupName, self::$passedGroups, true)) {
                return;
            }

            self::$passedGroups[] = $fqcnOrGroupName;

            if (!is_array(self::$groups[$fqcnOrGroupName][$key])) {
                throw new InvalidArgumentException(
                    "Group '$fqcnOrGroupName' must have a non-empty '$key' key."
                );
            }

            foreach (self::$groups[$fqcnOrGroupName][$key] as $subFqcnOrGroupName) {
                yield from self::resolveGroup($subFqcnOrGroupName, $key);
            }

            return;
        }

        yield $fqcnOrGroupName;
    }
}
