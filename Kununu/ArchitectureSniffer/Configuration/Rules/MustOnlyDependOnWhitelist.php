<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Rules;

use JsonException;
use Kununu\ArchitectureSniffer\Configuration\Selector\Selectable;
use Kununu\ArchitectureSniffer\Configuration\Selectors;
use PHPat\Selector\Selector;
use PHPat\Test\PHPat;

final readonly class MustOnlyDependOnWhitelist implements Rule
{
    public const string KEY = 'dependency-whitelist';

    /**
     * @param Selectable[] $dependencyWhitelist
     */
    public function __construct(
        public Selectable $selector,
        public array $dependencyWhitelist,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     *
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
