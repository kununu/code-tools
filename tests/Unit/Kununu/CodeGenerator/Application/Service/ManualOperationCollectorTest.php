<?php

declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Application\Service;

use Kununu\CodeGenerator\Application\Service\ManualOperationCollector;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;

#[Group('code-generator')]
final class ManualOperationCollectorTest extends TestCase
{
    private ManualOperationCollector $collector;
    private SymfonyStyle&MockObject $io;

    protected function setUp(): void
    {
        $this->io = $this->createMock(SymfonyStyle::class);
        $this->collector = new ManualOperationCollector($this->io);
    }

    public function testCollectOperationDetailsCollectsBasicInfoParameters(): void
    {
        // Setup basic operation info
        $this->io->expects($this->once())
            ->method('section')
            ->with('Manual Operation Details');

        // Use a callback to verify writeln calls
        $this->io->method('writeln')
            ->willReturnCallback(function($message) {
                static $callCount = 0;

                // Verify some of the expected messages
                if ($callCount === 0) {
                    $this->assertEquals('Please provide the following details for code generation:', $message);
                } elseif ($callCount === 1) {
                    $this->assertStringContainsString('Enter parameters', $message);
                }

                ++$callCount;

                return null;
            });

        // Basic info (operation ID, method, path)
        $this->io->method('ask')
            ->willReturnCallback(function($question, $default, $validator = null) {
                if (str_starts_with($question, 'Operation ID')) {
                    return 'getTestData';
                } elseif (str_starts_with($question, 'URL Path')) {
                    return '/test/{id}';
                } elseif (str_starts_with($question, 'Parameter name')) {
                    static $count = 0;
                    if ($count++ === 0) {
                        return 'id';
                    }

                    return ''; // Empty to end collection
                } elseif (str_starts_with($question, 'Status code')) {
                    return '200';
                }

                return null;
            });

        $this->io->method('choice')
            ->willReturnCallback(function($question, $choices, $default) {
                if (str_starts_with($question, 'HTTP Method')) {
                    return 'GET';
                } elseif (str_starts_with($question, 'Parameter location')) {
                    return 'path';
                } elseif (str_starts_with($question, 'Parameter type')) {
                    return 'string';
                } elseif (str_starts_with($question, 'Response schema type')) {
                    return 'object';
                }

                return $default;
            });

        // Parameters
        $this->io->method('confirm')
            ->willReturnCallback(function($question, $default) {
                if (str_starts_with($question, 'Does this operation have path or query parameters?')) {
                    return true;
                } elseif (str_starts_with($question, 'Is this parameter required?')) {
                    return true;
                } elseif (str_starts_with($question, 'Does this response have a body?')) {
                    return true;
                }

                return $default;
            });

        // Success message
        $this->io->expects($this->once())
            ->method('success')
            ->with('Operation details collected successfully');

        $result = $this->collector->collectOperationDetails();

        // Assertions
        $this->assertSame('getTestData', $result['id']);
        $this->assertSame('GET', $result['method']);
        $this->assertSame('/test/{id}', $result['path']);
        $this->assertArrayHasKey('parameters', $result);
        $this->assertCount(1, $result['parameters']);
        $this->assertSame('id', $result['parameters'][0]['name']);
        $this->assertSame('path', $result['parameters'][0]['in']);
        $this->assertTrue($result['parameters'][0]['required']);
        $this->assertArrayHasKey('responses', $result);
        $this->assertArrayHasKey('200', $result['responses']);
    }

