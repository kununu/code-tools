<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer\Configuration\Rules;

use Kununu\ArchitectureSniffer\Configuration\Rules\MustBeFinal;
use LogicException;
use PHPat\Selector\SelectorInterface;
use PHPUnit\Framework\TestCase;

final class AbstractRuleTest extends TestCase
{
    public function testGetPHPSelectorsReturnsCorrectSelectors(): void
    {
        $result = MustBeFinal::getPHPSelectors(['App\\Service\\MyService']);

        self::assertCount(1, $result);
        self::assertInstanceOf(SelectorInterface::class, $result[0]);
    }

    public function testGetPHPSelectorsReturnsEmptyArrayForNoInput(): void
    {
        $result = MustBeFinal::getPHPSelectors([]);

        self::assertSame([], $result);
    }

    public function testGetPHPSelectorsHandlesMultipleSelectors(): void
    {
        $result = MustBeFinal::getPHPSelectors([
            'App\\Service\\UserService',
            'App\\Service\\OrderService',
        ]);

        self::assertCount(2, $result);
        self::assertInstanceOf(SelectorInterface::class, $result[0]);
        self::assertInstanceOf(SelectorInterface::class, $result[1]);
    }

    public function testGetInvalidCallExceptionReturnsLogicException(): void
    {
        $exception = MustBeFinal::getInvalidCallException('MustBeFinal', 'TestGroup', 'final');

        self::assertInstanceOf(LogicException::class, $exception);
        self::assertStringContainsString('MustBeFinal', $exception->getMessage());
        self::assertStringContainsString('TestGroup', $exception->getMessage());
        self::assertStringContainsString('final', $exception->getMessage());
    }
}
