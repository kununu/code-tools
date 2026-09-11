<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Configuration\Selector;

use InvalidArgumentException;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
use PHPat\Selector\Modifier\AllOfSelectorModifier;
use PHPUnit\Framework\TestCase;

final class InterfaceClassSelectorTest extends TestCase
{
    public function testGetPHPatSelectorReturnsSelector(): void
    {
        $selector = new InterfaceClassSelector('App\\Contract\\ServiceInterface');

        $result = $selector->getPHPatSelector();

        self::assertInstanceOf(AllOfSelectorModifier::class, $result);
    }

    public function testGetPHPatSelectorWithWildcard(): void
    {
        $selector = new InterfaceClassSelector('App\\*\\ServiceInterface');

        $result = $selector->getPHPatSelector();

        self::assertInstanceOf(AllOfSelectorModifier::class, $result);
    }

    public function testGetPHPatSelectorThrowsOnEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $selector = new InterfaceClassSelector('');
        $selector->getPHPatSelector();
    }
}