    public function testCollectOperationDetailsWithPostMethodAndRequestBody(): void
    {
        // Setup basic operation info
        $this->io->expects($this->once())
            ->method('section')
            ->with('Manual Operation Details');

        $writelnCalls = [];
        $this->io->method('writeln')
            ->willReturnCallback(function($message) use (&$writelnCalls) {
                $writelnCalls[] = $message;

                return null;
            });

        // Mock ask method to return appropriate values based on question
        $this->io->method('ask')
            ->willReturnCallback(function($question, $default, $validator = null) {
                if (str_starts_with($question, 'Operation ID')) {
                    return 'createTestData';
                } elseif (str_starts_with($question, 'URL Path')) {
                    return '/test';
                } elseif (str_starts_with($question, 'Parameter name')) {
                    return ''; // No parameters
                } elseif (str_starts_with($question, 'Property name')) {
                    static $propCount = 0;

                    if ($propCount === 0) {
                        ++$propCount;

                        return 'name';
                    } elseif ($propCount === 1) {
                        ++$propCount;

                        return 'age';
                    } elseif ($propCount === 2) {
                        ++$propCount;

                        return 'address';
                    }

                    return ''; // No more properties
                } elseif (str_starts_with($question, 'Status code')) {
                    return '201';
                }

                return null;
            });

        // Mock choice method for HTTP method, param location, etc.
        $this->io->method('choice')
            ->willReturnCallback(function($question, $choices, $default) {
                if (str_starts_with($question, 'HTTP Method')) {
                    return 'POST';
                } elseif (str_starts_with($question, 'Property type')) {
                    static $propTypeCount = 0;

                    if ($propTypeCount === 0) {
                        ++$propTypeCount;

                        return 'string'; // name is string
                    } elseif ($propTypeCount === 1) {
                        ++$propTypeCount;

                        return 'integer'; // age is integer
                    } elseif ($propTypeCount === 2) {
                        ++$propTypeCount;

                        return 'object'; // address is object
                    }

                    return $default;
                } elseif (str_starts_with($question, 'Response schema type')) {
                    return 'object';
                }

                return $default;
            });

        // Mock confirm method for various questions
        $this->io->method('confirm')
            ->willReturnCallback(function($question, $default) {
                if (str_starts_with($question, 'Does this operation have path or query parameters?')) {
                    return false; // No parameters
                } elseif (str_starts_with($question, 'Does this operation have a request body?')) {
                    return true; // Has request body
                } elseif (str_starts_with($question, 'Is the request body required?')) {
                    return true; // Request body is required
                } elseif (str_starts_with($question, "Is 'name' required?")) {
                    return true; // name is required
                } elseif (str_starts_with($question, "Is 'age' required?")) {
                    return false; // age is not required
                } elseif (str_starts_with($question, "Is 'address' required?")) {
                    return false; // address is not required
                } elseif (str_starts_with($question, 'Does this response have a body?')) {
                    return true; // Response has body
                }

                return $default;
            });

        // Success message
        $this->io->expects($this->once())
            ->method('success')
            ->with('Operation details collected successfully');

        $result = $this->collector->collectOperationDetails();

        // Assertions
        $this->assertSame('createTestData', $result['id']);
        $this->assertSame('POST', $result['method']);
        $this->assertSame('/test', $result['path']);
        $this->assertEmpty($result['parameters']);
        $this->assertArrayHasKey('requestBody', $result);
        $this->assertTrue($result['requestBody']['required']);
        $this->assertArrayHasKey('application/json', $result['requestBody']['content']);
        $this->assertArrayHasKey('schema', $result['requestBody']['content']['application/json']);
        $this->assertArrayHasKey('properties', $result['requestBody']['content']['application/json']['schema']);
        $this->assertArrayHasKey('name', $result['requestBody']['content']['application/json']['schema']['properties']);
        $this->assertArrayHasKey('age', $result['requestBody']['content']['application/json']['schema']['properties']);
        $this->assertArrayHasKey('address', $result['requestBody']['content']['application/json']['schema']['properties']);
        $this->assertContains('name', $result['requestBody']['content']['application/json']['schema']['required']);
        $this->assertArrayHasKey('nullable', $result['requestBody']['content']['application/json']['schema']['properties']['age']);
        $this->assertArrayHasKey('201', $result['responses']);
    }

    public function testCollectOperationDetailsPutWithNoRequestBody(): void
    {
        // Setup basic operation info
        $this->io->expects($this->once())
            ->method('section')
            ->with('Manual Operation Details');

        $this->io->method('writeln')
            ->willReturnCallback(function($message) {
                return null;
            });

        // Mock ask method to return appropriate values based on question
        $this->io->method('ask')
            ->willReturnCallback(function($question, $default, $validator = null) {
                if (str_starts_with($question, 'Operation ID')) {
                    return 'updateTestData';
                } elseif (str_starts_with($question, 'URL Path')) {
                    return '/test/{id}';
                } elseif (str_starts_with($question, 'Parameter name')) {
                    static $paramCount = 0;

                    if ($paramCount === 0) {
                        ++$paramCount;

                        return 'id';
                    }

                    return ''; // No more parameters
                } elseif (str_starts_with($question, 'Status code')) {
                    return '204';
                }

                return null;
            });

        // Mock choice method
        $this->io->method('choice')
            ->willReturnCallback(function($question, $choices, $default) {
                if (str_starts_with($question, 'HTTP Method')) {
                    return 'PUT';
                } elseif (str_starts_with($question, 'Parameter location')) {
                    return 'path';
                } elseif (str_starts_with($question, 'Parameter type')) {
                    return 'string';
                }

                return $default;
            });

        // Mock confirm method
        $this->io->method('confirm')
            ->willReturnCallback(function($question, $default) {
                if (str_starts_with($question, 'Does this operation have path or query parameters?')) {
                    return true; // Has parameters
                } elseif (str_starts_with($question, 'Is this parameter required?')) {
                    return true; // Parameter is required
                } elseif (str_starts_with($question, 'Does this operation have a request body?')) {
                    return false; // No request body
                } elseif (str_starts_with($question, 'Does this response have a body?')) {
                    return false; // No response body
                }

                return $default;
            });

        // Success message
        $this->io->expects($this->once())
            ->method('success')
            ->with('Operation details collected successfully');

        $result = $this->collector->collectOperationDetails();

        // Assertions
        $this->assertSame('updateTestData', $result['id']);
        $this->assertSame('PUT', $result['method']);
        $this->assertSame('/test/{id}', $result['path']);
        $this->assertCount(1, $result['parameters']);
        $this->assertSame('id', $result['parameters'][0]['name']);
        $this->assertSame('path', $result['parameters'][0]['in']);
        $this->assertTrue($result['parameters'][0]['required']);
        $this->assertArrayNotHasKey('requestBody', $result);
        $this->assertArrayHasKey('204', $result['responses']);
        $this->assertArrayHasKey('content', $result['responses']['204']);
        $this->assertEmpty($result['responses']['204']['content']);
    }

