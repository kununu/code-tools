<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Configuration\Rules;

use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\Assertion;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\ConstraintStep;
use PHPat\Test\PHPat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssertionTest extends TestCase
{
    /** @param callable(ConstraintStep): callable $buildWithPhpat */
    #[DataProvider('assertionDataProvider')]
    public function testAssertionMatchesTheRulePhpatBuilds(Assertion $assertion, callable $buildWithPhpat): void
    {
        $rule = $buildWithPhpat(PHPat::rule()->classes(Selector::classname('App\\Foo')))();

        self::assertEquals($assertion->constraint(), $rule->constraint);
        self::assertEquals($assertion->type(), $rule->assertionType);
    }

    /** @return array<string, array{Assertion, callable(ConstraintStep): callable}> */
    public static function assertionDataProvider(): array
    {
        return [
            'can_only_depend_on'                       => [
                Assertion::CanOnlyDependOn,
                static fn(ConstraintStep $step): callable => $step->canOnly()->dependOn(),
            ],
            'should_be_final'                          => [
                Assertion::ShouldBeFinal,
                static fn(ConstraintStep $step): callable => $step->should()->beFinal(),
            ],
            'should_be_readonly'                       => [
                Assertion::ShouldBeReadonly,
                static fn(ConstraintStep $step): callable => $step->should()->beReadonly(),
            ],
            'should_extend'                            => [
                Assertion::ShouldExtend,
                static fn(ConstraintStep $step): callable => $step->should()->extend(),
            ],
            'should_have_only_one_public_method_named' => [
                Assertion::ShouldHaveOnlyOnePublicMethodNamed,
                static fn(ConstraintStep $step): callable => $step->should()->haveOnlyOnePublicMethodNamed('handle'),
            ],
            'should_implement'                         => [
                Assertion::ShouldImplement,
                static fn(ConstraintStep $step): callable => $step->should()->implement(),
            ],
            'should_not_depend_on'                     => [
                Assertion::ShouldNotDependOn,
                static fn(ConstraintStep $step): callable => $step->shouldNot()->dependOn(),
            ],
        ];
    }

    public function testEveryCaseIsCovered(): void
    {
        self::assertEquals(Assertion::cases(), array_column(self::assertionDataProvider(), 0));
    }
}
