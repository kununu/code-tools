<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\Architecture\Rules;

use Kununu\CodeTools\Architecture\Rules\RuleSet;
use Kununu\CodeTools\Tests\Architecture\RunsArchitectureCheck;
use PHPat\Test\Builder\Rule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

abstract class RuleSetTestCase extends TestCase
{
    use RunsArchitectureCheck;

    private const string CQRS_PROJECT = 'cqrs';

    public function testEveryRuleIsCoveredByAFixture(): void
    {
        self::assertEquals(self::ruleNames(), array_keys(static::expectedViolations()));
    }

    #[DataProvider('ruleCannotBeIgnoredDataProvider')]
    public function testRuleCannotBeIgnored(string $ruleName): void
    {
        $rule = new (static::ruleSet())()->{$ruleName}();

        self::assertInstanceOf(Rule::class, $rule);
        self::assertTrue($rule()->nonIgnorable);
    }

    /** @return array<string, array{string}> */
    public static function ruleCannotBeIgnoredDataProvider(): array
    {
        $ruleNames = self::ruleNames();

        return array_combine(
            $ruleNames,
            array_map(static fn(string $ruleName): array => [$ruleName], $ruleNames)
        );
    }

    public function testRulesReportExactlyTheExpectedViolations(): void
    {
        self::assertEquals(
            static::expectedViolations(),
            array_intersect_key(self::reportedViolations(self::CQRS_PROJECT), array_flip(self::ruleNames()))
        );
    }

    /** @return class-string<RuleSet> */
    abstract protected static function ruleSet(): string;

    /**
     * Rule name => fixture file => what the class should do according to each reported message, all sorted.
     *
     * @return array<string, array<string, list<string>>>
     */
    abstract protected static function expectedViolations(): array;

    /** @return list<string> */
    private static function ruleNames(): array
    {
        $ruleNames = array_values(
            array_filter(
                get_class_methods(static::ruleSet()),
                static fn(string $method): bool => str_starts_with($method, 'test')
            )
        );
        sort($ruleNames);

        return $ruleNames;
    }
}
