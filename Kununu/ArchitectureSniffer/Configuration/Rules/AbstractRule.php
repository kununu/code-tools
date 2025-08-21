<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\ArchitectureLibrary;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Helper\SelectorBuilder;
use LogicException;
use PHPat\Rule\Assertion\Declaration\DeclarationAssertion;
use PHPat\Rule\Assertion\Relation\RelationAssertion;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\Builder\Rule;
use PHPat\Test\RelationRule;

abstract readonly class AbstractRule
{
    abstract public static function createRule(
        Group $group,
        ArchitectureLibrary $library,
    ): Rule;

    /**
     * @param string[] $selectors
     *
     * @return array<SelectorInterface>
     */
    public static function getPHPSelectors(array $selectors): array
    {
        $result = [];
        foreach ($selectors as $selector) {
            $result[] = SelectorBuilder::createSelectable($selector)->getPHPatSelector();
        }

        return $result;
    }

    /**
     * @param class-string<DeclarationAssertion>|class-string<RelationAssertion> $specificRule
     * @param array<string, string|false>|null                                   $ruleParams
     * @param string[]|null                                                      $targets
     * @param string[]|null                                                      $targetExcludes
     * @param array<SelectorInterface>                                           $extraTargetSelectors
     * @param array<SelectorInterface>                                           $extraExcludeSelectors
     */
    protected static function buildDependencyRule(
        Group $group,
        string $specificRule,
        string $because = '',
        ?array $ruleParams = [],
        ?array $targets = null,
        ?array $targetExcludes = null,
        array $extraTargetSelectors = [],
        array $extraExcludeSelectors = [],
    ): Rule {
        $rule = new RelationRule();

        $rule->subjects = self::getPHPSelectors($group->flattenedIncludes);
        if ($group->flattenedExcludes !== null) {
            $rule->subjectExcludes = array_merge(
                self::getPHPSelectors($group->flattenedExcludes),
                $extraExcludeSelectors
            );
        }

        $rule->assertion = $specificRule;
        if ($ruleParams !== null) {
            $rule->params = $ruleParams;
        }

        if ($targets !== null) {
            $targetSelectors = self::getPHPSelectors($targets);
            $rule->targets = array_merge($targetSelectors, $extraTargetSelectors);

            if ($targetExcludes !== null) {
                $rule->targetExcludes = self::getPHPSelectors($targetExcludes);
            }
        }

        if ($because) {
            $rule->tips = [$because];
        }

        return new BuildStep($rule);
    }

    public static function getInvalidCallException(string $rule, string $groupName, string $key): LogicException
    {
        return new LogicException(
            "$rule should only be called if there are $key defined in $groupName."
        );
    }
}
