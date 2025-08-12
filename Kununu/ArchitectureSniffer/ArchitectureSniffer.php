<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer;

use InvalidArgumentException;
use JsonException;
use Kununu\ArchitectureSniffer\Configuration\Layer;
use PHPat\Test\Builder\Rule as PHPatRule;

final class ArchitectureSniffer
{
    /**
     * @throws JsonException
     *
     * @return iterable<PHPatRule>
     */
    public function testArchitecture(): iterable
    {
        $archDefinition = DirectoryFinder::getArchitectureDefinition();
        $layers = $this->validateArchitectureDefinition($archDefinition);
        foreach ($layers as $layer) {
            foreach ($layer->subLayers as $subLayer) {
                foreach ($subLayer->rules as $rule) {
                    yield $rule->getPHPatRule();
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $architectureDefinition
     *
     * @throws JsonException
     *
     * @return Layer[]
     */
    private function validateArchitectureDefinition(array $architectureDefinition): array
    {
        if (!array_key_exists('architecture', $architectureDefinition)) {
            throw new InvalidArgumentException('Invalid architecture definition, missing architecture key');
        }

        $layers = [];
        foreach ($architectureDefinition['architecture'] as $layer) {
            $layers[] = Layer::fromArray($layer);
        }

        return $layers;
    }
}
