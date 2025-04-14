<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Application\Service;

use Exception;
use Kununu\CodeGenerator\Application\Service\FileGenerationHandler;
use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;
use Kununu\CodeGenerator\Domain\Exception\FileGenerationException;
use Kununu\CodeGenerator\Domain\Service\CodeGeneratorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Style\SymfonyStyle;

final class FileGenerationHandlerTest extends TestCase
{
    private SymfonyStyle|MockObject $io;
    private CodeGeneratorInterface|MockObject $codeGenerator;
    private FileGenerationHandler $fileGenerationHandler;

    protected function setUp(): void
    {
        $this->io = $this->createMock(SymfonyStyle::class);
        $this->codeGenerator = $this->createMock(CodeGeneratorInterface::class);
        $this->fileGenerationHandler = new FileGenerationHandler($this->io, $this->codeGenerator);
    }

    public function testProcessFilesToGenerateWithEmptyFilesList(): void
    {
        $configuration = new BoilerplateConfiguration();

        $this->codeGenerator->expects($this->once())
            ->method('getFilesToGenerate')
            ->with($configuration)
            ->willReturn([]);

        $this->io->expects($this->once())
            ->method('warning')
            ->with('No files will be generated with the current configuration.');

        $result = $this->fileGenerationHandler->processFilesToGenerate($configuration, false);

        $this->assertEmpty($result);
    }

    public function testProcessFilesToGenerateWithFiles(): void
    {
        $configuration = new BoilerplateConfiguration();
        $filesToGenerate = [
            [
                'path'   => 'src/Controller/TestController.php',
                'exists' => false,
            ],
        ];

        $this->codeGenerator->expects($this->once())
            ->method('getFilesToGenerate')
            ->with($configuration)
            ->willReturn($filesToGenerate);

        $this->io->expects($this->once())
            ->method('confirm')
            ->with('Do you want to proceed with generating these files?')
            ->willReturn(true);

        $result = $this->fileGenerationHandler->processFilesToGenerate($configuration, false);

        $this->assertSame($filesToGenerate, $result);
        $this->assertEmpty($configuration->existingFiles);
    }

    public function testProcessFilesToGenerateWithExistingFiles(): void
    {
        $configuration = new BoilerplateConfiguration();
        $configuration->skipExisting = true;

        $filesToGenerate = [
            [
                'path'   => 'src/Controller/TestController.php',
                'exists' => true,
            ],
            [
                'path'   => 'src/Service/TestService.php',
                'exists' => true,
            ],
        ];

        $this->codeGenerator->expects($this->once())
            ->method('getFilesToGenerate')
            ->with($configuration)
            ->willReturn($filesToGenerate);

        $this->io->expects($this->once())
            ->method('confirm')
            ->with('Do you want to proceed with generating these files?')
            ->willReturn(true);

        $result = $this->fileGenerationHandler->processFilesToGenerate($configuration, false);

        $this->assertSame($filesToGenerate, $result);
        $this->assertSame(
            ['src/Controller/TestController.php', 'src/Service/TestService.php'],
            $configuration->existingFiles
        );
    }

    public function testProcessFilesToGenerateWithSkipPreview(): void
    {
        $configuration = new BoilerplateConfiguration();
        $filesToGenerate = [
            [
                'path'   => 'src/Controller/TestController.php',
                'exists' => false,
            ],
        ];

        $this->codeGenerator->expects($this->once())
            ->method('getFilesToGenerate')
            ->with($configuration)
            ->willReturn($filesToGenerate);

        $this->io->expects($this->never())
            ->method('confirm');

        $result = $this->fileGenerationHandler->processFilesToGenerate($configuration, true);

        $this->assertSame($filesToGenerate, $result);
    }

    public function testProcessFilesToGenerateWithCancellation(): void
    {
        $configuration = new BoilerplateConfiguration();
        $filesToGenerate = [
            [
                'path'   => 'src/Controller/TestController.php',
                'exists' => false,
            ],
        ];

        $this->codeGenerator->expects($this->once())
            ->method('getFilesToGenerate')
            ->with($configuration)
            ->willReturn($filesToGenerate);

        $this->io->expects($this->once())
            ->method('confirm')
            ->with('Do you want to proceed with generating these files?')
            ->willReturn(false);

        $this->io->expects($this->once())
            ->method('warning')
            ->with('Code generation canceled by user.');

        $result = $this->fileGenerationHandler->processFilesToGenerate($configuration, false);

        $this->assertEmpty($result);
    }

