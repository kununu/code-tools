<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\Selectable;
use Kununu\ArchitectureSniffer\Configuration\SelectorsLibrary;
use PHPat\Test\Builder\Rule as PHPatRule;
use PHPat\Test\PHPat;

final readonly class MustExtend extends AbstractRule
{
    public static function createRule(
        string $groupName,
        SelectorsLibrary $selectorsLibrary,
    ): PHPatRule {
        $includes = $selectorsLibrary->getIncludesByGroup($groupName);
        $excludes = $selectorsLibrary->getExcludesByGroup($groupName);
        $extensions = self::checkIfNotInterfaceSelectors(
            $selectorsLibrary->getTargetByGroup($groupName, Group::EXTENDS_KEY)
        );
        $extensionExcludes = $selectorsLibrary->getTargetExcludesByGroup($groupName, Group::EXTENDS_KEY);

        $rule = PHPat::rule()->classes(...self::getPHPSelectors($includes));

        $excludes = self::getPHPSelectors($excludes);
        if ($excludes !== []) {
            $rule = $rule->excluding(...$excludes);
        }

        $rule = $rule->shouldExtend()->classes(...self::getPHPSelectors($extensions));

        $extensionExcludes = self::getPHPSelectors($extensionExcludes);
        if ($extensionExcludes !== []) {
            $rule = $rule->excluding(...$extensionExcludes);
        }

        return $rule->because("$groupName should extend class.");
    }

    /**
     * @param iterable<Selectable> $selectors
     *
     * @return iterable<Selectable>
     */
    private static function checkIfNotInterfaceSelectors(iterable $selectors): iterable
    {
        foreach ($selectors as $selector) {
            if ($selector instanceof InterfaceClassSelector) {
                throw new InvalidArgumentException(
                    "$selector->interface cannot be used in the MustExtend rule, as it is an interface."
                );
            }
            yield $selector;
        }
    }
}
