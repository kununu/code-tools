<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Configuration\Rules;

use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\AssertionType;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\ShouldStep;
use PHPat\Test\PHPat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssertionTypeTest extends TestCase
{
    /** @param callable(ShouldStep): callable $buildWithPhpat */
    #[DataProvider('assertionTypeDataProvider')]
    public function testValueMatchesTheOnePhpatBuilds(AssertionType $assertionType, callable $buildWithPhpat): void
    {
        $step = PHPat::rule()->classes(Selector::classname('App\\Foo'))->should();

        self::assertEquals($buildWithPhpat($step)()->assertionType, $assertionType->value);
    }

    /** @return array<string, array{AssertionType, callable(ShouldStep): callable}> */
    public static function assertionTypeDataProvider(): array
    {
        return [
            'be_final'                          => [
                AssertionType::BeFinal,
                static fn(ShouldStep $step): callable => $step->beFinal(),
            ],
            'be_readonly'                       => [
                AssertionType::BeReadonly,
                static fn(ShouldStep $step): callable => $step->beReadonly(),
            ],
            'depend'                            => [
                AssertionType::Depend,
                static fn(ShouldStep $step): callable => $step->dependOn(),
            ],
            'extend'                            => [
                AssertionType::Extend,
                static fn(ShouldStep $step): callable => $step->extend(),
            ],
            'have_only_one_public_method_named' => [
                AssertionType::HaveOnlyOnePublicMethodNamed,
                static fn(ShouldStep $step): callable => $step->haveOnlyOnePublicMethodNamed('handle'),
            ],
            'implement'                         => [
                AssertionType::Implement,
                static fn(ShouldStep $step): callable => $step->implement(),
            ],
        ];
    }

    public function testEveryCaseIsCovered(): void
    {
        self::assertCount(count(AssertionType::cases()), self::assertionTypeDataProvider());
    }
}
