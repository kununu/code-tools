<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Infrastructure\Template;

use Kununu\CodeGenerator\Domain\Service\Template\StringTransformerInterface;

final class StringTransformer implements StringTransformerInterface
{
    public function operationIdToClassName(string $operationId): string
    {
        if (empty($operationId)) {
            return '';
        }

        // Properly capitalize each word in camelCase strings
        // e.g., "getToneOfVoiceSettings" becomes "GetToneOfVoiceSettings"
        $parts = preg_split('/(?=[A-Z])/', $operationId, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts)) {
            return ucfirst($operationId);
        }

        // Properly capitalize the first part which likely doesn't start with uppercase
        $parts[0] = ucfirst($parts[0]);

        return implode('', $parts);
    }

    public function extractEntityNameFromOperationId(string $operationId): string
    {
        if (empty($operationId)) {
            return '';
        }

        // Remove common prefixes
        $name = (string) preg_replace('/^(get|create|update|delete|find|list)/', '', $operationId);

        // Remove common suffixes
        $name = (string) preg_replace('/(List|Collection|Item|By.*)$/', '', $name);

        // Return the first part of the remaining string (likely the entity name)
        $matches = [];
        if (preg_match('/^([A-Z][a-z0-9]+)/', ucfirst($name), $matches)) {
            return $matches[1];
        }

        return ucfirst($name);
    }

    public function snakeToCamelCase(string $string): string
    {
        if (empty($string)) {
            return $string;
        }

        $string = ltrim($string, '_');

        return lcfirst(str_replace('_', '', lcfirst(ucwords($string, '_'))));
    }

    public function generateOutputPath(string $pattern, string $basePath, array $variables): string
    {
        // Replace all placeholders in the pattern
        $output = $pattern;

        // Always replace basePath
        $output = str_replace('{basePath}', $basePath, $output);

        // Replace operation name if available
        if (isset($variables['operation_id'])) {
            $operationName = $this->operationIdToClassName($variables['operation_id']);
            $output = str_replace('{operationName}', $operationName, $output);
        }

        // Replace entity name if available
        if (isset($variables['entity_name'])) {
            $entityName = $this->operationIdToClassName($variables['entity_name']);
            $output = str_replace('{entityName}', $entityName, $output);
        }

        // Replace method if available
        if (isset($variables['method'])) {
            $output = str_replace('{method}', strtolower($variables['method']), $output);
        }

        // Replace CQRS type if available
        if (isset($variables['cqrsType'])) {
            $output = str_replace('{cqrsType}', $variables['cqrsType'], $output);
        }

        return $output;
    }

    public function getDynamicNamespace(string $outputPath, string $basePath, string $baseNamespace): string
    {
        // Normalize paths to use consistent directory separators
        $normalizedOutputPath = str_replace('\\', '/', $outputPath);
        $normalizedBasePath = str_replace('\\', '/', $basePath);

        // Special handling for test files
        if (str_contains($normalizedOutputPath, 'tests/')) {
            return $this->getTestFileNamespace($normalizedOutputPath, $baseNamespace);
        }

        // Remove base path from the output path
        $relativePath = $normalizedBasePath ?
            preg_replace('#^' . preg_quote($normalizedBasePath . '/', '#') . '#', '', $normalizedOutputPath) :
            $normalizedOutputPath;

        // Get the directory part of the path (remove the file name)
        $directory = dirname((string) $relativePath);

        // If we're at the root directory (.), use the base namespace
        if ($directory === '.') {
            return $baseNamespace;
        }

        // Convert directory separators to namespace separators
        $namespaceSegments = explode('/', $directory);

        // Remove any empty segments or segments that would create redundancy
        $namespaceSegments = array_filter($namespaceSegments, function($segment) {
            return !empty($segment) && $segment !== '.';
        });

        // Convert to namespace format
        $namespaceAddition = implode('\\', $namespaceSegments);

        // Check for common redundancy patterns in namespaces
        return $this->normalizeNamespace($baseNamespace, $namespaceAddition);
    }

    private function getTestFileNamespace(string $outputPath, string $baseNamespace): string
    {
        // Handle tests directory specially
        $parts = explode('tests/', $outputPath, 2);
        if (count($parts) > 1) {
            $testPath = dirname($parts[1]);

            // Convert to namespace format
            $testNamespaceSegments = explode('/', $testPath);

            // Remove any empty segments
            $testNamespaceSegments = array_filter($testNamespaceSegments, function($segment) {
                return !empty($segment) && $segment !== '.';
            });

            $testNamespace = implode('\\', $testNamespaceSegments);

            // Check if Tests is already in the namespace to avoid duplication
            if (!str_starts_with($testNamespace, 'Tests\\') && !str_starts_with($testNamespace, 'Test\\')) {
                $testNamespace = 'Tests\\' . $testNamespace;
            }

            return $this->normalizeNamespace($baseNamespace, $testNamespace);
        }

        return $baseNamespace . '\\Tests';
    }

    private function normalizeNamespace(string $baseNamespace, string $namespaceAddition): string
    {
        if (empty($namespaceAddition)) {
            return $baseNamespace;
        }

        // Split into segments for analysis
        $baseSegments = explode('\\', $baseNamespace);
        $additionSegments = explode('\\', $namespaceAddition);

        // Check for redundancy at the beginning of the addition
        $redundantSegments = 0;
        $baseSegmentsCount = count($baseSegments);

        foreach ($additionSegments as $segment) {
            // Check if this segment would be redundant with the base namespace
            $baseIndex = $baseSegmentsCount - $redundantSegments - 1;
            if ($baseIndex >= 0 && strtolower($baseSegments[$baseIndex]) === strtolower($segment)) {
                ++$redundantSegments;
            } else {
                break;
            }
        }

        // Remove redundant segments
        if ($redundantSegments > 0) {
            $additionSegments = array_slice($additionSegments, $redundantSegments);
        }

        // Build the final namespace
        $finalNamespace = $baseNamespace;

        if (!empty($additionSegments)) {
            $finalNamespace .= '\\' . implode('\\', $additionSegments);
        }

        return $finalNamespace;
    }
}
