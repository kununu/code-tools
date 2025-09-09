<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Helper\ProjectPathResolver;
use Kununu\ArchitectureSniffer\Helper\RuleBuilder;
use Kununu\ArchitectureSniffer\Helper\TypeChecker;
use PHPat\Test\Builder\Rule as PHPatRule;
use Symfony\Component\Yaml\Yaml;

final class ArchitectureSniffer
{
    private const string ARCHITECTURE_FILENAME = 'architecture.yaml';
    public const string ARCHITECTURE_KEY = 'architecture';

    /**
     * @return iterable<PHPatRule>
     */
    public function testArchitecture(): iterable
    {
        /** @var array<string, mixed> $data */
        $data = Yaml::parseFile(ProjectPathResolver::resolve(self::ARCHITECTURE_FILENAME));

        if (!array_key_exists(self::ARCHITECTURE_KEY, $data)) {
            throw new InvalidArgumentException(
                'Invalid architecture configuration: "architecture" key is missing.'
            );
        }

        $architecture = $data['architecture'];

        if (!TypeChecker::isArrayKeysOfStrings($architecture)) {
            throw new InvalidArgumentException(
                'Invalid architecture configuration: "groups" must be a non-empty array.'
            );
        }

        if (!is_array($architecture)) {
            throw new InvalidArgumentException(
                'Invalid architecture configuration: "groups" must be an array.'
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
                static fn($include) => str_starts_with($include, 'App\\')
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

        $library = new ArchitectureLibrary($architecture);

        foreach (array_keys($architecture) as $groupName) {
            foreach (RuleBuilder::getRules($library->getGroupBy($groupName), $library) as $rule) {
                yield $rule;
            }
        }
    }
}
