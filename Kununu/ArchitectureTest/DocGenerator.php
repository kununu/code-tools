<?php
declare(strict_types=1);

$directory = dirname(__DIR__);
$root = explode('/services', $directory)[0] . '/services';

require $root . '/vendor/autoload.php';

use Kununu\ArchitectureTest\ConfigurableArchitectureTest;
use Symfony\Component\Yaml\Yaml;

$outputDir = ConfigurableArchitectureTest::getProjectDirectory() . '/doc/architecture';
$outputFile = $outputDir . '/architecture.mmd';

if (!is_dir($outputDir)) {
    if (!mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
        throw new RuntimeException(sprintf('Directory "%s" was not created', $outputDir));
    }
}

$yamlFile = ConfigurableArchitectureTest::getArchitectureDefinitionFile();
$architectureDefinition = Yaml::parseFile($yamlFile);

$mmdContent = "%%{init: {'theme': 'default'}}%%\n"; // Optional: Set theme for Mermaid
$mmdContent .= "block-beta\n";
// Function to recursively add layers to MMD content
function addLayerToMmd(array $layers, &$content): void
{
    foreach ($layers as $layer) {
        // Add main layer as a block
        $content .= "  block: {$layer['layer']}\n";

        // Check for sub-layers and add them
        if (isset($layer['sub-layers'])) {
            foreach ($layer['sub-layers'] as $subLayer) {
                // Add sub-layer as a connected block
                $content .= "    block: {$subLayer['layer']}\n";

                // Simulate dependencies linking with arrows
                if (isset($subLayer['dependencyWhitelist'])) {
                    foreach ($subLayer['dependencyWhitelist'] as $whitelist) {
                        if (isset($whitelist['class'])) {
                            $content .= sprintf(
                                "      %s --> %s\n",
                                $subLayer['layer'],
                                $whitelist['class']
                            );
                        } elseif (isset($whitelist['namespace'])) {
                            $content .= sprintf(
                                "      %s --> %s\n",
                                $subLayer['layer'],
                                $whitelist['namespace']
                            );
                        }
                    }
                }

                // Add extends information as a comment or metadata (not as part of block)
                if (isset($subLayer['extends'])) {
                    $content .= sprintf(
                        "      %% Extends: %s %%\n",
                        $subLayer['extends']['class']
                    );
                }

                // Add implements information as a comment or metadata
                if (isset($subLayer['implements'])) {
                    foreach ($subLayer['implements'] as $implement) {
                        $content .= sprintf(
                            "      %% Implements: %s %%\n",
                            $implement['class']
                        );
                    }
                }

                $content .= "    end\n"; // End sub-layer block
            }
        }

        $content .= "  end\n\n"; // End the main layer block
    }
}

addLayerToMmd($architectureDefinition['architecture'], $mmdContent);

file_put_contents($outputFile, $mmdContent);

echo 'Mermaid file generated at: ' . $outputFile . PHP_EOL;
