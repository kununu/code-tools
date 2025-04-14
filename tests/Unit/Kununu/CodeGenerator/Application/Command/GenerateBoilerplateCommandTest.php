<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Application\Command;

use Exception;
use Kununu\CodeGenerator\Application\Command\GenerateBoilerplateCommand;
use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;
use Kununu\CodeGenerator\Domain\Service\CodeGeneratorInterface;
use Kununu\CodeGenerator\Domain\Service\ConfigurationBuilderInterface;
use Kununu\CodeGenerator\Domain\Service\ConfigurationLoaderInterface;
use Kununu\CodeGenerator\Domain\Service\FileGenerationHandlerInterface;
use Kununu\CodeGenerator\Domain\Service\ManualOperationCollectorInterface;
use Kununu\CodeGenerator\Domain\Service\OpenApiParserInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class GenerateBoilerplateCommandTest extends TestCase
{
    private GenerateBoilerplateCommand $command;
    private OpenApiParserInterface|MockObject $openApiParser;
    private ConfigurationBuilderInterface|MockObject $configBuilder;
    private FileGenerationHandlerInterface|MockObject $fileGenerationHandler;
    private ManualOperationCollectorInterface|MockObject $manualOperationCollector;
    private InputInterface|MockObject $input;
    private OutputInterface|MockObject $output;
    private SymfonyStyle|MockObject $io;

    protected function setUp(): void
    {
        $configLoader = $this->createMock(ConfigurationLoaderInterface::class);
        $this->openApiParser = $this->createMock(OpenApiParserInterface::class);
        $codeGenerator = $this->createMock(CodeGeneratorInterface::class);
        $this->configBuilder = $this->createMock(ConfigurationBuilderInterface::class);
        $this->fileGenerationHandler = $this->createMock(FileGenerationHandlerInterface::class);
        $this->manualOperationCollector = $this->createMock(ManualOperationCollectorInterface::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
        $this->io = $this->createMock(SymfonyStyle::class);

        $this->command = new GenerateBoilerplateCommand(
            $configLoader,
            $this->openApiParser,
            $codeGenerator
        );

        // Inject mocks via reflection
        $reflection = new ReflectionClass($this->command);

        $ioProperty = $reflection->getProperty('io');
        $ioProperty->setAccessible(true);
        $ioProperty->setValue($this->command, $this->io);

        $configBuilderProperty = $reflection->getProperty('configBuilder');
        $configBuilderProperty->setAccessible(true);
        $configBuilderProperty->setValue($this->command, $this->configBuilder);

        $fileGenerationHandlerProperty = $reflection->getProperty('fileGenerationHandler');
        $fileGenerationHandlerProperty->setAccessible(true);
        $fileGenerationHandlerProperty->setValue($this->command, $this->fileGenerationHandler);

        $manualOperationCollectorProperty = $reflection->getProperty('manualOperationCollector');
        $manualOperationCollectorProperty->setAccessible(true);
        $manualOperationCollectorProperty->setValue($this->command, $this->manualOperationCollector);
    }

    public function testConfigureAddsExpectedOptions(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('openapi-file'));
        $this->assertTrue($definition->hasOption('operation-id'));
        $this->assertTrue($definition->hasOption('config'));
        $this->assertTrue($definition->hasOption('non-interactive'));
        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('quiet'));
        $this->assertTrue($definition->hasOption('no-color'));
        $this->assertTrue($definition->hasOption('skip-existing'));
        $this->assertTrue($definition->hasOption('manual'));
        $this->assertTrue($definition->hasOption('template-dir'));
    }

    public function testExecuteSuccess(): void
    {
        $configuration = new BoilerplateConfiguration();
        $configuration->templateDir = null;

        // Mock prepareConfiguration and other methods directly rather than using run
        $reflection = new ReflectionClass($this->command);
        $executeMethod = $reflection->getMethod('execute');
        $executeMethod->setAccessible(true);

        $prepareConfigMethod = $reflection->getMethod('prepareConfiguration');
        $prepareConfigMethod->setAccessible(true);

        // Setup input with expectations
        $this->input->expects($this->any())
            ->method('getOption')
            ->willReturnCallback(function($option) {
                return match ($option) {
                    'config'          => '.code-generator.yaml',
                    'manual'          => false,
                    'non-interactive' => false,
                    'quiet'           => false,
                    default           => null,
                };
            });

        // Mock the prepare configuration method to return our test config
        $this->configBuilder->expects($this->once())
            ->method('buildConfiguration')
            ->with($this->input, '.code-generator.yaml')
            ->willReturn($configuration);

        $this->fileGenerationHandler->expects($this->once())
            ->method('processFilesToGenerate')
            ->willReturn(['file1.php' => 'content']);

        $this->fileGenerationHandler->expects($this->once())
            ->method('generateFiles')
            ->with($configuration, ['file1.php' => 'content'], false)
            ->willReturn(['file1.php']);

        // Execute the command
        $result = $executeMethod->invoke($this->command, $this->input, $this->output);

        $this->assertEquals(0, $result);
    }

    public function testExecuteWithError(): void
    {
        // Setup input with expectations
        $this->input->expects($this->any())
            ->method('getOption')
            ->willReturnCallback(function($option) {
                return match ($option) {
                    'config' => '.code-generator.yaml',
                    default  => null,
                };
            });

        $exception = new Exception('Test error');

        // Mock prepareConfiguration to throw an exception
        $reflection = new ReflectionClass($this->command);
        $executeMethod = $reflection->getMethod('execute');
        $executeMethod->setAccessible(true);

        $this->configBuilder->expects($this->once())
            ->method('buildConfiguration')
            ->with($this->input, '.code-generator.yaml')
            ->willThrowException($exception);

        $this->io->expects($this->once())
            ->method('error')
            ->with('Test error');

        $result = $executeMethod->invoke($this->command, $this->input, $this->output);

        $this->assertEquals(1, $result);
    }

    public function testCollectOperationDetailsWithManualMode(): void
    {
        $configuration = new BoilerplateConfiguration();
        $operationDetails = [
            'path'   => '/test',
            'method' => 'GET',
        ];

        // Setup the input mock with all needed options
        $this->input->expects($this->any())
            ->method('getOption')
            ->willReturnCallback(function($option) {
                return match ($option) {
                    'manual'          => true,
                    'non-interactive' => false,
                    default           => null,
                };
            });

        $this->manualOperationCollector->expects($this->once())
            ->method('collectOperationDetails')
            ->willReturn($operationDetails);

        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('collectOperationDetails');
        $method->setAccessible(true);

        $method->invoke($this->command, $this->input, $configuration);

        $this->assertSame($operationDetails, $configuration->operationDetails);
    }

    public function testCollectOperationDetailsWithOpenApi(): void
    {
        $configuration = new BoilerplateConfiguration();
        $configuration->openApiFilePath = 'openapi.yaml';
        $configuration->operationId = 'testOperation';

        $operationDetails = [
            'path'   => '/api/test',
            'method' => 'POST',
        ];

        // Setup the input mock with all needed options
        $this->input->expects($this->any())
            ->method('getOption')
            ->willReturnCallback(function($option) {
                return match ($option) {
                    'manual'          => false,
                    'non-interactive' => false,
                    default           => null,
                };
            });

        $this->openApiParser->expects($this->once())
            ->method('parseFile')
            ->with('openapi.yaml');

        $this->openApiParser->expects($this->once())
            ->method('getOperationById')
            ->with('testOperation')
            ->willReturn($operationDetails);

        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('collectOperationDetails');
        $method->setAccessible(true);

        $method->invoke($this->command, $this->input, $configuration);

        $this->assertSame($operationDetails, $configuration->operationDetails);
    }

    public function testGenerateFilesWithEmptyFileList(): void
    {
        $configuration = new BoilerplateConfiguration();

        $this->input->expects($this->any())
            ->method('getOption')
            ->willReturnCallback(function($option) {
                return match ($option) {
                    'non-interactive' => false,
                    'quiet'           => false,
                    default           => null,
                };
            });

        $this->fileGenerationHandler->expects($this->once())
            ->method('processFilesToGenerate')
            ->willReturn([]);

        $this->fileGenerationHandler->expects($this->never())
            ->method('generateFiles');

        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('generateFiles');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $this->input, $configuration);

        $this->assertEquals(0, $result);
    }

    public function testDetermineManualModeWithOption(): void
    {
        $configuration = new BoilerplateConfiguration();

        $this->input->expects($this->any())
            ->method('getOption')
            ->willReturnCallback(function($option) {
                return match ($option) {
                    'manual'          => true,
                    'non-interactive' => false,
                    default           => null,
                };
            });

        $this->io->expects($this->never())
            ->method('confirm');

        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('determineManualMode');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $this->input, $configuration);

        $this->assertTrue($result);
    }

    public function testDetermineManualModeWithUserConfirmation(): void
    {
        $configuration = new BoilerplateConfiguration();

        $this->input->expects($this->any())
            ->method('getOption')
            ->willReturnCallback(function($option) {
                return match ($option) {
                    'manual'          => false,
                    'non-interactive' => false,
                    default           => null,
                };
            });

        $this->io->expects($this->once())
            ->method('confirm')
            ->with('Would you like to provide operation details manually instead of using OpenAPI?', false)
            ->willReturn(true);

        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('determineManualMode');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $this->input, $configuration);

        $this->assertTrue($result);
    }

    #[DataProvider('provideSkipPreviewOptions')]
    public function testShouldSkipPreview(bool $nonInteractive, bool $quiet, bool $expected): void
    {
        $this->input->expects($this->any())
            ->method('getOption')
            ->willReturnCallback(function($option) use ($nonInteractive, $quiet) {
                return match ($option) {
                    'non-interactive' => $nonInteractive,
                    'quiet'           => $quiet,
                    default           => null,
                };
            });

        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('shouldSkipPreview');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $this->input);

        $this->assertEquals($expected, $result);
    }

    public static function provideSkipPreviewOptions(): array
    {
        return [
            'both false'           => [false, false, false],
            'non-interactive true' => [true, false, true],
            'quiet true'           => [false, true, true],
            'both true'            => [true, true, true],
        ];
    }

    public function testInitializeWithNoColorOption(): void
    {
        // Set up the command definition
        $reflection = new ReflectionClass($this->command);
        $configureMethod = $reflection->getMethod('configure');
        $configureMethod->setAccessible(true);
        $configureMethod->invoke($this->command);

        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')
            ->willReturnCallback(function($option) {
                return match ($option) {
                    'no-color' => true,
                    'quiet'    => false,
                    default    => null,
                };
            });

        $this->output->expects($this->once())
            ->method('setDecorated')
            ->with(false);

        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('initialize');
        $method->setAccessible(true);

        $method->invoke($this->command, $input, $this->output);
    }

    public function testInitializeWithQuietOption(): void
    {
        // Set up the command definition
        $reflection = new ReflectionClass($this->command);
        $configureMethod = $reflection->getMethod('configure');
        $configureMethod->setAccessible(true);
        $configureMethod->invoke($this->command);

        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')
            ->willReturnCallback(function($option) {
                return match ($option) {
                    'no-color' => false,
                    'quiet'    => true,
                    default    => null,
                };
            });

        $this->output->expects($this->once())
            ->method('setVerbosity')
            ->with(OutputInterface::VERBOSITY_QUIET);

        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('initialize');
        $method->setAccessible(true);

        $method->invoke($this->command, $input, $this->output);
    }
}
