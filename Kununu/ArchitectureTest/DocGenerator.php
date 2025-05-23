<?php
declare(strict_types=1);

$directory = dirname(__DIR__);
$root = explode('/services', $directory)[0] . '/services';

require $root . '/vendor/autoload.php';

use Kununu\ArchitectureTest\DirectoryFinder;

$outputDir = DirectoryFinder::getProjectDirectory() . '/doc/architecture';
$outputFile = $outputDir . '/architecture.mmd';

if (!is_dir($outputDir)) {
    if (!mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
        throw new RuntimeException(sprintf('Directory "%s" was not created', $outputDir));
    }
}

$architectureDefinition = DirectoryFinder::getArchitectureDefinitionFile();

$mmdContent = "C4Context\n";
function addLayerToMmd(array $layers, &$content): void
{
    foreach ($layers as $layer) {
        $content .= "  title Architecture Diagram\n";
        $content .= "  Enterprise_Boundary(b0, \"{$layer['layer']}\"){\n";

        $content .= "  }\n\n"; // End the main layer block
    }
}

addLayerToMmd($architectureDefinition['architecture'], $mmdContent);

file_put_contents($outputFile, $mmdContent);

echo 'Mermaid file generated at: ' . $outputFile . PHP_EOL;
