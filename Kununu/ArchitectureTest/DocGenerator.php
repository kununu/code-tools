<?php
declare(strict_types=1);

use Kununu\ArchitectureTest\Configuration\Layer;
use Kununu\ArchitectureTest\DirectoryFinder;

require getProjectRoot() . '/vendor/autoload.php';

main();

/**
 * Entry point for script execution.
 */
function main(): void
{
    $outputDir = DirectoryFinder::getProjectDirectory() . '/doc/architecture';
    $outputFile = $outputDir . '/architecture-diagram.mmd';
    ensureDirectoryExists($outputDir);

    $architectureDefinition = DirectoryFinder::getArchitectureDefinition();
    $content = buildArchitectureDiagram($architectureDefinition);

    file_put_contents($outputFile, $content);
    echo 'Mermaid file generated at: ' . $outputFile . PHP_EOL;
}

/**
 * Gets the root directory for the project.
 */
function getProjectRoot(): string
{
    $directory = dirname(__DIR__);

    return explode('/services', $directory)[0] . '/services';
}

/**
 * Creates a directory if it doesn't exist.
 */
function ensureDirectoryExists(string $dir): void
{
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
    }
}

/**
 * Builds the full Mermaid architecture diagram content.
 */
function buildArchitectureDiagram(array $definition): string
{
    $layersTrack = [];
    $componentsTrack = [];
    $innerDependenciesTrack = [];
    $dependenciesTrack = [];
    $externalDependenciesTrack = [];
    $trackedRelations = [];

    $content = "C4Context\n";
    $content .= "title Architecture Diagram\n";

    // Track layer indexes
    $layerIndex = 0;
    foreach ($definition['architecture'] as $layerData) {
        $layer = Layer::fromArray($layerData);
        $layersTrack[$layer->selector->getName()] = $layerIndex++;
    }

    // Process main architecture layers
    $componentIndex = 0;
    $externalIndex = 0;

    foreach ($definition['architecture'] as $layerData) {
        $layer = Layer::fromArray($layerData);
        $content .= buildLayerContent(
            $layer,
            $layersTrack,
            $componentsTrack,
            $innerDependenciesTrack,
            $dependenciesTrack,
            $trackedRelations,
            $componentIndex,
            $externalIndex
        );
    }

    // Process deprecated layers
    $deprecatedComponentIds = [];
    if (!empty($definition['deprecated'])) {
        $content .= buildDeprecatedLayers(
            $definition['deprecated'],
            $dependenciesTrack,
            $externalDependenciesTrack,
            $deprecatedComponentIds,
            $layerIndex
        );
    } else {
        $externalDependenciesTrack = $dependenciesTrack;
    }

    // Process external dependencies
    $externalComponentsTrack = [];
    foreach ($externalDependenciesTrack as $container => $external) {
        $content .= buildExternalContainer($container, $external, $externalComponentsTrack, $layerIndex);
    }

    // Build relations
    $trackedInnerRelations = buildInnerRelations($innerDependenciesTrack, $componentsTrack);
    $relations = buildRelations($trackedRelations, $externalComponentsTrack, $deprecatedComponentIds);
    $relations .= buildInnerRelationsContent($trackedInnerRelations);

    return $content . $relations;
}

/**
 * Builds Mermaid content for a single layer.
 */
function buildLayerContent(
    Layer $layer,
    array &$layersTrack,
    array &$componentsTrack,
    array &$innerDependenciesTrack,
    array &$dependenciesTrack,
    array &$trackedRelations,
    int &$componentIndex,
    int &$externalIndex,
): string {
    $content = "Container_Boundary(b{$layersTrack[$layer->selector->getName()]}, \"{$layer->name}\"){\n";

    foreach ($layer->subLayers as $subLayer) {
        $componentsTrack[$subLayer->selector->getName()] = $componentIndex;
        $trackedRelations[$componentIndex] = [];
        $innerDependenciesTrack[$componentIndex] = [];

        $finalText = hasFinalRule($subLayer) ? '🔒 ' : '';
        processWhitelistRules(
            $subLayer,
            $layer,
            $layersTrack,
            $innerDependenciesTrack,
            $dependenciesTrack,
            $trackedRelations,
            $componentIndex,
            $externalIndex
        );

        $content .= "    Component(\"c{$componentIndex}\", \"{$finalText}{$subLayer->name}\", \"{$subLayer->selector->getName()}\")\n";
        ++$componentIndex;
    }

    return $content . "}\n\n";
}

/**
 * Checks if a sub-layer has a "MustBeFinal" rule.
 */
function hasFinalRule($subLayer): bool
{
    foreach ($subLayer->rules as $rule) {
        if ($rule instanceof Kununu\ArchitectureTest\Configuration\Rules\MustBeFinal) {
            return true;
        }
    }

    return false;
}

/**
 * Processes dependency whitelist rules for a sub-layer.
 */
function processWhitelistRules(
    $subLayer,
    Layer $layer,
    array $layersTrack,
    array &$innerDependenciesTrack,
    array &$dependenciesTrack,
    array &$trackedRelations,
    int $componentIndex,
    int &$externalIndex,
): void {
    foreach ($subLayer->rules as $rule) {
        if (!$rule instanceof Kununu\ArchitectureTest\Configuration\Rules\MustOnlyDependOnWhitelist) {
            continue;
        }

        foreach ($rule->dependencyWhitelist as $dependency) {
            $name = trim($dependency->getName(), '\\');
            if (str_starts_with($name, '*')) {
                continue;
            }

            if (str_starts_with($name, 'App')) {
                $outerLayerOfDependency = 'App\\' . explode('\\', $name)[1];
                if ($outerLayerOfDependency === $layer->selector->getName()) {
                    continue;
                }
                if (isset($layersTrack[$outerLayerOfDependency])) {
                    $innerDependenciesTrack[$componentIndex][$subLayer->selector->getName()] = $dependency->getName();
                    continue;
                }
                if (!isset($dependenciesTrack[$name])) {
                    $dependenciesTrack[$name] = $externalIndex++;
                }
                $trackedRelations[$componentIndex][] = $dependenciesTrack[$name];
                continue;
            }

            if (!isset($dependenciesTrack[$name])) {
                $dependenciesTrack[$name] = $externalIndex++;
            }
            $trackedRelations[$componentIndex][] = $dependenciesTrack[$name];
        }
    }
}