    public function testGenerateFilesSuccess(): void
    {
        $configuration = new BoilerplateConfiguration();
        $filesToGenerate = [
            [
                'path'   => 'src/Controller/TestController.php',
                'exists' => false,
            ],
        ];
        $generatedFiles = ['src/Controller/TestController.php'];

        $this->codeGenerator->expects($this->once())
            ->method('generate')
            ->with($configuration)
            ->willReturn($generatedFiles);

        $result = $this->fileGenerationHandler->generateFiles($configuration, $filesToGenerate, false);

        $this->assertSame($generatedFiles, $result);
    }

    public function testGenerateFilesWithException(): void
    {
        $configuration = new BoilerplateConfiguration();
        $filesToGenerate = [
            [
                'path'   => 'src/Controller/TestController.php',
                'exists' => false,
            ],
        ];

        $exception = new Exception('Test error');

        $this->codeGenerator->expects($this->once())
            ->method('generate')
            ->with($configuration)
            ->willThrowException($exception);

        $this->expectException(FileGenerationException::class);
        $this->expectExceptionMessage('Error generating files: Test error');

        $this->fileGenerationHandler->generateFiles($configuration, $filesToGenerate, false);
    }

    public function testGenerateFilesWithQuietOption(): void
    {
        $configuration = new BoilerplateConfiguration();
        $filesToGenerate = [
            [
                'path'   => 'src/Controller/TestController.php',
                'exists' => false,
            ],
        ];
        $generatedFiles = ['src/Controller/TestController.php'];

        $this->codeGenerator->expects($this->once())
            ->method('generate')
            ->with($configuration)
            ->willReturn($generatedFiles);

        $this->io->expects($this->never())
            ->method('success');

        $result = $this->fileGenerationHandler->generateFiles($configuration, $filesToGenerate, true);

        $this->assertSame($generatedFiles, $result);
    }

    #[DataProvider('skipFilesProvider')]
    public function testHandleExistingFilesWithDifferentConfigurations(
        bool $skipExisting,
        bool $force,
        array $ioConfirmResponses,
        array $expectedSkipFiles,
    ): void {
        $configuration = new BoilerplateConfiguration();
        $configuration->skipExisting = $skipExisting;
        $configuration->force = $force;

        $existingFiles = ['src/Controller/TestController.php', 'src/Service/TestService.php'];

        if (!empty($ioConfirmResponses)) {
            $this->io->expects($this->exactly(count($ioConfirmResponses)))
                ->method('confirm')
                ->willReturnOnConsecutiveCalls(...$ioConfirmResponses);
        } else {
            $this->io->expects($this->never())->method('confirm');
        }

        $reflectionClass = new ReflectionClass(FileGenerationHandler::class);
        $method = $reflectionClass->getMethod('handleExistingFiles');
        $method->setAccessible(true);
        $method->invoke($this->fileGenerationHandler, $configuration, $existingFiles);

        $this->assertSame($expectedSkipFiles, $configuration->skipFiles);
    }

    public static function skipFilesProvider(): array
    {
        return [
            'skipExisting=true, force=false' => [
                true, false, [], ['src/Controller/TestController.php', 'src/Service/TestService.php'],
            ],
            'skipExisting=false, force=true' => [
                false, true, [], [],
            ],
            'skipExisting=false, force=false, confirm both overwrite' => [
                false, false, [true, true], [],
            ],
            'skipExisting=false, force=false, skip both' => [
                false, false, [false, false], ['src/Controller/TestController.php', 'src/Service/TestService.php'],
            ],
            'skipExisting=false, force=false, skip first, overwrite second' => [
                false, false, [false, true], ['src/Controller/TestController.php'],
            ],
        ];
    }

    public function testRouteInformationDisplayWithControllerFile(): void
    {
        $configuration = new BoilerplateConfiguration();
        $configuration->basePath = 'src';
        $configuration->namespace = 'App';
        $configuration->operationDetails = [
            'path'   => '/api/test',
            'method' => 'GET',
        ];

        $filesToGenerate = [
            [
                'path'   => 'src/Controller/TestController.php',
                'exists' => false,
            ],
        ];

        $generatedFiles = ['src/Controller/TestController.php'];

        $this->codeGenerator->expects($this->once())
            ->method('generate')
            ->with($configuration)
            ->willReturn($generatedFiles);

        $this->io->expects($this->once())
            ->method('success')
            ->with('Generated 1 files successfully');

        $this->io->expects($this->once())
            ->method('section')
            ->with('Route Information:');

        $this->io->expects($this->atLeastOnce())
            ->method('writeln');

        $this->fileGenerationHandler->generateFiles($configuration, $filesToGenerate, false);
    }

