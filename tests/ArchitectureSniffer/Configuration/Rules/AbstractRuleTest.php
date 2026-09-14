<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Configuration\Rules;

use Kununu\CodeTools\ArchitectureSniffer\Configuration\Rules\MustBeFinal;
use PHPat\Selector\Classname;
use PHPUnit\Framework\TestCase;

final class AbstractRuleTest extends TestCase
{
    public function testGetPHPSelectorsReturnsCorrectSelectors(): void
    {
        $result = MustBeFinal::getPHPSelectors(['App\\Service\\MyService']);

        self::assertCount(1, $result);
        self::assertInstanceOf(Classname::class, $result[0]);
    }

    public function testGetPHPSelectorsReturnsEmptyArrayForNoInput(): void
    {
        $result = MustBeFinal::getPHPSelectors([]);

        self::assertEquals([], $result);
    }

    public function testGetPHPSelectorsHandlesMultipleSelectors(): void
    {
        $result = MustBeFinal::getPHPSelectors([
            'App\\Service\\UserService',
            'App\\Service\\OrderService',
        ]);

        self::assertCount(2, $result);
        self::assertInstanceOf(Classname::class, $result[0]);
        self::assertInstanceOf(Classname::class, $result[1]);
    }

    public function testGetInvalidCallExceptionReturnsLogicException(): void
    {
        $exception = MustBeFinal::getInvalidCallException('MustBeFinal', 'TestGroup', 'final');

        self::assertStringContainsString('MustBeFinal', $exception->getMessage());
        self::assertStringContainsString('TestGroup', $exception->getMessage());
        self::assertStringContainsString('final', $exception->getMessage());
    }
}
