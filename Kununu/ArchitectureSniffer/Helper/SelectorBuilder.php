<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Helper;

use Kununu\ArchitectureSniffer\Configuration\Selector\ClassSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\NamespaceSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\Selectable;

final readonly class SelectorBuilder
{
    public static function createSelectable(string $fqcn): Selectable
    {
        return match (true) {
            interface_exists($fqcn) || str_ends_with($fqcn, 'Interface') => new InterfaceClassSelector($fqcn),
            str_ends_with($fqcn, '\\')                                   => new NamespaceSelector($fqcn),
            default                                                      => new ClassSelector($fqcn),
        };
    }
}
