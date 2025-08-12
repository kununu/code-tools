<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Selector;

use InvalidArgumentException;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;

final readonly class InterfaceClassSelector implements Selectable
{
    use RegexTrait;

    public const string KEY = 'interface';

    public function __construct(
        public string $name,
        public string $interface,
    ) {
    }

    public function getPHPatSelector(): SelectorInterface
    {
        $interface = $this->makeRegex($this->interface);

        if (empty($interface)) {
            throw new InvalidArgumentException('Interface definition should not be an empty string.');
        }

        return Selector::AllOf(
            Selector::classname($interface, $interface !== $this->interface),
            Selector::isInterface(),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }
}
