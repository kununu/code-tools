<?php
declare(strict_types=1);

namespace Kununu\ArchitectureTest;

use Kununu\ArchitectureTest\Configuration\Layer;
use Kununu\ArchitectureTest\Configuration\Rules\Rule;
use Kununu\ArchitectureTest\Configuration\SubLayer;
use InvalidArgumentException;
use PHPat\Test\Builder\Rule as PHPatRule;

final class ArchitectureTest
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