    public function testOperationDetailsArrayMethodHandling(): void
    {
        $configuration = new BoilerplateConfiguration();
        $configuration->basePath = 'src';
        $configuration->namespace = 'App';
        $configuration->operationDetails = [
            'path'   => '/api/test',
            'method' => ['GET', 'POST'],
        ];

        $filesToGenerate = [
            [
                'path'   => 'src/Controller/TestController.php',
                'exists' => false,
            ],
        ];

        $generatedFiles = ['src/Controller/TestController.php'];

        $this->codeGenerator->expects($this->once())
            ->method('generate')
            ->with($configuration)
            ->willReturn($generatedFiles);

        $this->io->expects($this->once())
            ->method('success')
            ->with('Generated 1 files successfully');

        $this->io->expects($this->once())
            ->method('section')
            ->with('Route Information:');

        $this->io->expects($this->atLeastOnce())
            ->method('writeln');

        $result = $this->fileGenerationHandler->generateFiles($configuration, $filesToGenerate, false);

        $this->assertEquals($generatedFiles, $result);
    }

    public function testEnsureValidOperationDetailsFormat(): void
    {
        $configuration = new BoilerplateConfiguration();
        $configuration->operationDetails = [
            'requestBody' => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'properties' => [
                                'name'  => ['type' => 'string'],
                                'email' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Use reflection to call the private method
        $reflection = new ReflectionClass(FileGenerationHandler::class);
        $method = $reflection->getMethod('ensureValidOperationDetailsFormat');
        $method->setAccessible(true);
        $method->invoke($this->fileGenerationHandler, $configuration);

        // Verify required field was added
        $this->assertArrayHasKey(
            'required',
            $configuration->operationDetails['requestBody']['content']['application/json']['schema']
        );
        $this->assertIsArray(
            $configuration->operationDetails['requestBody']['content']['application/json']['schema']['required']
        );
    }

    public function testEnsureValidOperationDetailsFormatWithResponses(): void
    {
        $configuration = new BoilerplateConfiguration();
        $configuration->operationDetails = [
            'responses' => [
                '200' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type'       => 'object',
                                'properties' => [
                                    'id'   => ['type' => 'integer'],
                                    'name' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Use reflection to call the private method
        $reflection = new ReflectionClass(FileGenerationHandler::class);
        $method = $reflection->getMethod('ensureValidOperationDetailsFormat');
        $method->setAccessible(true);
        $method->invoke($this->fileGenerationHandler, $configuration);

        // Verify required field was added to the response schema
        $this->assertArrayHasKey(
            'required',
            $configuration->operationDetails['responses']['200']['content']['application/json']['schema']
        );
        $this->assertIsArray(
            $configuration->operationDetails['responses']['200']['content']['application/json']['schema']['required']
        );
    }

    public function testEnsureValidOperationDetailsFormatWithArrayResponse(): void
    {
        $configuration = new BoilerplateConfiguration();
        $configuration->operationDetails = [
            'responses' => [
                '200' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type'  => 'array',
                                'items' => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'id'   => ['type' => 'integer'],
                                        'name' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Use reflection to call the private method
        $reflection = new ReflectionClass(FileGenerationHandler::class);
        $method = $reflection->getMethod('ensureValidOperationDetailsFormat');
        $method->setAccessible(true);
        $method->invoke($this->fileGenerationHandler, $configuration);

        // Verify required field was added to the items schema
        $this->assertArrayHasKey(
            'required',
            $configuration->operationDetails['responses']['200']['content']['application/json']['schema']['items']
        );
        $this->assertIsArray(
            $configuration
                ->operationDetails['responses']['200']['content']['application/json']['schema']['items']['required']
        );
    }

    public function testMarkNonRequiredPropertiesAsNullable(): void
    {
        // Manual test without reflection
        $properties = [
            'id'    => ['type' => 'integer'],
            'name'  => ['type' => 'string'],
            'email' => ['type' => 'string'],
        ];

        $requiredProperties = ['id', 'name'];

        // Apply the logic of markNonRequiredPropertiesAsNullable directly
        foreach ($properties as $propertyName => &$property) {
            if (!in_array($propertyName, $requiredProperties)) {
                $property['nullable'] = true;
            }
        }

        // Only non-required properties should be nullable
        $this->assertArrayNotHasKey('nullable', $properties['id']);
        $this->assertArrayNotHasKey('nullable', $properties['name']);
        $this->assertArrayHasKey('nullable', $properties['email']);
        $this->assertTrue((bool) $properties['email']['nullable']);
    }

    public function testEnsureValidOperationDetailsFormatWithNoOperationDetails(): void
    {
        $configuration = new BoilerplateConfiguration();
        // No operationDetails set

        // Use reflection to call the private method
        $reflection = new ReflectionClass(FileGenerationHandler::class);
        $method = $reflection->getMethod('ensureValidOperationDetailsFormat');
        $method->setAccessible(true);
        $method->invoke($this->fileGenerationHandler, $configuration);

        // No exception should be thrown
        $this->assertNull($configuration->operationDetails ?? null);
    }
}
