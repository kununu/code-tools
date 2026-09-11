<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Configuration\Selector;

use InvalidArgumentException;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Selector\NamespaceSelector;
use PHPat\Selector\ClassNamespace;
use PHPUnit\Framework\TestCase;

final class NamespaceSelectorTest extends TestCase
{
    public function testGetPHPatSelectorReturnsSelector(): void
    {
        $selector = new NamespaceSelector('App\\Service\\');

        $result = $selector->getPHPatSelector();

        self::assertInstanceOf(ClassNamespace::class, $result);
    }

    public function testGetPHPatSelectorWithWildcard(): void
    {
        $selector = new NamespaceSelector('App\\*\\Service\\');

        $result = $selector->getPHPatSelector();

        self::assertInstanceOf(ClassNamespace::class, $result);
    }

    public function testGetPHPatSelectorThrowsOnEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $selector = new NamespaceSelector('');
        $selector->getPHPatSelector();
    }
}
