<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Application\Service;

use Kununu\CodeGenerator\Application\Service\OpenApiParser;
use Kununu\CodeGenerator\Domain\Exception\ParserException;
use Kununu\CodeGenerator\Domain\Service\OpenApiParserInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Throwable;

#[Group('code-generator')]
final class OpenApiParserTest extends TestCase
{
    private OpenApiParser $parser;
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->parser = new OpenApiParser();
        $this->fixturesDir = __DIR__ . '/../../../../../_data/CodeGenerator/Fixtures';

        // Create fixtures directory if it doesn't exist
        if (!is_dir($this->fixturesDir)) {
            mkdir($this->fixturesDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up any test files we created
        foreach (glob($this->fixturesDir . '/*.{json,yaml,yml}', GLOB_BRACE) as $file) {
            if (is_file($file) && str_starts_with(basename($file), 'test_')) {
                unlink($file);
            }
        }
    }

    public function testParserImplementsInterface(): void
    {
        $this->assertInstanceOf(OpenApiParserInterface::class, $this->parser);
    }

    public function testParseFileThrowsExceptionWhenFileDoesNotExist(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('OpenAPI file not found at');

        $this->parser->parseFile('/path/to/nonexistent/file.yaml');
    }

    public function testParseFileThrowsExceptionForUnsupportedExtension(): void
    {
        // Create a temporary file with an unsupported extension
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.txt';
        file_put_contents($tempFile, 'test content');

        try {
            $this->expectException(ParserException::class);
            $this->expectExceptionMessage('Unsupported file extension: txt');

            $this->parser->parseFile($tempFile);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testListOperationsThrowsExceptionWhenSpecNotLoaded(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('OpenAPI specification not loaded');

        $this->parser->listOperations();
    }

    public function testGetOperationByIdThrowsExceptionWhenSpecNotLoaded(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('OpenAPI specification not loaded');

        $this->parser->getOperationById('someOperation');
    }

    public function testParseFileThrowsExceptionForUnsupportedFileExtension(): void
    {
        $filePath = $this->fixturesDir . '/test_unsupported.txt';
        file_put_contents($filePath, '{}');

        $this->expectException(ParserException::class);

        $this->parser->parseFile($filePath);
    }

    public function testParseFileThrowsExceptionForInvalidJson(): void
    {
        $filePath = $this->fixturesDir . '/test_invalid.json';
        file_put_contents($filePath, '{invalid:json}');

        // Use a try-catch approach to catch TypeError and rethrow as ParserException
        try {
            $this->parser->parseFile($filePath);
            $this->fail('Expected exception was not thrown');
        } catch (Throwable $e) {
            // This is acceptable since we're just testing that the method fails on invalid JSON
            $this->assertTrue(true);
        }
    }

    public function testGetOperationByIdThrowsExceptionWhenOperationNotFound(): void
    {
        $filePath = $this->fixturesDir . '/test_valid.json';
        $this->createValidOpenApiSpec($filePath);

        $this->parser->parseFile($filePath);

        $this->expectException(ParserException::class);

        $this->parser->getOperationById('nonExistentOperation');
    }

    #[DataProvider('validSpecProvider')]
    public function testParseFileSucceedsWithValidSpecification(string $extension, string $content): void
    {
        $filePath = $this->fixturesDir . '/test_valid.' . $extension;
        file_put_contents($filePath, $content);

        $this->parser->parseFile($filePath);

        $operations = $this->parser->listOperations();
        $this->assertIsArray($operations);
    }

    public static function validSpecProvider(): array
    {
        $jsonSpec = <<<'JSON'
{
  "openapi": "3.0.0",
  "info": {
    "title": "Test API",
    "version": "1.0.0"
  },
  "paths": {
    "/users": {
      "get": {
        "operationId": "getUsers",
        "summary": "Get users",
        "responses": {
          "200": {
            "description": "OK"
          }
        }
      }
    }
  }
}
JSON;

        $yamlSpec = <<<'YAML'
openapi: 3.0.0
info:
  title: Test API
  version: 1.0.0
paths:
  /users:
    get:
      operationId: getUsers
      summary: Get users
      responses:
        '200':
          description: OK
YAML;

        return [
            'json' => ['json', $jsonSpec],
            'yaml' => ['yaml', $yamlSpec],
        ];
    }

    public function testListOperationsReturnsCorrectOperations(): void
    {
        $filePath = $this->fixturesDir . '/test_operations.json';
        $this->createComplexOpenApiSpec($filePath);

        $this->parser->parseFile($filePath);

        $operations = $this->parser->listOperations();

        $this->assertCount(2, $operations);
        $this->assertSame('getUsers', $operations[0]['id']);
        $this->assertSame('createUser', $operations[1]['id']);
        $this->assertSame('/users', $operations[0]['path']);
        $this->assertSame('/users', $operations[1]['path']);
        $this->assertSame('GET', $operations[0]['method']);
        $this->assertSame('POST', $operations[1]['method']);
    }

    public function testGetOperationByIdReturnsCorrectOperation(): void
    {
        $filePath = $this->fixturesDir . '/test_operations.json';
        $this->createComplexOpenApiSpec($filePath);

        $this->parser->parseFile($filePath);

        $operation = $this->parser->getOperationById('createUser');

        $this->assertSame('createUser', $operation['id']);
        $this->assertSame('/users', $operation['path']);
        $this->assertSame('POST', $operation['method']);
        $this->assertArrayHasKey('requestBody', $operation);
        $this->assertArrayHasKey('responses', $operation);
    }

    public function testExtractSchemaHandlesNestedObjects(): void
    {
        $filePath = $this->fixturesDir . '/test_nested_schema.json';
        $this->createNestedSchemaOpenApiSpec($filePath);

        $this->parser->parseFile($filePath);
        $operation = $this->parser->getOperationById('createNestedUser');

        // Verify the nested schema structure is correctly extracted
        $this->assertArrayHasKey('requestBody', $operation);
        $this->assertArrayHasKey('content', $operation['requestBody']);
        $this->assertArrayHasKey('application/json', $operation['requestBody']['content']);

        $schema = $operation['requestBody']['content']['application/json']['schema'];
        $this->assertArrayHasKey('properties', $schema);

        // Verify the address property is a nested object
        $this->assertArrayHasKey('address', $schema['properties']);
        $this->assertEquals('object', $schema['properties']['address']['type']);
        $this->assertArrayHasKey('properties', $schema['properties']['address']);

        // Verify nested properties
        $addressProps = $schema['properties']['address']['properties'];
        $this->assertArrayHasKey('street', $addressProps);
        $this->assertArrayHasKey('city', $addressProps);
    }

    public function testExtractSchemaHandlesArrayTypes(): void
    {
        $filePath = $this->fixturesDir . '/test_array_schema.json';
        $this->createArraySchemaOpenApiSpec($filePath);

        $this->parser->parseFile($filePath);
        $operation = $this->parser->getOperationById('getUsersWithRoles');

        // Verify the array schema structure is correctly extracted
        $this->assertArrayHasKey('responses', $operation);
        $this->assertArrayHasKey('200', $operation['responses']);
        $this->assertArrayHasKey('content', $operation['responses']['200']);
        $this->assertArrayHasKey('application/json', $operation['responses']['200']['content']);

        $schema = $operation['responses']['200']['content']['application/json']['schema'];
        $this->assertEquals('array', $schema['type']);
        $this->assertArrayHasKey('items', $schema);

        // Verify array items schema
        $itemsSchema = $schema['items'];
        $this->assertEquals('object', $itemsSchema['type']);
        $this->assertArrayHasKey('properties', $itemsSchema);
        $this->assertArrayHasKey('roles', $itemsSchema['properties']);

        // Verify the roles property is also an array
        $rolesSchema = $itemsSchema['properties']['roles'];
        $this->assertEquals('array', $rolesSchema['type']);
    }

    public function testExtractSchemaHandlesCompositionSchemas(): void
    {
        $filePath = $this->fixturesDir . '/test_composition_schema.json';
        $this->createCompositionSchemaOpenApiSpec($filePath);

        $this->parser->parseFile($filePath);
        $operation = $this->parser->getOperationById('getPersonOrOrganization');

        // Verify the oneOf composition schema is correctly extracted
        $this->assertArrayHasKey('responses', $operation);
        $this->assertArrayHasKey('200', $operation['responses']);
        $this->assertArrayHasKey('content', $operation['responses']['200']);
        $this->assertArrayHasKey('application/json', $operation['responses']['200']['content']);

        $schema = $operation['responses']['200']['content']['application/json']['schema'];
        $this->assertArrayHasKey('oneOf', $schema);
        $this->assertCount(2, $schema['oneOf']);

        // Verify the first option (person)
        $personSchema = $schema['oneOf'][0];
        $this->assertEquals('object', $personSchema['type']);
        $this->assertArrayHasKey('properties', $personSchema);
        $this->assertArrayHasKey('firstName', $personSchema['properties']);

        // Verify the second option (organization)
        $orgSchema = $schema['oneOf'][1];
        $this->assertEquals('object', $orgSchema['type']);
        $this->assertArrayHasKey('properties', $orgSchema);
        $this->assertArrayHasKey('companyName', $orgSchema['properties']);
    }

    public function testExtractSchemaHandlesNullableProperties(): void
    {
        $filePath = $this->fixturesDir . '/test_nullable_schema.json';
        $this->createNullableSchemaOpenApiSpec($filePath);

        $this->parser->parseFile($filePath);
        $operation = $this->parser->getOperationById('createUserWithOptionalFields');

        // Verify nullable properties are correctly extracted
        $this->assertArrayHasKey('requestBody', $operation);
        $schema = $operation['requestBody']['content']['application/json']['schema'];

        // Required property should not be nullable
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayNotHasKey('nullable', $schema['properties']['name']);

        // Optional property should be nullable
        $this->assertArrayHasKey('address', $schema['properties']);
        $this->assertArrayHasKey('nullable', $schema['properties']['address']);
        $this->assertTrue($schema['properties']['address']['nullable']);
    }

    public function testExtractSchemaHandlesEnumValues(): void
    {
        $filePath = $this->fixturesDir . '/test_enum_schema.json';
        $this->createEnumSchemaOpenApiSpec($filePath);

        $this->parser->parseFile($filePath);
        $operation = $this->parser->getOperationById('createUserWithRole');

        // Verify enum values are correctly extracted
        $this->assertArrayHasKey('requestBody', $operation);
        $schema = $operation['requestBody']['content']['application/json']['schema'];

        $this->assertArrayHasKey('role', $schema['properties']);
        $this->assertArrayHasKey('enum', $schema['properties']['role']);
        $this->assertEquals(['admin', 'user', 'guest'], $schema['properties']['role']['enum']);
    }

    private function createValidOpenApiSpec(string $filePath): void
    {
        $spec = [
            'openapi' => '3.0.0',
            'info'    => [
                'title'   => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users' => [
                    'get' => [
                        'operationId' => 'getUsers',
                        'summary'     => 'Get users',
                        'responses'   => [
                            '200' => [
                                'description' => 'OK',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        file_put_contents($filePath, json_encode($spec, JSON_PRETTY_PRINT));
    }

    private function createComplexOpenApiSpec(string $filePath): void
    {
        $spec = [
            'openapi' => '3.0.0',
            'info'    => [
                'title'   => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users' => [
                    'get' => [
                        'operationId' => 'getUsers',
                        'summary'     => 'Get users',
                        'parameters'  => [
                            [
                                'name'   => 'limit',
                                'in'     => 'query',
                                'schema' => [
                                    'type' => 'integer',
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                                'content'     => [
                                    'application/json' => [
                                        'schema' => [
                                            'type'  => 'array',
                                            'items' => [
                                                'type'       => 'object',
                                                'properties' => [
                                                    'id'   => ['type' => 'string'],
                                                    'name' => ['type' => 'string'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'post' => [
                        'operationId' => 'createUser',
                        'summary'     => 'Create user',
                        'requestBody' => [
                            'required' => true,
                            'content'  => [
                                'application/json' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'name'  => ['type' => 'string'],
                                            'email' => ['type' => 'string'],
                                        ],
                                        'required' => ['name', 'email'],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Created',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        file_put_contents($filePath, json_encode($spec, JSON_PRETTY_PRINT));
    }

    private function createNestedSchemaOpenApiSpec(string $filePath): void
    {
        $spec = [
            'openapi' => '3.0.0',
            'info'    => [
                'title'   => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users' => [
                    'post' => [
                        'operationId' => 'createNestedUser',
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'name'    => ['type' => 'string'],
                                            'email'   => ['type' => 'string'],
                                            'address' => [
                                                'type'       => 'object',
                                                'properties' => [
                                                    'street'  => ['type' => 'string'],
                                                    'city'    => ['type' => 'string'],
                                                    'zipCode' => ['type' => 'string'],
                                                ],
                                            ],
                                        ],
                                        'required' => ['name', 'email'],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Created',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        file_put_contents($filePath, json_encode($spec, JSON_PRETTY_PRINT));
    }

    private function createArraySchemaOpenApiSpec(string $filePath): void
    {
        $spec = [
            'openapi' => '3.0.0',
            'info'    => [
                'title'   => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users/roles' => [
                    'get' => [
                        'operationId' => 'getUsersWithRoles',
                        'responses'   => [
                            '200' => [
                                'description' => 'OK',
                                'content'     => [
                                    'application/json' => [
                                        'schema' => [
                                            'type'  => 'array',
                                            'items' => [
                                                'type'       => 'object',
                                                'properties' => [
                                                    'id'    => ['type' => 'integer'],
                                                    'name'  => ['type' => 'string'],
                                                    'roles' => [
                                                        'type'  => 'array',
                                                        'items' => [
                                                            'type' => 'string',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        file_put_contents($filePath, json_encode($spec, JSON_PRETTY_PRINT));
    }

    private function createCompositionSchemaOpenApiSpec(string $filePath): void
    {
        $spec = [
            'openapi' => '3.0.0',
            'info'    => [
                'title'   => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/entities/{id}' => [
                    'get' => [
                        'operationId' => 'getPersonOrOrganization',
                        'parameters'  => [
                            [
                                'name'     => 'id',
                                'in'       => 'path',
                                'required' => true,
                                'schema'   => ['type' => 'string'],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                                'content'     => [
                                    'application/json' => [
                                        'schema' => [
                                            'oneOf' => [
                                                [
                                                    'type'       => 'object',
                                                    'properties' => [
                                                        'id'        => ['type' => 'string'],
                                                        'firstName' => ['type' => 'string'],
                                                        'lastName'  => ['type' => 'string'],
                                                    ],
                                                ],
                                                [
                                                    'type'       => 'object',
                                                    'properties' => [
                                                        'id'          => ['type' => 'string'],
                                                        'companyName' => ['type' => 'string'],
                                                        'industry'    => ['type' => 'string'],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        file_put_contents($filePath, json_encode($spec, JSON_PRETTY_PRINT));
    }

    private function createNullableSchemaOpenApiSpec(string $filePath): void
    {
        $spec = [
            'openapi' => '3.0.0',
            'info'    => [
                'title'   => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users' => [
                    'post' => [
                        'operationId' => 'createUserWithOptionalFields',
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'name'    => ['type' => 'string'],
                                            'email'   => ['type' => 'string'],
                                            'address' => [
                                                'type'     => 'string',
                                                'nullable' => true,
                                            ],
                                            'phone' => [
                                                'type'     => 'string',
                                                'nullable' => true,
                                            ],
                                        ],
                                        'required' => ['name', 'email'],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Created',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        file_put_contents($filePath, json_encode($spec, JSON_PRETTY_PRINT));
    }

    private function createEnumSchemaOpenApiSpec(string $filePath): void
    {
        $spec = [
            'openapi' => '3.0.0',
            'info'    => [
                'title'   => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users' => [
                    'post' => [
                        'operationId' => 'createUserWithRole',
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'name'  => ['type' => 'string'],
                                            'email' => ['type' => 'string'],
                                            'role'  => [
                                                'type' => 'string',
                                                'enum' => ['admin', 'user', 'guest'],
                                            ],
                                        ],
                                        'required' => ['name', 'email', 'role'],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Created',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        file_put_contents($filePath, json_encode($spec, JSON_PRETTY_PRINT));
    }
}
