<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Selector\Selectable;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Builder\AssertionStep;
use PHPat\Test\Builder\Rule as PHPatRule;
use PHPat\Test\Builder\TargetStep;
use PHPat\Test\PHPat;

abstract readonly class AbstractRule
{
    /**
     * @param iterable<Selectable> $selectors
     *
     * @return array<SelectorInterface>
     */
    public static function getPHPSelectors(iterable $selectors): array
    {
        $result = [];
        foreach ($selectors as $selector) {
            $result[] = $selector->getPHPatSelector();
        }

        return $result;
    }

    /**
     * @param callable(AssertionStep): TargetStep $assertionStep
     * @param array<SelectorInterface>            $extraSelectors
     */
    protected static function buildDependencyRule(
        string $groupName,
        ArchitectureLibrary $library,
        callable $assertionStep,
        string $targetKey,
        string $because = '',
        array $extraSelectors = [],
    ): PHPatRule {
        $includes = $library->getIncludesByGroup($groupName);
        $excludes = $library->getExcludesByGroup($groupName);
        $target = $targetKey ? $library->getTargetByGroup($groupName, $targetKey) : [];
        $targetExcludes = $targetKey ? $library->getTargetExcludesByGroup($groupName, $targetKey) : [];

        $includes = self::getPHPSelectors($includes);
        $excludes = self::getPHPSelectors($excludes);
        $target = self::getPHPSelectors($target);
        $targetExcludes = self::getPHPSelectors($targetExcludes);

        $rule = PHPat::rule()->classes(...$includes);
        if ($excludes !== []) {
            $rule = $rule->excluding(...$excludes);
        }
        $rule = $assertionStep($rule);

        if ($extraSelectors !== []) {
            $target = array_merge($target, $extraSelectors);
        }
        $rule = $rule->classes(...$target);
        if ($targetExcludes !== []) {
            $rule = $rule->excluding(...$targetExcludes);
        }
        if ($because) {
            $rule = $rule->because($because);
        }

        return $rule;
    }
}
