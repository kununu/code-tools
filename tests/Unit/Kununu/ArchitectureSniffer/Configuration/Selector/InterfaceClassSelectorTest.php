<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer\Configuration\Selector;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
use PHPat\Selector\SelectorInterface;
use PHPUnit\Framework\TestCase;

final class InterfaceClassSelectorTest extends TestCase
{
    public function testGetPHPatSelectorReturnsSelector(): void
    {
        $selector = new InterfaceClassSelector('App\\Contract\\ServiceInterface');

        $result = $selector->getPHPatSelector();

        self::assertInstanceOf(SelectorInterface::class, $result);
    }

    public function testGetPHPatSelectorWithWildcard(): void
    {
        $selector = new InterfaceClassSelector('App\\*\\ServiceInterface');

        $result = $selector->getPHPatSelector();

        self::assertInstanceOf(SelectorInterface::class, $result);
    }

    public function testGetPHPatSelectorThrowsOnEmptyString(): void
    {
        self::expectException(InvalidArgumentException::class);

        $selector = new InterfaceClassSelector('');
        $selector->getPHPatSelector();
    }
}
