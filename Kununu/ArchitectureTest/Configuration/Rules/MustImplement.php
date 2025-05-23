<?php
declare(strict_types=1);

namespace Kununu\ArchitectureTest\Configuration\Rules;

use Kununu\ArchitectureTest\Configuration\InterfaceClassSelector;
use Kununu\ArchitectureTest\Configuration\Selectable;
use Kununu\ArchitectureTest\Configuration\Selectors;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\PHPat;

final readonly class MustImplement implements Rule
{
    public const string KEY = 'implements';

    public function __construct(
        public Selectable $selector,
        public array $interfaces,
    ) {
    }

    public static function fromArray(Selectable $selector, array $data): self
    {
        $interfaces = [];
        foreach ($data as $interface) {
            $interfaceSelector = Selectors::findSelector($interface);
            if (!$interfaceSelector instanceof InterfaceClassSelector) {
                throw new \InvalidArgumentException(
                    "The {$interfaceSelector->getName()} must be declared as interface."
                );
            }
            $interfaces[] = $interfaceSelector;
        }

        return new self($selector, $interfaces);
    }

    public function getPHPatRule(): \PHPat\Test\Builder\Rule
    {
        $interfacesString = implode(', ', array_map(
            static fn (Selectable $interface): string => $interface->getName(),
            $this->interfaces
        ));

        return PHPat::rule()
            ->classes(
                $this->selector->getPHPatSelector(),
            )
            ->excluding(Selector::isInterface())
            ->shouldImplement()
            ->classes(
                ...array_map(
                    static fn (Selectable $interface): SelectorInterface => $interface->getPHPatSelector(),
                    $this->interfaces
                )
            )
            ->because("{$this->selector->getName()} must implement $interfacesString.");
    }
}
