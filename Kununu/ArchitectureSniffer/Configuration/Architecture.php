<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration;

use Generator;
use InvalidArgumentException;

final readonly class Architecture
{
    public const string ARCHITECTURE_KEY = 'architecture';

    /**
     * @param array<Group> $groups
     */
    private function __construct(private array $groups)
    {
    }

    /**
     * @param array<int, array<string, mixed>> $data
     */
    public static function fromArray(array $data): self
    {
        if (!array_key_exists(self::ARCHITECTURE_KEY, $data)) {
            throw new InvalidArgumentException(
                'Invalid architecture configuration: "architecture" key is missing.'
            );
        }

        $architecture = $data['architecture'];

        if (empty($architecture)) {
            throw new InvalidArgumentException(
                'Invalid architecture configuration: "groups" must be a non-empty array.'
            );
        }

        // each group must have an include with at least one fully qualified fqcn or another qualified group
        if (!array_filter(
            $architecture,
            static fn(array $group) => array_key_exists(Group::INCLUDES_KEY, $group)
                && !empty($group[Group::INCLUDES_KEY])
        )) {
            throw new InvalidArgumentException(
                'Each group must have an "includes" property with at least one fully qualified fqcn or '
                . 'another qualified group.'
            );
        }
        // at least one group with a depends_on property with at least one fqcn or another qualified group
        if (!array_filter(
            $architecture,
            static fn(array $group) => array_key_exists(Group::DEPENDS_ON_KEY, $group)
                && !empty($group[Group::DEPENDS_ON_KEY])
        )) {
            throw new InvalidArgumentException(
                'At least one group must have a "dependsOn" property with at least one fqcn or '
                . 'another qualified group.'
            );
        }
        // groups with at least one include from a global namespace other than App\\, the depends_on properties must not be defined
        $groupsWithIncludesFromGlobalNamespace = array_filter(
            $architecture,
            static fn(array $group) => !array_filter(
                is_array($group[Group::INCLUDES_KEY] ?? null) ? $group[Group::INCLUDES_KEY] : [],
                fn($include) => str_starts_with($include, 'App\\')
            )
        );

        if ($groupsWithIncludesFromGlobalNamespace) {
            if (array_filter(
                $groupsWithIncludesFromGlobalNamespace,
                static fn(array $group) => array_key_exists(Group::DEPENDS_ON_KEY, $group)
            )) {
                throw new InvalidArgumentException(
                    'Groups with includes from a global namespace other than App\\ must not have a '
                    . '"dependsOn" property defined.'
                );
            }
        }

        $groups = array_map(
            static fn(array $groupData) => Group::fromArray($groupData),
            $architecture
        );

        return new self($groups);
    }

    public function getRules(): Generator
    {
        foreach ($this->groups as $group) {
            yield $group->generateRules()->getRules();
        }
    }
}
