<?php
declare(strict_types=1);

namespace Kununu\ArchitectureTest\Configuration;

use Exception;
use InvalidArgumentException;
use JsonException;
use Kununu\ArchitectureTest\Configuration\Selector\Selectable;

final readonly class Layer
{
    public const string KEY = 'layer';

    public function __construct(
        public string $name,
        public Selectable $selector,
        public array $subLayers = [],
    ) {
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        $selector = Selectors::findSelector($data);

        if (empty($data[self::KEY])) {
            throw new InvalidArgumentException('Layer name is missing.');
        }

        return new self(
            name: $data[self::KEY],
            selector: $selector,
            subLayers: array_key_exists(SubLayer::KEY, $data) ?
                array_map(
                    static fn(array $subLayer) => SubLayer::fromArray($subLayer),
                    $data[SubLayer::KEY],
                ) : [],
        );
    }
}
