<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer\Configuration\Selector;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Selector\NamespaceSelector;
use PHPat\Selector\SelectorInterface;
use PHPUnit\Framework\TestCase;

final class NamespaceSelectorTest extends TestCase
{
    public function testGetPHPatSelectorReturnsSelector(): void
    {
        $selector = new NamespaceSelector('App\\Service\\');

        $result = $selector->getPHPatSelector();

        self::assertInstanceOf(SelectorInterface::class, $result);
    }

    public function testGetPHPatSelectorWithWildcard(): void
    {
        $selector = new NamespaceSelector('App\\*\\Service\\');

        $result = $selector->getPHPatSelector();

        self::assertInstanceOf(SelectorInterface::class, $result);
    }

    public function testGetPHPatSelectorThrowsOnEmptyString(): void
    {
        self::expectException(InvalidArgumentException::class);

        $selector = new NamespaceSelector('');
        $selector->getPHPatSelector();
    }
}
