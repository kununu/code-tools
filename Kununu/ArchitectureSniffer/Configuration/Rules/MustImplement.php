<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\Selector\ClassSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\NamespaceSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\Selectable;
use Kununu\ArchitectureSniffer\Configuration\SelectorsLibrary;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule as PHPatRule;
use PHPat\Test\PHPat;

final readonly class MustImplement extends AbstractRule
{
    public static function createRule(
        string $groupName,
        SelectorsLibrary $library,
    ): PHPatRule {
        $includes = $library->getIncludesByGroup($groupName);
        $excludes = $library->getExcludesByGroup($groupName);
        $interfaces = self::checkIfInterfaceSelectors($library->getTargetByGroup($groupName, Group::IMPLEMENTS_KEY));
        $interfacesExcludes = $library->getTargetExcludesByGroup($groupName, Group::IMPLEMENTS_KEY);

        $rule = PHPat::rule()->classes(...self::getPHPSelectors($includes));

        $excludeSelectors = self::getPHPSelectors($excludes);
        $excludeSelectors[] = Selector::isInterface();
        $rule = $rule->excluding(...$excludeSelectors);

        $rule = $rule->shouldImplement()->classes(...self::getPHPSelectors($interfaces));
        $interfacesExcludes = self::getPHPSelectors($interfacesExcludes);
        if ($interfacesExcludes !== []) {
            $rule = $rule->excluding(...$interfacesExcludes);
        }

        return $rule->because("$groupName must implement interface.");
    }

    /**
     * @param iterable<Selectable> $selectors
     *
     * @return iterable<Selectable>
     */
    private static function checkIfInterfaceSelectors(iterable $selectors): iterable
    {
        foreach ($selectors as $selector) {
            if ($selector instanceof ClassSelector || $selector instanceof NamespaceSelector) {
                if ($selector instanceof NamespaceSelector) {
                    $name = $selector->namespace;
                } else {
                    $name = $selector->class;
                }
                throw new InvalidArgumentException(
                    "$name cannot be used in the MustImplement rule, as it is not an interface."
                );
            }
            yield $selector;
        }
    }
}
