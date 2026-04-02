<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer\Helper;

use Kununu\ArchitectureSniffer\Configuration\Selector\ClassSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\NamespaceSelector;
use Kununu\ArchitectureSniffer\Helper\SelectorBuilder;
use PHPUnit\Framework\TestCase;

final class SelectorBuilderTest extends TestCase
{
    public function testCreateSelectableReturnsInterfaceClassSelectorForInterfaceSuffix(): void
    {
        $result = SelectorBuilder::createSelectable('App\\Contract\\ServiceInterface');

        self::assertInstanceOf(InterfaceClassSelector::class, $result);
    }

    public function testCreateSelectableReturnsNamespaceSelectorForTrailingBackslash(): void
    {
        $result = SelectorBuilder::createSelectable('App\\Service\\');

        self::assertInstanceOf(NamespaceSelector::class, $result);
    }

    public function testCreateSelectableReturnsClassSelectorForConcreteClass(): void
    {
        $result = SelectorBuilder::createSelectable('App\\Service\\MyService');

        self::assertInstanceOf(ClassSelector::class, $result);
    }
}
