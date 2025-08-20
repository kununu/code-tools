<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration;

use PHPat\Test\Builder\Rule as PHPatRule;

final readonly class Group
{
    public const string INCLUDES_KEY = 'includes';
    public const string EXCLUDES_KEY = 'excludes';
    public const string DEPENDS_ON_KEY = 'depends_on';
    public const string FINAL_KEY = 'final';
    public const string EXTENDS_KEY = 'extends';
    public const string IMPLEMENTS_KEY = 'implements';
    public const string MUST_ONLY_HAVE_ONE_PUBLIC_METHOD_NAMED_KEY = 'must_only_have_one_public_method_named';
    public const string MUST_NOT_DEPEND_ON_KEY = 'must_not_depend_on';

    /**
     * @return iterable<PHPatRule>
     **/
    public static function getRules(string $groupName, SelectorsLibrary $library): iterable
    {
        if ($library->groupHasKey($groupName, self::EXTENDS_KEY)) {
            yield Rules\MustExtend::createRule(
                $groupName,
                $library
            );
        }

        if ($library->groupHasKey($groupName, self::IMPLEMENTS_KEY)) {
            yield Rules\MustImplement::createRule(
                $groupName,
                $library
            );
        }

        if ($library->groupHasKey($groupName, self::FINAL_KEY)) {
            yield Rules\MustBeFinal::createRule(
                $groupName,
                $library
            );
        }
        if ($library->groupHasKey($groupName, self::DEPENDS_ON_KEY)) {
            yield Rules\MustOnlyDependOn::createRule(
                $groupName,
                $library
            );
        }
        if ($library->groupHasKey($groupName, self::MUST_NOT_DEPEND_ON_KEY)) {
            yield Rules\MustNotDependOn::createRule(
                $groupName,
                $library
            );
        }
        if ($library->getOnlyPublicFunctionByGroup($groupName)) {
            yield Rules\MustOnlyHaveOnePublicMethodNamed::createRule(
                $groupName,
                $library
            );
        }
    }
}
