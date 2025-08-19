<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Group;
use Kununu\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
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
        $extensions = $selectorsLibrary->getTargetByGroup($groupName, Group::EXTENDS_KEY);
        $extensionExcludes = $selectorsLibrary->getTargetExcludesByGroup($groupName, Group::EXTENDS_KEY);

        foreach ($extensions as $extension) {
            if ($extension instanceof InterfaceClassSelector) {
                throw new InvalidArgumentException('Classes can not extend interfaces.');
            }
        }

        $rule = PHPat::rule()->classes(...self::getPHPSelectors($includes));

        if ($excludes !== null) {
            $rule = $rule->excluding(...self::getPHPSelectors($excludes));
        }

        $rule = $rule->shouldExtend()->classes(...self::getPHPSelectors($extensions));

        if ($extensionExcludes !== null) {
            $rule = $rule->excluding(...self::getPHPSelectors($extensionExcludes));
        }

        return $rule->because("$groupName should extend class.");
    }
}
