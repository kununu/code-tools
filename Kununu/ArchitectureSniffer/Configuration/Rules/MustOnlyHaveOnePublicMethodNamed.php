<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Generator;
use PHPat\Test\PHPat;

final readonly class MustOnlyHaveOnePublicMethodNamed extends AbstractRule
{
    public const string KEY = 'only-one-public-method-named';

    public function __construct(
        public Generator $selectables,
        public string $functionName,
    ) {
    }

    public function getPHPatRule(string $groupName): \PHPat\Test\Builder\Rule
    {
        return PHPat::rule()
            ->classes(...$this->getPHPSelectors($this->selectables))
            ->shouldHaveOnlyOnePublicMethodNamed($this->functionName)
            ->because("$groupName should only have one public method named $this->functionName.");
    }
}
