<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Application\Service;

use cebe\openapi\exceptions\IOException;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\json\InvalidJsonPointerSyntaxException;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Parameter;
use Kununu\CodeGenerator\Domain\Exception\ParserException;
use Kununu\CodeGenerator\Domain\Service\OpenApiParserInterface;

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
final class OpenApiParser implements OpenApiParserInterface
{
    private ?OpenApi $openApi = null;

    /**
     * @throws IOException|TypeErrorException|UnresolvableReferenceException|InvalidJsonPointerSyntaxException
     */
    public function parseFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new ParserException(sprintf('OpenAPI file not found at %s', $filePath));
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        $this->openApi = match (strtolower($extension)) {
            'json' => Reader::readFromJsonFile($filePath),
            'yaml', 'yml' => Reader::readFromYamlFile($filePath),
            default => throw new ParserException(sprintf('Unsupported file extension: %s', $extension)),
        };

        if (!$this->openApi->validate()) {
            $errors = $this->openApi->getErrors();
            throw new ParserException(sprintf(
                'Invalid OpenAPI specification: %s',
                implode(', ', $errors)
            ));
        }

        // Return some basic information about the parsed spec
        return [
            'title'       => $this->openApi->info->title ?? 'Unknown API',
            'version'     => $this->openApi->info->version ?? 'Unknown Version',
            'description' => $this->openApi->info->description ?? '',
        ];
    }

    public function listOperations(): array
    {
        if ($this->openApi === null) {
            throw new ParserException('OpenAPI specification not loaded. Call parseFile() first.');
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
            throw new ParserException('OpenAPI specification not loaded. Call parseFile() first.');
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
                        /** @var Parameter $parameter */
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

        throw new ParserException(sprintf('Operation with ID "%s" not found in OpenAPI specification', $operationId));
    }

    private function extractSchema(mixed $schema): ?array
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
                $result['properties'][$name] = $this->extractSchema($property);

                // Handle nullable properties in OpenAPI 3.0 and 3.1
                if (isset($property->nullable) && $property->nullable === true) {
                    $result['properties'][$name]['nullable'] = true;
                }
            }
        }

        if (isset($schema->items)) {
            $result['items'] = $this->extractSchema($schema->items);
        }

        if (isset($schema->required) && is_array($schema->required)) {
            $result['required'] = $schema->required;
        }

        if (isset($schema->enum)) {
            $result['enum'] = $schema->enum;
        }

        if (isset($schema->format)) {
            $result['format'] = $schema->format;
        }

        if (isset($schema->description)) {
            $result['description'] = $schema->description;
        }

        if (isset($schema->default)) {
            $result['default'] = $schema->default;
        }

        if (isset($schema->nullable) && $schema->nullable === true) {
            $result['nullable'] = true;
        }

        return $result;
    }

    private function extractCompositionSchema(array &$target, mixed $schema): void
    {
        // Handle oneOf
        if (isset($schema->oneOf) && is_array($schema->oneOf)) {
            $target['oneOf'] = [];
            foreach ($schema->oneOf as $item) {
                $target['oneOf'][] = $this->extractSchema($item);
            }
        }

        // Handle anyOf
        if (isset($schema->anyOf) && is_array($schema->anyOf)) {
            $target['anyOf'] = [];
            foreach ($schema->anyOf as $item) {
                $target['anyOf'][] = $this->extractSchema($item);
            }
        }

        // Handle allOf
        if (isset($schema->allOf) && is_array($schema->allOf)) {
            $target['allOf'] = [];
            foreach ($schema->allOf as $item) {
                $target['allOf'][] = $this->extractSchema($item);
            }
        }
    }
}
