<?php
declare(strict_types=1);

namespace Kununu\CodeTools\ArchitectureSniffer\Helper;

use Kununu\CodeTools\ArchitectureSniffer\Configuration\Selector\ClassSelector;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Selector\NamespaceSelector;
use Kununu\CodeTools\ArchitectureSniffer\Configuration\Selector\Selectable;

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
