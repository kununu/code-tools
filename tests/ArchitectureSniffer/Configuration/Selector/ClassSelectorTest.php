<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Configuration\Selector;

use InvalidArgumentException;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Selector\ClassSelector;
use PHPat\Selector\Classname;
use PHPUnit\Framework\TestCase;

final class ClassSelectorTest extends TestCase
{
    public function testGetPHPatSelectorReturnsSelector(): void
    {
        $selector = new ClassSelector('App\\Service\\MyService');

        $result = $selector->getPHPatSelector();

        self::assertInstanceOf(Classname::class, $result);
    }

    public function testGetPHPatSelectorWithWildcard(): void
    {
        $selector = new ClassSelector('App\\*\\MyService');

        $result = $selector->getPHPatSelector();

        self::assertInstanceOf(Classname::class, $result);
    }

    public function testGetPHPatSelectorThrowsOnEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $selector = new ClassSelector('');
        $selector->getPHPatSelector();
    }
}
