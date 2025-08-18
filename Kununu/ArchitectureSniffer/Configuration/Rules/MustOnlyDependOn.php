<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use Generator;
use PHPat\Selector\Selector;
use PHPat\Test\PHPat;

final readonly class MustOnlyDependOn extends AbstractRule
{
    public function __construct(
        public array $selectables,
        public array $dependencies,
        public ?array $excludes = null,
    ) {
    }

    public static function fromGenerators(
        Generator $selectables,
        Generator $dependencies,
        ?Generator $extends = null,
        ?Generator $implements = null,
        ?Generator $excludes = null,
    ): self {
        $iteratedSelectables = iterator_to_array($selectables);
        $unitedDependencies = array_merge(
            iterator_to_array($dependencies),
            $iteratedSelectables,
            $extends ? iterator_to_array($extends) : [],
            $implements ? iterator_to_array($implements) : []
        );

        return new self(
            $iteratedSelectables,
            $unitedDependencies,
            $excludes ? iterator_to_array($excludes) : null
        );
    }

    public function getPHPatRule(string $groupName): \PHPat\Test\Builder\Rule
    {
        $allowed = array_merge(
            [Selector::classname('/^\\\\*[^\\\\]+$/', true)],
            self::getPHPSelectors($this->dependencies)
        );

        return PHPat::rule()
            ->classes(...self::getPHPSelectors($this->selectables))
            ->excluding(...$this->excludes)
            ->canOnlyDependOn()
            ->classes(...$allowed)
            ->because("$groupName has dependencies outside the allowed list.");
    }
}
