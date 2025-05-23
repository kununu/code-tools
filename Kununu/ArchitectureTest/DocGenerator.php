<?php
declare(strict_types=1);

$directory = dirname(__DIR__);
$root = explode('/services', $directory)[0] . '/services';

require $root . '/vendor/autoload.php';

use Kununu\ArchitectureTest\DirectoryFinder;
use Kununu\ArchitectureTest\Configuration\Layer;

$outputDir = DirectoryFinder::getProjectDirectory() . '/doc/architecture';
$outputFile = $outputDir . '/architecture-diagram.mmd';

if (!is_dir($outputDir)) {
    if (!mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
        throw new RuntimeException(sprintf('Directory "%s" was not created', $outputDir));
    }
}

$architectureDefinition = DirectoryFinder::getArchitectureDefinition();

$content = "C4Context\n";
$content .= "title Architecture Diagram\n";
$layersTrack = [];
$componentsTrack = [];
$innerDependenciesTrack = [];
$dependenciesTrack = [];
$externalDependenciesTrack = [];
$trackedRelations = [];
$relations = "";
$i = 0;
$j = 0;
$k = 0;
foreach ($architectureDefinition['architecture'] as $layerData) {
    $layer = Layer::fromArray($layerData);
    $layersTrack[$layer->selector->getName()] = $i;
    $i++;
}

foreach ($architectureDefinition['architecture'] as $layerData) {
    $layer = Layer::fromArray($layerData);
    $outerLayerNr = $layersTrack[$layer->selector->getName()];
    $content .= "Container_Boundary(b$outerLayerNr, \"{$layer->name}\"){\n";

    /** @var \Kununu\ArchitectureTest\Configuration\SubLayer $subLayer */
    foreach ($layer->subLayers as $subLayer) {
        $componentsTrack[$subLayer->selector->getName()] = $j;
        $trackedRelations[$j] = [];
        $innerDependenciesTrack[$j] = [];
        $finalText = "";
        foreach($subLayer->rules as $rule) {
            if ($rule instanceof \Kununu\ArchitectureTest\Configuration\Rules\MustBeFinal) {
                $finalText = "🔒 ";
            }

            if ($rule instanceof \Kununu\ArchitectureTest\Configuration\Rules\MustOnlyDependOnWhitelist) {
                /** @var \Kununu\ArchitectureTest\Configuration\Selector\Selectable $dependency */
                foreach ($rule->dependencyWhitelist as $dependency) {
                    $name = $dependency->getName();
                    if (str_starts_with($name, "\\")) {
                        $name = substr($dependency->getName(), 1);
                    }
                    if (str_ends_with($name, "\\")) {
                        $name = substr($name, 0, -1);
                    }
                    if (str_starts_with($name, '*')) {
                        continue;
                    }

                    if (str_starts_with($name, 'App')) {
                        $outerLayerOfDependency = "App\\" . explode('\\', $name)[1];
                        $currentOuterLayer = $layer->selector->getName();
                        if ($outerLayerOfDependency === $currentOuterLayer) {
                            continue;
                        }
                        if (in_array($outerLayerOfDependency, array_keys($layersTrack), true)) {
                            $innerDependenciesTrack[$j][$subLayer->selector->getName()] = $dependency->getName();
                            continue;
                        }
                        $dependenciesTrack[$name] = $k;
                        $trackedRelations[$j][] = $k;
                        $k++;
                        continue;
                    }
                    if (!array_key_exists($name, $dependenciesTrack)) {
                        $dependenciesTrack[$name] = $k;
                        $trackedRelations[$j][] = $k;
                        $k++;
                    }
                    $number = $dependenciesTrack[$name];
                    $trackedRelations[$j][] = $number;
                }
            }
        }
        $content .= "    Component(\"c$j\", \"$finalText{$subLayer->name}\", \"{$subLayer->selector->getName()}\")\n";
        $j++;
    }
    $content .= "}\n\n"; // End the main layer block
}

