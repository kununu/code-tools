<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Selector;

use InvalidArgumentException;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;

final readonly class ClassSelector implements Selectable
{
    use RegexTrait;

    public const string KEY = 'ClassSelector';

    public function __construct(public string $class)
    {
    }

    public function getPHPatSelector(): SelectorInterface
    {
        $class = $this->makeRegex($this->class);

        if (empty($class)) {
            throw new InvalidArgumentException('Class definition should not be an empty string.');
        }

        return Selector::classname($class, $class !== $this->class);
    }

    public function getDefinition(): string
    {
        return $this->class;
    }
}