    public function testCollectResponseWithArrayType(): void
    {
        // Setup basic operation info
        $this->io->expects($this->once())
            ->method('section')
            ->with('Manual Operation Details');

        $this->io->method('writeln')
            ->willReturnCallback(function($message) {
                return null;
            });

        // Mock ask method
        $this->io->method('ask')
            ->willReturnCallback(function($question, $default, $validator = null) {
                if (str_starts_with($question, 'Operation ID')) {
                    return 'listTestData';
                } elseif (str_starts_with($question, 'URL Path')) {
                    return '/tests';
                } elseif (str_starts_with($question, 'Parameter name')) {
                    return ''; // No parameters
                } elseif (str_starts_with($question, 'Status code')) {
                    return '200';
                } elseif (str_starts_with($question, 'Property name')) {
                    static $propCount = 0;

                    if ($propCount === 0) {
                        ++$propCount;

                        return 'id';
                    } elseif ($propCount === 1) {
                        ++$propCount;

                        return 'name';
                    } elseif ($propCount === 2) {
                        ++$propCount;

                        return 'active';
                    }

                    return ''; // No more properties
                }

                return null;
            });

        // Mock choice method
        $this->io->method('choice')
            ->willReturnCallback(function($question, $choices, $default) {
                if (str_starts_with($question, 'HTTP Method')) {
                    return 'GET';
                } elseif (str_starts_with($question, 'Response schema type')) {
                    return 'array';
                } elseif (str_starts_with($question, 'Array items type')) {
                    return 'object';
                } elseif (str_starts_with($question, 'Property type')) {
                    static $propTypeCount = 0;

                    if ($propTypeCount === 0) {
                        ++$propTypeCount;

                        return 'string'; // id is string
                    } elseif ($propTypeCount === 1) {
                        ++$propTypeCount;

                        return 'string'; // name is string
                    } elseif ($propTypeCount === 2) {
                        ++$propTypeCount;

                        return 'boolean'; // active is boolean
                    }

                    return $default;
                }

                return $default;
            });

        // Mock confirm method
        $this->io->method('confirm')
            ->willReturnCallback(function($question, $default) {
                if (str_starts_with($question, 'Does this operation have path or query parameters?')) {
                    return false; // No parameters
                } elseif (str_starts_with($question, 'Does this response have a body?')) {
                    return true; // Has response body
                } elseif (str_starts_with($question, "Is 'id' required?")) {
                    return true; // id is required
                } elseif (str_starts_with($question, "Is 'name' required?")) {
                    return true; // name is required
                } elseif (str_starts_with($question, "Is 'active' required?")) {
                    return false; // active is not required
                }

                return $default;
            });

        // Success message
        $this->io->expects($this->once())
            ->method('success')
            ->with('Operation details collected successfully');

        $result = $this->collector->collectOperationDetails();

        // Assertions
        $this->assertSame('listTestData', $result['id']);
        $this->assertSame('GET', $result['method']);
        $this->assertSame('/tests', $result['path']);
        $this->assertEmpty($result['parameters']);
        $this->assertArrayHasKey('200', $result['responses']);
        $this->assertArrayHasKey('content', $result['responses']['200']);
        $this->assertArrayHasKey('application/json', $result['responses']['200']['content']);
        $this->assertArrayHasKey('schema', $result['responses']['200']['content']['application/json']);
        $this->assertSame('array', $result['responses']['200']['content']['application/json']['schema']['type']);
        $this->assertArrayHasKey('items', $result['responses']['200']['content']['application/json']['schema']);
        $this->assertSame('object', $result['responses']['200']['content']['application/json']['schema']['items']['type']);
        $this->assertArrayHasKey('properties', $result['responses']['200']['content']['application/json']['schema']['items']);
        $this->assertArrayHasKey('id', $result['responses']['200']['content']['application/json']['schema']['items']['properties']);
        $this->assertArrayHasKey('name', $result['responses']['200']['content']['application/json']['schema']['items']['properties']);
        $this->assertArrayHasKey('active', $result['responses']['200']['content']['application/json']['schema']['items']['properties']);
        $this->assertArrayHasKey('required', $result['responses']['200']['content']['application/json']['schema']['items']);
        $this->assertContains('id', $result['responses']['200']['content']['application/json']['schema']['items']['required']);
        $this->assertContains('name', $result['responses']['200']['content']['application/json']['schema']['items']['required']);
        $this->assertArrayHasKey('nullable', $result['responses']['200']['content']['application/json']['schema']['items']['properties']['active']);
    }
}
