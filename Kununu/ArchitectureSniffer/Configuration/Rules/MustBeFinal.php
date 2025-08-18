<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Generator;
use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Configuration\Selector\ClassSelector;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule as PHPatRule;
use PHPat\Test\PHPat;

final readonly class MustBeFinal extends AbstractRule
{
    public function __construct(public array $selectables)
    {
        foreach ($this->selectables as $selectable) {
            if (!$selectable instanceof ClassSelector) {
                throw new InvalidArgumentException(
                    'Only classes can be final.'
                );
            }
        }
    }

    public static function fromGenerator(Generator $selectables): self
    {
        return new self(iterator_to_array($selectables));
    }

    public function getPHPatRule(string $groupName): PHPatRule
    {
        return PHPat::rule()
            ->classes(...self::getPHPSelectors($this->selectables))
            ->excluding(Selector::isInterface())
            ->shouldBeFinal()
            ->because("$groupName must be final.");
    }
}
