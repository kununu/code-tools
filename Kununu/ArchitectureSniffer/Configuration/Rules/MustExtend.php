<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Generator;
use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Selector\InterfaceClassSelector;
use PHPat\Test\PHPat;

final readonly class MustExtend extends AbstractRule
{
    public const string KEY = 'extends';

    public function __construct(
        public Generator $extensions,
        public Generator $selectables,
    ) {
        $extensions = clone $this->extensions;

        foreach ($extensions as $extension) {
            if ($extension instanceof InterfaceClassSelector) {
                throw new InvalidArgumentException(
                    'Classes can not extend interfaces.'
                );
            }
        }
    }

    public function getPHPatRule(string $groupName): \PHPat\Test\Builder\Rule
    {
        return PHPat::rule()
            ->classes(...$this->getPHPSelectors($this->selectables))
            ->shouldExtend()
            ->classes(...$this->getPHPSelectors($this->extensions))
            ->because("$groupName should extend class.");
    }
}
