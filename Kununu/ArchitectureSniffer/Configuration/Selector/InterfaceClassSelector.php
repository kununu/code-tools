<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Selector;

use InvalidArgumentException;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;

final readonly class InterfaceClassSelector implements Selectable
{
    use RegexTrait;

    public const KEY = 'InterfaceSelector';

    public function __construct(public string $interface)
    {
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

    public function getDefinition(): string
    {
        return $this->interface;
    }
}
