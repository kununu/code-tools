<?php
declare(strict_types=1);

namespace Kununu\ArchitectureTest;

use Kununu\ArchitectureTest\Configuration\Layer;
use Kununu\ArchitectureTest\Configuration\Rules\Rule;
use Kununu\ArchitectureTest\Configuration\SubLayer;
use InvalidArgumentException;
use PHPat\Test\Builder\Rule as PHPatRule;
use Symfony\Component\Yaml\Yaml;

final class ConfigurableArchitectureTest
{
    private const string ARCHITECTURE_DEFINITION_FILE = '/arch_definition.yaml';

    /**
     * @return iterable<PHPatRule>
     */
    public function testArchitecture(): iterable
    {
        $archDefinition = self::getArchitectureDefinition();
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

    public static function getProjectDirectory(): string
    {
        $directory = dirname(__DIR__);

        return explode('/services', $directory)[0] . '/services';
    }

    public static function getArchitectureDefinitionFile(): string
    {
        return self::getProjectDirectory() . self::ARCHITECTURE_DEFINITION_FILE;
    }

    private static function getArchitectureDefinition(): array
    {
        $filePath = self::getArchitectureDefinitionFile();

        if (!file_exists($filePath)) {
            throw new InvalidArgumentException(
                'ArchitectureTest definition file not found, please create it at ' . $filePath
            );
        }

        return Yaml::parseFile($filePath);
    }
}
