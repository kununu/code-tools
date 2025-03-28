<?php

declare(strict_types=1);

namespace Kununu\CodeGenerator\Application\Service;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use RuntimeException;

/**
 * Parser for OpenAPI specifications - supports both 3.0 and 3.1 versions
 *
 * This class parses OpenAPI specification files and extracts operation details
 * to be used for code generation. It handles the differences between OpenAPI 3.0 and 3.1
 * schema formats, including:
 *
 * - Multiple type definitions (array of types in 3.1)
 * - Nullable properties
 * - Composition schemas (oneOf, anyOf, allOf)
 *
 * Uses devizzent/cebe-php-openapi for the underlying parsing.
 */
final class OpenApiParser
{
    private ?OpenApi $openApi = null;

    public function parseFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException(sprintf('OpenAPI file not found at %s', $filePath));
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        $this->openApi = match (strtolower($extension)) {
            'json' => Reader::readFromJsonFile($filePath),
            'yaml', 'yml' => Reader::readFromYamlFile($filePath),
            default => throw new RuntimeException(sprintf('Unsupported file extension: %s', $extension)),
        };

        // Validate except for strict mode, as OpenAPI 3.1 might introduce new fields
        $validationOptions = [
            'validateSchema'   => true,
            'validateSpec'     => true,
            'validateSemantic' => true,
            'strictValidation' => false, // Set to false to handle OpenAPI 3.1 extensions
        ];

        if (!$this->openApi->validate($validationOptions)) {
            $errors = $this->openApi->getErrors();
            throw new RuntimeException(sprintf(
                'Invalid OpenAPI specification: %s',
                implode(', ', $errors)
            ));
        }
    }

    public function listOperations(): array
    {
        if ($this->openApi === null) {
            throw new RuntimeException('OpenAPI specification not loaded. Call parseFile() first.');
        }

        $operations = [];

        foreach ($this->openApi->paths as $path => $pathItem) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                if (isset($pathItem->$method)) {
                    $operation = $pathItem->$method;

                    if (isset($operation->operationId)) {
                        $operations[] = [
                            'id'          => $operation->operationId,
                            'path'        => $path,
                            'method'      => strtoupper($method),
                            'summary'     => $operation->summary ?? '',
                            'description' => $operation->description ?? '',
                        ];
                    }
                }
            }
        }

        return $operations;
    }

    public function getOperationById(string $operationId): array
    {
        if ($this->openApi === null) {
            throw new RuntimeException('OpenAPI specification not loaded. Call parseFile() first.');
        }

        foreach ($this->openApi->paths as $path => $pathItem) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                if (isset($pathItem->$method) && $pathItem->$method->operationId === $operationId) {
                    $operation = $pathItem->$method;

                    $operationDetails = [
                        'id'          => $operation->operationId,
                        'path'        => $path,
                        'method'      => strtoupper($method),
                        'summary'     => $operation->summary ?? '',
                        'description' => $operation->description ?? '',
                        'parameters'  => [],
                        'responses'   => [],
                    ];

                    // Parse parameters
                    if (isset($operation->parameters)) {
                        foreach ($operation->parameters as $parameter) {
                            $operationDetails['parameters'][] = [
                                'name'        => $parameter->name,
                                'in'          => $parameter->in,
                                'required'    => $parameter->required ?? false,
                                'description' => $parameter->description ?? '',
                                'schema'      => $this->extractSchema($parameter->schema ?? null),
                            ];
                        }
                    }

                    // Parse request body
                    if (isset($operation->requestBody)) {
                        $operationDetails['requestBody'] = [
                            'description' => $operation->requestBody->description ?? '',
                            'required'    => $operation->requestBody->required ?? false,
                            'content'     => [],
                        ];

                        if (isset($operation->requestBody->content)) {
                            foreach ($operation->requestBody->content as $mediaType => $content) {
                                $operationDetails['requestBody']['content'][$mediaType] = [
                                    'schema' => $this->extractSchema($content->schema ?? null),
                                ];
                            }
                        }
                    }

                    // Parse responses
                    if (isset($operation->responses)) {
                        foreach ($operation->responses as $statusCode => $response) {
                            $responseDetails = [
                                'description' => $response->description ?? '',
                                'content'     => [],
                            ];

                            if (isset($response->content)) {
                                foreach ($response->content as $mediaType => $content) {
                                    $responseDetails['content'][$mediaType] = [
                                        'schema' => $this->extractSchema($content->schema ?? null),
                                    ];
                                }
                            }

                            $operationDetails['responses'][$statusCode] = $responseDetails;
                        }
                    }

                    return $operationDetails;
                }
            }
        }

        throw new RuntimeException(sprintf('Operation with ID "%s" not found in OpenAPI specification', $operationId));
    }

    private function extractSchema($schema): ?array
    {
        if ($schema === null) {
            return null;
        }

        // Handle both OpenAPI 3.0 and 3.1 type definitions
        $type = $schema->type ?? null;

        // In OpenAPI 3.1, type can be an array of types
        if (is_array($type) && !empty($type)) {
            // Use the first type as primary
            $type = $type[0];
        }

        $result = [
            'type' => $type,
        ];

        // Handle OpenAPI 3.1 oneOf, anyOf, allOf
        $this->extractCompositionSchema($result, $schema);

        if (isset($schema->properties)) {
            $result['properties'] = [];

            foreach ($schema->properties as $name => $property) {
                $propertyType = $property->type ?? null;

                // Handle array of types in OpenAPI 3.1
                if (is_array($propertyType) && !empty($propertyType)) {
                    $propertyType = $propertyType[0];
                }

                $result['properties'][$name] = [
                    'type'        => $propertyType,
                    'format'      => $property->format ?? null,
                    'description' => $property->description ?? null,
                ];

                // Handle OpenAPI 3.1 nullable property
                if (isset($property->nullable) && $property->nullable === true) {
                    $result['properties'][$name]['nullable'] = true;
                }

                if (isset($property->items)) {
                    $result['properties'][$name]['items'] = $this->extractSchema($property->items);
                }

                // Handle composition schemas for properties
                $this->extractCompositionSchema($result['properties'][$name], $property);
            }
        }

        if (isset($schema->items)) {
            $result['items'] = $this->extractSchema($schema->items);
        }

        if (isset($schema->required)) {
            $result['required'] = $schema->required;
        }

        if (isset($schema->example)) {
            $result['example'] = $schema->example;
        }

        return $result;
    }

    /**
     * Extract composition schemas (oneOf, anyOf, allOf) from OpenAPI 3.1
     */
    private function extractCompositionSchema(array &$target, $schema): void
    {
        foreach (['oneOf', 'anyOf', 'allOf'] as $composition) {
            if (isset($schema->$composition)) {
                $target[$composition] = [];
                foreach ($schema->$composition as $subSchema) {
                    $target[$composition][] = $this->extractSchema($subSchema);
                }
            }
        }
    }
}
