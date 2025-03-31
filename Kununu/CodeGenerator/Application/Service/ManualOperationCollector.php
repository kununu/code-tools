<?php

declare(strict_types=1);

namespace Kununu\CodeGenerator\Application\Service;

use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;

final readonly class ManualOperationCollector
{
    private SymfonyStyle $io;

    public function __construct(SymfonyStyle $io)
    {
        $this->io = $io;
    }

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
                throw new RuntimeException('Operation ID cannot be empty');
            }

            return $value;
        });

        $method = $this->io->choice('HTTP Method', ['GET', 'POST', 'PUT', 'DELETE'], 'GET');
        $path = $this->io->ask('URL Path (e.g., /users/{userId})', '/', function($value) {
            if (empty($value)) {
                throw new RuntimeException('URL Path cannot be empty');
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
            $paramName = $this->io->ask('Parameter name', null);
            if (empty($paramName)) {
                break;
            }

            $parameter = $this->collectParameterDetails($paramName);
            $parameters[] = $parameter;

            $this->io->writeln(sprintf('Added parameter: <info>%s</info> (%s)', $paramName, $parameter['schema']['type']));
        }

        return $parameters;
    }

    private function collectParameterDetails(string $paramName): array
    {
        $paramIn = $this->io->choice('Parameter location', ['path', 'query', 'header'], 'path');
        $paramRequired = $this->io->confirm('Is this parameter required?', true);
        $paramType = $this->io->choice('Parameter type', ['string', 'integer', 'number', 'boolean', 'array'], 'string');

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
        if (!$this->io->confirm('Does this operation have a request body?', true)) {
            return null;
        }

        $requestBody = [
            'required' => $this->io->confirm('Is the request body required?', true),
            'content'  => [],
        ];

        $contentType = $this->io->choice('Content type', ['application/json', 'application/xml', 'multipart/form-data'], 'application/json');

        $properties = $this->collectSchemaProperties('request body');
        $requestBody['content'][$contentType] = [
            'schema' => [
                'type'       => 'object',
                'properties' => $properties,
            ],
        ];

        return $requestBody;
    }

    private function collectSchemaProperties(string $context): array
    {
        $properties = [];
        $this->io->writeln(sprintf('Enter %s properties (leave name empty to finish):', $context));

        while (true) {
            $propName = $this->io->ask('Property name', null);
            if (empty($propName)) {
                break;
            }

            $property = $this->collectPropertyDetails($propName);
            $properties[$propName] = $property;

            $this->io->writeln(sprintf('Added property: <info>%s</info> (%s)', $propName, $property['type']));
        }

        return $properties;
    }

    private function collectPropertyDetails(string $propName): array
    {
        $propType = $this->io->choice('Property type', ['string', 'integer', 'number', 'boolean', 'array', 'object'], 'string');

        $property = [
            'type' => $propType,
        ];

        if ($propType === 'array') {
            $itemType = $this->io->choice('Array items type', ['string', 'integer', 'number', 'boolean', 'object'], 'string');
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

        do {
            $statusCode = $this->collectStatusCode();

            $response = [
                'content' => [],
            ];

            if ($this->io->confirm('Does this response have a body?', true)) {
                $response['content'] = $this->collectResponseContent();
            }

            $responses[$statusCode] = $response;
            $this->io->writeln(sprintf('Added response: <info>%s</info> - %s', $statusCode, $responseDesc));
        } while (empty($responses) || $this->io->confirm('Add another response?', false));

        return $responses;
    }

    private function collectStatusCode(): string
    {
        return $this->io->ask('Status code', '200', function($value) {
            if (!preg_match('/^[1-5][0-9][0-9]$/', $value)) {
                throw new RuntimeException('Invalid status code. Must be a 3-digit HTTP status code.');
            }

            return $value;
        });
    }

    private function collectResponseContent(): array
    {
        $content = [];
        $contentType = $this->io->choice('Content type', ['application/json', 'application/xml', 'text/plain'], 'application/json');

        if (in_array($contentType, ['application/json', 'application/xml'])) {
            $responseType = $this->io->choice('Response schema type', ['object', 'array', 'string', 'integer', 'number', 'boolean'], 'object');
            $schema = $this->buildResponseSchema($responseType);
            $content[$contentType] = [
                'schema' => $schema,
            ];
        } else {
            $content[$contentType] = [
                'schema' => [
                    'type' => 'string',
                ],
            ];
        }

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
        } elseif ($responseType === 'array') {
            $itemType = $this->io->choice('Array items type', ['string', 'integer', 'number', 'boolean', 'object'], 'object');
            $schema['items'] = [
                'type' => $itemType,
            ];

            if ($itemType === 'object') {
                $properties = $this->collectSchemaProperties('item');
                $schema['items']['properties'] = $properties;
            }
        }

        return $schema;
    }
}
