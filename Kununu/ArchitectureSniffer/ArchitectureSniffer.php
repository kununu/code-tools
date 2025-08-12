<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer;

use Kununu\ArchitectureSniffer\Configuration\Layer;
use Kununu\ArchitectureSniffer\Configuration\Rules\Rule;
use Kununu\ArchitectureSniffer\Configuration\SubLayer;
use InvalidArgumentException;
use PHPat\Test\Builder\Rule as PHPatRule;

final class ArchitectureSniffer
{
    /**
     * @return iterable<PHPatRule>
     */
    public function testArchitecture(): iterable
    {
        $archDefinition = DirectoryFinder::getArchitectureDefinition();
        $layers = $this->validateArchitectureDefinition($archDefinition);
        /** @var Layer $layer */
        foreach ($layers as $layer) {
            /** @var SubLayer $subLayer */
            foreach ($layer->subLayers as $subLayer) {
                /** @var Rule $rule */
                foreach ($subLayer->rules as $rule) {
                    yield $rule->getPHPatRule();
                }
            }
        }
    }

    /**
     * @throws \JsonException
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
