<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration;

use Exception;
use InvalidArgumentException;
use JsonException;
use Kununu\ArchitectureSniffer\Configuration\Rules\MustBeFinal;
use Kununu\ArchitectureSniffer\Configuration\Rules\MustExtend;
use Kununu\ArchitectureSniffer\Configuration\Rules\MustImplement;
use Kununu\ArchitectureSniffer\Configuration\Rules\MustOnlyDependOnWhitelist;
use Kununu\ArchitectureSniffer\Configuration\Rules\MustOnlyHaveOnePublicMethodNamed;
use Kununu\ArchitectureSniffer\Configuration\Selector\Selectable;

final readonly class SubLayer
{
    public const string KEY = 'sub-layers';
    public const string NAME_KEY = 'name';

    public function __construct(
        public string $name,
        public Selectable $selector,
        public array $rules = [],
    ) {
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public static function fromArray(array $subLayer): SubLayer
    {
        $rules = [];
        $selector = Selectors::findSelector($subLayer);
        foreach ($subLayer as $key => $item) {
            if (in_array($key, Selectors::getValidTypes(), true)) {
                continue;
            }
            match ($key) {
                self::NAME_KEY   => $name = $item,
                MustBeFinal::KEY => $item !== true ?:
                    $rules[] = MustBeFinal::fromArray($selector),
                MustExtend::KEY                       => $rules[] = MustExtend::fromArray($selector, $item),
                MustImplement::KEY                    => $rules[] = MustImplement::fromArray($selector, $item),
                MustOnlyDependOnWhitelist::KEY        => $rules[] = MustOnlyDependOnWhitelist::fromArray(
                    $selector,
                    $item
                ),
                MustOnlyHaveOnePublicMethodNamed::KEY => $rules[] = MustOnlyHaveOnePublicMethodNamed::fromArray(
                    $selector,
                    $item
                ),
                default                               => throw new Exception("Unknown key: $key"),
            };
        }

        if (empty($name)) {
            throw new InvalidArgumentException('Missing name for sub layer');
        }

        return new self(
            name: $name,
            selector: $selector,
            rules: $rules,
        );
    }
}
