<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration;

use Generator;
use InvalidArgumentException;

final readonly class Architecture
{
    /**
     * @param array<Group> $groups
     */
    private function __construct(
        private array $groups,
    ) {
    }

    public static function fromArray(array $data): self
    {
        if (empty($data) || !is_array($data)) {
            throw new InvalidArgumentException('Invalid architecture configuration: "groups" must be a non-empty array.');
        }

        // each group must have an include with at least on fully qualified fqcn or another qualified group
        if (!array_filter($data, static fn(array $group) => array_key_exists(Group::INCLUDES_KEY, $group) && !empty($group[Group::INCLUDES_KEY]))) {
            throw new InvalidArgumentException('Each group must have an "includes" property with at least one fully qualified fqcn or another qualified group.');
        }
        // at least one group with a depends_on property with at least one fqcn or another qualified group
        if (!array_filter($data, static fn(array $group) => array_key_exists(Group::DEPENDS_ON_KEY, $group) && !empty($group[Group::DEPENDS_ON_KEY]))) {
            throw new InvalidArgumentException('At least one group must have a "dependsOn" property with at least one fqcn or another qualified group.');
        }
        // groups with at least one include from a global namespace other than App\\, the depends_on properties must not be defined
        if (array_filter($data, static fn(array $group) => !str_starts_with($group['includes'][0], 'App\\'))) {
            if (array_filter($data, static fn(array $group) => array_key_exists(Group::DEPENDS_ON_KEY, $group))) {
                throw new InvalidArgumentException('Groups with includes from a global namespace other than App\\ must not have a "dependsOn" property defined.');
            }
        }

        $groups = array_map(
            static fn(array $groupData) => Group::fromArray($groupData),
            $data
        );

        foreach ($groups as $group) {
            $group->generateRules();
        }

        return new self($groups);
    }

    public function getGroups(): Generator
    {
        foreach ($this->groups as $group) {
            yield $group;
        }
    }
}