$depricatedComponentIds = [];
if (array_key_exists('deprecated', $architectureDefinition)) {
    foreach ($architectureDefinition['deprecated'] as $layerData) {
        $layer = Layer::fromArray($layerData);
        $layersCount[$layer->selector->getName()] = $i;
        $content .= "Container_Boundary(b$i, \"{$layerData['layer']}\"){\n";
        foreach($dependenciesTrack as $name => $number) {
            if (str_starts_with($name, $layer->selector->getName())) {
                $nameSpaces = explode('\\', $name);
                $className = $nameSpaces[count($nameSpaces) - 1];
                $content .= "    Component(\"e$number\", \"Deprecated $className\", \"$name\")\n";
                $depricatedComponentIds[] = $number;
            } else {
                if (str_starts_with($name, 'App')) {
                    continue;
                }
                $externalContainer = explode('\\', $name)[0];
                if (!array_key_exists($externalContainer, $externalDependenciesTrack)) {
                    $externalDependenciesTrack[$externalContainer] = [
                        $name => $number
                    ];
                    continue;
                }
                $externalDependenciesTrack[$externalContainer][$name] = $number;
            }
        }
        $content .= "}\n\n"; // End the main layer block
        $i++;
    }
} else {
    $externalDependenciesTrack = $dependenciesTrack;
}

$externalComponentsTrack = [];
foreach ($externalDependenciesTrack as $container => $external) {
    $content .= "Container_Boundary(b$i, \"{$container}\"){\n";
    foreach ($external as $name => $number) {
        $nameSpaces = explode('\\', $name);
        $className = $nameSpaces[count($nameSpaces) - 1];
        $content .= "    Component(\"e$number\", \"$className\", \"$name\")\n";
        $externalComponentsTrack[$name] = $number;
    }
    $content .= "}\n\n"; // End the main layer block
    $i++;
}

$trackedInnerRelations = [];
foreach ($innerDependenciesTrack as $componentNumber => $connections) {
    $trackedInnerRelations[$componentNumber] = [];
    foreach($connections as $sourceComponent => $dependency) {
        $nameSpaces = explode('\\', $dependency);
        foreach ($componentsTrack as $component => $externalNumber) {
            if (count($nameSpaces) < 3 || count_chars($nameSpaces[2]) < 2) {
                $componentOuterLayer = 'App\\' . explode('\\', $component)[1];
                if ($componentOuterLayer === $dependency) {
                    $trackedInnerRelations[$componentNumber][] = $externalNumber;
                }
                continue;
            }
            $shortedName = $nameSpaces[0] . '/' . $nameSpaces[1] . '/' . $nameSpaces[2];
            if (str_starts_with($component, $shortedName)) {
                $nameSpaces = explode('\\', $name);
                $className = $nameSpaces[count($nameSpaces) - 1];
                $trackedInnerRelations[$componentNumber][] = $externalNumber;
            }
        }
    }
}

foreach($trackedRelations as $number => $externalNumbers) {
    foreach(array_unique($externalNumbers) as $externalNumber) {
        if (!in_array($externalNumber, $externalComponentsTrack, true) &&
            !in_array($externalNumber, $depricatedComponentIds, true)) {
            var_dump("Missing external Component nr $externalNumber");
            continue;
        }
        $relations .= "Rel(c$number, e$externalNumber, \"Can depend on\", \"DI\")\n";
        if (in_array($externalNumber, $depricatedComponentIds, true)) {
            $relations .= "UpdateRelStyle(c$number, e$externalNumber, \$textColor=\"red\", \$offsetY=\"-40\")\n";
        }
    }
}

foreach ($trackedInnerRelations as $number => $innerNumbers) {
    foreach(array_unique($innerNumbers) as $innerNumber) {
        $relations .= "Rel(c$number, c$innerNumber, \"Can depend on\", \"DI\")\n";
    }
}

$content .= $relations;

file_put_contents($outputFile, $content);

echo 'Mermaid file generated at: ' . $outputFile . PHP_EOL;
