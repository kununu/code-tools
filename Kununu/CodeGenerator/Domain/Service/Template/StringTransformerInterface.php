<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Domain\Service\Template;

interface StringTransformerInterface
{
    /**
     * Converts an operation ID to a class name (e.g., "getUserProfile" -> "GetUserProfile")
     */
    public function operationIdToClassName(string $operationId): string;

    /**
     * Extracts an entity name from an operation ID (e.g., "getUserProfile" -> "User")
     */
    public function extractEntityNameFromOperationId(string $operationId): string;

    /**
     * Converts snake_case to camelCase (e.g., "user_profile" -> "userProfile")
     */
    public function snakeToCamelCase(string $string): string;

    /**
     * Generates an output path by replacing placeholders in a pattern
     */
    public function generateOutputPath(string $pattern, string $basePath, array $variables): string;

    /**
     * Determines the appropriate namespace for a file based on its path
     */
    public function getDynamicNamespace(string $outputPath, string $basePath, string $baseNamespace): string;
}
