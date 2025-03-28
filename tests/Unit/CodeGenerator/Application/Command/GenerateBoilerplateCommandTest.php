<?php

declare(strict_types=1);

namespace Tests\Unit\CodeGenerator\Application\Command;

use Kununu\CodeGenerator\Application\Command\GenerateBoilerplateCommand;
use Kununu\CodeGenerator\Application\Service\ConfigurationLoader;
use Kununu\CodeGenerator\Application\Service\OpenApiParser;
use Kununu\CodeGenerator\Domain\Service\CodeGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateBoilerplateCommandTest extends TestCase
{
    private GenerateBoilerplateCommand $command;
    private CommandTester $commandTester;
    private MockObject $codeGenerator;
    private MockObject $openApiParser;
    private MockObject $configLoader;

    protected function setUp(): void
    {
        // Create mocks for dependencies
        $this->codeGenerator = $this->createMock(CodeGeneratorInterface::class);
        $this->openApiParser = $this->createMock(OpenApiParser::class);
        $this->configLoader = $this->createMock(ConfigurationLoader::class);

        // Create a reflection of the command to inject mocks
        $this->command = new class($this->codeGenerator, $this->openApiParser, $this->configLoader) extends GenerateBoilerplateCommand {
            private CodeGeneratorInterface $codeGeneratorMock;
            private OpenApiParser $openApiParserMock;
            private ConfigurationLoader $configLoaderMock;

            public function __construct(
                CodeGeneratorInterface $codeGenerator,
                OpenApiParser $openApiParser,
                ConfigurationLoader $configLoader,
            ) {
                parent::__construct();
                $this->codeGeneratorMock = $codeGenerator;
                $this->openApiParserMock = $openApiParser;
                $this->configLoaderMock = $configLoader;
            }

            protected function getConfigLoader(): ConfigurationLoader
            {
                return $this->configLoaderMock;
            }

            protected function getOpenApiParser(): OpenApiParser
            {
                return $this->openApiParserMock;
            }

            protected function getCodeGenerator(): CodeGeneratorInterface
            {
                return $this->codeGeneratorMock;
            }
        };

        // Create an application and add the command
        $application = new Application();
        $application->add($this->command);

        // Create a command tester
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteInNonInteractiveMode(): void
    {
        // Configure mocks
        $this->configLoader->expects($this->once())
            ->method('loadConfig')
            ->with('.code-generator-test')
            ->willReturn([
                'base_path' => 'src/test',
                'namespace' => 'Test',
            ]);

        $this->openApiParser->expects($this->once())
            ->method('parseFile')
            ->with('test.yaml');

        $this->openApiParser->expects($this->once())
            ->method('getOperationById')
            ->with('testOperation')
            ->willReturn([
                'id'     => 'testOperation',
                'path'   => '/test',
                'method' => 'POST',
            ]);

        $this->codeGenerator->expects($this->once())
            ->method('generate')
            ->willReturn([
                'src/test/Controller/TestOperationController.php',
                'src/test/DTO/Request/TestOperationRequest.php',
            ]);

        // Execute the command
        $this->commandTester->execute([
            'command'           => $this->command->getName(),
            '--openapi-file'    => 'test.yaml',
            '--operation-id'    => 'testOperation',
            '--config'          => '.code-generator-test',
            '--non-interactive' => true,
        ]);

        // Assert the command output
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Generated 2 files successfully', $output);
        $this->assertStringContainsString('src/test/Controller/TestOperationController.php', $output);
        $this->assertStringContainsString('src/test/DTO/Request/TestOperationRequest.php', $output);
    }

    public function testExecuteWithMissingOpenApiFile(): void
    {
        // Configure mocks
        $this->configLoader->expects($this->once())
            ->method('loadConfig')
            ->willReturn([]);

        $this->openApiParser->expects($this->never())
            ->method('parseFile');

        // Execute the command
        $this->commandTester->execute([
            'command'           => $this->command->getName(),
            '--non-interactive' => true,
        ]);

        // Assert the command output
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('OpenAPI specification not loaded', $output);
    }
}
