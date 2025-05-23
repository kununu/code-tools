<?php
declare(strict_types=1);

namespace Kununu\ArchitectureTest\Configuration\Rules;

use Kununu\ArchitectureTest\Configuration\InterfaceClassSelector;
use Kununu\ArchitectureTest\Configuration\Selectable;
use Kununu\ArchitectureTest\Configuration\Selectors;
use PHPat\Test\PHPat;

final readonly class MustExtend implements Rule
{
    public const string KEY = 'extends';
    public function __construct(
        public Selectable $selector,
        public Selectable $parent,
    ) {
    }

    public static function fromArray(Selectable $selector, array $data): self
    {
        $parent = Selectors::findSelector($data);

        if ($parent instanceof InterfaceClassSelector) {
            throw new \InvalidArgumentException(
                'The parent class must not be an interface.'
            );
        }

        return new self($selector, $parent);
    }

    public function getPHPatRule(): \PHPat\Test\Builder\Rule
    {
        return PHPat::rule()
            ->classes($this->selector->getPHPatSelector())
            ->shouldExtend()
            ->classes(
                $this->parent->getPHPatSelector()
            )
            ->because("{$this->selector->getName()} should extend {$this->parent->getName()}.");
    }
}
