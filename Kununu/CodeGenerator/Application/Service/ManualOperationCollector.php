<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Application\Service;

use Kununu\CodeGenerator\Domain\Exception\ConfigurationException;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Collects operation details manually from the user through console interaction
 *
 * This class is used when no OpenAPI specification is available or manual mode is selected.
 * It guides the user through a series of questions to gather all information needed for
 * code generation, including:
 * - Basic operation information (ID, method, path)
 * - Request parameters
 * - Request body schema
 * - Response schemas
 *
 * The gathered information mimics the structure obtained from parsing OpenAPI specifications.
 */
final readonly class ManualOperationCollector
{
    private SymfonyStyle $io;

    public function __construct(SymfonyStyle $io)
    {
        $this->io = $io;
    }

    /**
     * Main method to collect all operation details interactively
     *
     * @return array Operation details in a format compatible with OpenAPI parsed data
     */
    public function collectOperationDetails(): array
    {
        $this->io->section('Manual Operation Details');
        $this->io->writeln('Please provide the following details for code generation:');

        $operationDetails = $this->collectBasicOperationInfo();
        $parameters = $this->collectParameters();
        $operationDetails['parameters'] = $parameters;

        if (in_array($operationDetails['method'], ['POST', 'PUT'])) {
            $requestBody = $this->collectRequestBody();
            if ($requestBody !== null) {
                $operationDetails['requestBody'] = $requestBody;
            }
        }

        $responses = $this->collectResponses();
        $operationDetails['responses'] = $responses;

        $this->io->success('Operation details collected successfully');

        return $operationDetails;
    }

    private function collectBasicOperationInfo(): array
    {
        $operationId = $this->io->ask('Operation ID (e.g., getUserById)', null, function($value) {
            if (empty($value)) {
                throw new ConfigurationException('Operation ID cannot be empty');
            }

            return $value;
        });

        $method = $this->io->choice('HTTP Method', ['GET', 'POST', 'PUT', 'DELETE'], 'GET');
        $path = $this->io->ask('URL Path (e.g., /users/{userId})', '/', function($value) {
            if (empty($value)) {
                throw new ConfigurationException('URL Path cannot be empty');
            }

            return $value;
        });

        return [
            'id'     => $operationId,
            'path'   => $path,
            'method' => $method,
        ];
    }

    private function collectParameters(): array
    {
        $parameters = [];

        if (!$this->io->confirm('Does this operation have path or query parameters?', false)) {
            return $parameters;
        }

        $this->io->writeln('Enter parameters (leave name empty to finish):');

        while (true) {
            $paramName = $this->io->ask('Parameter name');
            if (empty($paramName)) {
                break;
            }

            $parameter = $this->collectParameterDetails($paramName);
            $parameters[] = $parameter;

            $this->io->writeln(
                sprintf('Added parameter: <info>%s</info> (%s)', $paramName, $parameter['schema']['type'])
            );
        }

        return $parameters;
    }

    private function collectParameterDetails(string $paramName): array
    {
        $paramIn = $this->io->choice('Parameter location', ['path', 'query', 'header'], 'path');
        $paramRequired = $this->io->confirm('Is this parameter required?');
        $paramType = $this->io->choice(
            'Parameter type',
            [
                'string',
                'integer',
                'number',
                'boolean',
                'array',
            ],
            'string'
        );

        return [
            'name'     => $paramName,
            'in'       => $paramIn,
            'required' => $paramRequired,
            'schema'   => [
                'type' => $paramType,
            ],
        ];
    }

    private function collectRequestBody(): ?array
    {
        if (!$this->io->confirm('Does this operation have a request body?')) {
            return null;
        }

        $requestBody = [
            'required' => $this->io->confirm('Is the request body required?'),
            'content'  => [],
        ];

        $contentType = 'application/json';
        // TODO: Allow user to select content type
        // $contentType = $this->io->choice('Content type', ['application/json', 'application/xml', 'multipart/form-data'], 'application/json');

        $properties = $this->collectSchemaProperties('request body');
        $requiredFields = $this->collectRequiredFields(array_keys($properties));

        // Mark non-required properties as nullable
        $this->markNonRequiredPropertiesAsNullable($properties, $requiredFields);

        $requestBody['content'][$contentType] = [
            'schema' => [
                'type'       => 'object',
                'properties' => $properties,
                'required'   => $requiredFields,
            ],
        ];

        return $requestBody;
    }

    private function collectRequiredFields(array $propertyNames): array
    {
        if (empty($propertyNames)) {
            return [];
        }

        $this->io->writeln('Select which fields are required (non-required fields will be nullable):');
        $requiredFields = [];

        foreach ($propertyNames as $propName) {
            if ($this->io->confirm(sprintf('Is \'%s\' required?', $propName), false)) {
                $requiredFields[] = $propName;
            } else {
                $this->io->writeln(" - <comment>$propName</comment> will be nullable");
            }
        }

        return $requiredFields;
    }

    private function collectSchemaProperties(string $context): array
    {
        $properties = [];
        $this->io->writeln(sprintf('Enter %s properties (leave name empty to finish):', $context));

        while (true) {
            $propName = $this->io->ask('Property name');
            if (empty($propName)) {
                break;
            }

            $property = $this->collectPropertyDetails();
            $properties[$propName] = $property;

            $this->io->writeln(sprintf('Added property: <info>%s</info> (%s)', $propName, $property['type']));
        }

        return $properties;
    }

    private function collectPropertyDetails(): array
    {
        $propType = $this->io->choice(
            'Property type',
            ['string', 'integer', 'number', 'boolean', 'array', 'object'],
            'string'
        );

        $property = [
            'type' => $propType,
        ];

        if ($propType === 'array') {
            $itemType = $this->io->choice(
                'Array items type',
                ['string', 'integer', 'number', 'boolean', 'object'],
                'string'
            );
            $property['items'] = [
                'type' => $itemType,
            ];

            if ($itemType === 'object') {
                $itemProperties = $this->collectSchemaProperties('array item');
                $property['items']['properties'] = $itemProperties;
            }
        }

        return $property;
    }

    private function collectResponses(): array
    {
        $responses = [];
        $this->io->writeln('Enter responses (at least one is required):');

        $statusCode = $this->collectStatusCode();

        $response = [
            'content' => [],
        ];

        if ($this->io->confirm('Does this response have a body?')) {
            $response['content'] = $this->collectResponseContent();
        }

        $responses[$statusCode] = $response;
        $this->io->writeln(sprintf('Added response: <info>%s</info>', $statusCode));

        return $responses;
    }

    private function collectStatusCode(): string
    {
        return $this->io->ask('Status code', '200', function($value) {
            if (!preg_match('/^[1-5][0-9][0-9]$/', $value)) {
                throw new ConfigurationException('Invalid status code. Must be a 3-digit HTTP status code.');
            }

            return $value;
        });
    }

    private function collectResponseContent(): array
    {
        $content = [];
        $contentType = 'application/json';

        $responseType = $this->io->choice(
            'Response schema type',
            ['object', 'array', 'string', 'integer', 'number', 'boolean'],
            'object'
        );
        $schema = $this->buildResponseSchema($responseType);
        $content[$contentType] = [
            'schema' => $schema,
        ];

        return $content;
    }

    private function buildResponseSchema(string $responseType): array
    {
        $schema = [
            'type' => $responseType,
        ];

        if ($responseType === 'object') {
            $properties = $this->collectSchemaProperties('response');
            $schema['properties'] = $properties;

            if (!empty($properties)) {
                $schema['required'] = $this->collectRequiredFields(array_keys($properties));

                // Mark non-required properties as nullable
                $this->markNonRequiredPropertiesAsNullable($schema['properties'], $schema['required']);
            }
        } elseif ($responseType === 'array') {
            $itemType = $this->io->choice(
                'Array items type',
                ['string', 'integer', 'number', 'boolean', 'object'],
                'object'
            );
            $schema['items'] = [
                'type' => $itemType,
            ];

            if ($itemType === 'object') {
                $properties = $this->collectSchemaProperties('item');
                $schema['items']['properties'] = $properties;

                if (!empty($properties)) {
                    $schema['items']['required'] = $this->collectRequiredFields(array_keys($properties));

                    // Mark non-required properties as nullable
                    $this->markNonRequiredPropertiesAsNullable(
                        $schema['items']['properties'], $schema['items']['required']
                    );
                }
            }
        }

        return $schema;
    }

    private function markNonRequiredPropertiesAsNullable(array &$properties, array $requiredProperties): void
    {
        foreach ($properties as $propertyName => &$property) {
            if (!in_array($propertyName, $requiredProperties)) {
                $property['nullable'] = true;
            }
        }
    }
}
