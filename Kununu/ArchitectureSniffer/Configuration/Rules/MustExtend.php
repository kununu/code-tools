<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use InvalidArgumentException;
use JsonException;
use Kununu\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
use Kununu\ArchitectureSniffer\Configuration\Selector\Selectable;
use Kununu\ArchitectureSniffer\Configuration\Selectors;
use PHPat\Test\PHPat;

final readonly class MustExtend implements Rule
{
    public const string KEY = 'extends';

    public function __construct(
        public Selectable $selector,
        public Selectable $parent,
    ) {
    }

    /**
     * @throws JsonException
     */
    public static function fromArray(Selectable $selector, array $data): self
    {
        $parent = Selectors::findSelector($data);

        if ($parent instanceof InterfaceClassSelector) {
            throw new InvalidArgumentException(
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
