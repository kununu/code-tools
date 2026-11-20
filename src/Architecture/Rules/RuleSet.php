<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Architecture\Rules;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

abstract readonly class RuleSet
{
    protected const string APPLICATION = 'App';
    protected const string CONSOLE_COMMAND = 'App\Command';
    protected const string CONTROLLER = 'App\Controller';
    protected const string DOMAIN = 'App\Domain';
    protected const string INFRASTRUCTURE = 'App\Infrastructure';
    protected const string MESSAGING = 'App\Messaging';
    protected const string TESTS = 'App\Tests';

    protected const string DOCTRINE = 'Doctrine';
    protected const string SYMFONY_BUNDLE = 'Symfony\Bundle';
    protected const string SYMFONY_CONSOLE = 'Symfony\Component\Console';
    protected const string SYMFONY_HTTP_FOUNDATION = 'Symfony\Component\HttpFoundation';
    protected const string SYMFONY_HTTP_KERNEL = 'Symfony\Component\HttpKernel';

    /**
     * @param list<non-empty-string> $from
     * @param list<non-empty-string> $on
     * @param list<non-empty-string> $except
     */
    protected static function forbidDependency(array $from, array $on, string $because, array $except = []): Rule
    {
        return PHPat::rule()
            ->classes(...self::namespaces($from))
            ->excluding(...self::namespaces($except))
            ->shouldNot()
            ->dependOn()
            ->classes(...self::namespaces($on))
            ->because($because)
            ->nonIgnorable();
    }

    /**
     * @param list<non-empty-string> $namespaces
     *
     * @return list<SelectorInterface>
     */
    private static function namespaces(array $namespaces): array
    {
        return array_map(
            static fn(string $namespace): SelectorInterface => Selector::inNamespace($namespace),
            $namespaces
        );
    }
}
