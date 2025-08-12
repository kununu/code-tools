<?php
declare(strict_types=1);

namespace Kununu\ArchitectureTest\Configuration\Rules;

use JsonException;
use Kununu\ArchitectureTest\Configuration\Selector\Selectable;
use Kununu\ArchitectureTest\Configuration\Selectors;
use PHPat\Selector\Selector;
use PHPat\Test\PHPat;

final readonly class MustOnlyDependOnWhitelist implements Rule
{
    public const string KEY = 'dependency-whitelist';

    public function __construct(
        public Selectable $selector,
        public array $dependencyWhitelist,
    ) {
    }

    /**
     * @throws JsonException
     */
    public static function fromArray(Selectable $selector, array $data): self
    {
        $dependencies = [];
        foreach ($data as $dependency) {
            $dependencySelector = Selectors::findSelector($dependency);
            $dependencies[] = $dependencySelector;
        }

        return new self($selector, $dependencies);
    }

    public function getPHPatRule(): \PHPat\Test\Builder\Rule
    {
        $dependentsString = implode(', ', array_map(
            static fn(Selectable $dependency): string => $dependency->getName(),
            $this->dependencyWhitelist
        ));

        $selectors = array_map(
            static fn(Selectable $dependency) => $dependency->getPHPatSelector(),
            $this->dependencyWhitelist
        );
        $selectors[] = Selector::classname('/^\\\\*[^\\\\]+$/', true);

        return PHPat::rule()
            ->classes($this->selector->getPHPatSelector())
            ->canOnlyDependOn()
            ->classes(...$selectors)
            ->because("{$this->selector->getName()} should only depend on $dependentsString.");
    }
}
