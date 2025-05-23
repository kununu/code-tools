<?php
declare(strict_types=1);

namespace Kununu\ArchitectureTest\Configuration\Rules;

use Kununu\ArchitectureTest\Configuration\InterfaceClassSelector;
use Kununu\ArchitectureTest\Configuration\Selectable;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule as PHPatRule;
use PHPat\Test\PHPat;

final readonly class MustBeFinal implements Rule
{
    public const string KEY = 'final';

    public function __construct(
        public ?Selectable $selector
    ) {
    }

    public static function fromArray(Selectable $selector): self
    {
        if ($selector instanceof InterfaceClassSelector) {
            throw new \InvalidArgumentException(
                'The class must not be an interface.'
            );
        }

        return new self($selector);
    }

    public function getPHPatRule(): PHPatRule
    {
        return PHPat::rule()
            ->classes($this->selector->getPHPatSelector())
            ->excluding(Selector::isInterface())
            ->shouldBeFinal()
            ->because("{$this->selector->getName()} must be final.");
    }
}
