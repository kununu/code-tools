<?php
declare(strict_types=1);

namespace Kununu\ArchitectureTest\Configuration\Rules;

use Kununu\ArchitectureTest\Configuration\Selectable;
use PHPat\Test\PHPat;

final readonly class MustOnlyHaveOnePublicMethodNamed implements Rule
{
    public const string KEY = 'only-one-public-method-named';
    public function __construct(
        public Selectable $selector,
        public string $functionName,
    ) {
    }

    public static function fromArray(Selectable $base, string $functionName): self
    {
        return new self($base, $functionName);
    }

    public function getPHPatRule(): \PHPat\Test\Builder\Rule
    {
        return PHPat::rule()
            ->classes($this->selector->getPHPatSelector())
            ->shouldHaveOnlyOnePublicMethodNamed($this->functionName)
            ->because("{$this->selector->getName()} should only have one public method named $this->functionName.");
    }
}
