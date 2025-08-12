<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Selector;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;

final readonly class InterfaceClassSelector implements Selectable
{
    use RegexTrait;

    public const string KEY = 'interface';

    public function __construct(
        public string $name,
        public string $namespace,
    ) {
    }

    public function getPHPatSelector(): SelectorInterface
    {
        $namespace = $this->makeRegex($this->namespace);

        return Selector::AllOf(
            Selector::classname($namespace, $namespace !== $this->namespace),
            Selector::isInterface(),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }
}
