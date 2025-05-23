<?php
declare(strict_types=1);

namespace Kununu\ArchitectureTest\Configuration;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;

final readonly class NamespaceSelector implements Selectable
{
    use RegexTrait;

    public const string KEY = 'namespace';

    public function __construct(
        public string $name,
        public string $namespace,
    ) {
    }

    public function getPHPatSelector(): SelectorInterface
    {
        $namespace = $this->makeRegex($this->namespace);

        return Selector::inNamespace($namespace, $namespace !== $this->namespace);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
