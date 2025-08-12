<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Selector;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;

final readonly class ClassSelector implements Selectable
{
    use RegexTrait;

    public const string KEY = 'class';

    public function __construct(
        public string $name,
        public string $namespace,
    ) {
    }

    public function getPHPatSelector(): SelectorInterface
    {
        $namespace = $this->makeRegex($this->namespace);

        return Selector::classname($namespace, $namespace !== $this->namespace);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
