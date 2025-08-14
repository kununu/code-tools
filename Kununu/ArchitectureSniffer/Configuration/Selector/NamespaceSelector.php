<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Selector;

use InvalidArgumentException;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;

final readonly class NamespaceSelector implements Selectable
{
    use RegexTrait;

    public function __construct(public string $namespace)
    {
    }

    public function getPHPatSelector(): SelectorInterface
    {
        $namespace = $this->makeRegex($this->namespace);

        if (empty($namespace)) {
            throw new InvalidArgumentException('Namespace definition should not be an empty string.');
        }

        return Selector::inNamespace($namespace, $namespace !== $this->namespace);
    }

    public function getDefinition(): string
    {
        return $this->namespace;
    }
}
