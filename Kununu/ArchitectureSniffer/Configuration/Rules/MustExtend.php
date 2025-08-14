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
        public array $extensions,
        public array $selectables,
    ) {
        foreach ($this->extensions as $extension) {
            if ($extension instanceof InterfaceClassSelector) {
                throw new InvalidArgumentException(
                    'Classes can not extend interfaces.'
                );
            }
        }
    }

    public static function fromGenerators(Generator $extensions, Generator $selectables): self
    {
        return new self(iterator_to_array($extensions), iterator_to_array($selectables));
    }

    public function getPHPatRule(string $groupName): \PHPat\Test\Builder\Rule
    {
        return PHPat::rule()
            ->classes(...self::getPHPSelectors($this->selectables))
            ->shouldExtend()
            ->classes(...self::getPHPSelectors($this->extensions))
            ->because("$groupName should extend class.");
    }
}
