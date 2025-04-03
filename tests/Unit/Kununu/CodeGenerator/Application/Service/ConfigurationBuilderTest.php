<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Application\Service;

use Kununu\CodeGenerator\Application\Service\ConfigurationBuilder;
use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[Group('code-generator')]
final class ConfigurationBuilderTest extends TestCase
{
    private ConfigurationBuilder $configBuilder;
    private MockObject&SymfonyStyle $io;
    private TestConfigurationLoader $configLoader;
    private TestOpenApiParser $openApiParser;
    private MockObject&InputInterface $input;
    private string $configPath;

    protected function setUp(): void
    {
        $this->io = $this->createMock(SymfonyStyle::class);
        $this->configLoader = new TestConfigurationLoader();
        $this->openApiParser = new TestOpenApiParser();
        $this->input = $this->createMock(InputInterface::class);
        $this->configPath = 'code-generator.yaml';

        // Set up basic configuration
        $defaultConfig = [
            'base_path'     => 'src',
            'namespace'     => 'App',
            'path_patterns' => [
                'controller' => '{basePath}/Controller/{operationName}Controller.php',
            ],
            'generators' => [
                'controller' => true,
                'repository' => true,
            ],
            'force'         => false,
            'skip_existing' => false,
        ];

        $this->configLoader->setDefaultConfig($defaultConfig);

        // Set up test operations for the OpenAPI parser
        $operations = [
            [
                'id'      => 'getUserById',
                'summary' => 'Get user by ID',
                'path'    => '/users/{id}',
                'method'  => 'GET',
            ],
            [
                'id'      => 'createUser',
                'summary' => 'Create a new user',
                'path'    => '/users',
                'method'  => 'POST',
            ],
        ];

        $this->openApiParser->setOperations($operations);
        $this->openApiParser->setParseFileResult([
            'title'   => 'Test API',
            'version' => '1.0.0',
        ]);

        // Create the ConfigurationBuilder with our test doubles
        $this->configBuilder = new ConfigurationBuilder(
            $this->io,
            $this->configLoader,
            $this->openApiParser
        );
    }

    public function testBuildConfigurationWithBasicSettings(): void
    {
        // Set manual option to skip OpenAPI configuration
        $this->input->method('getOption')
            ->willReturnMap([
                ['manual', true],
                ['force', false],
                ['skip-existing', false],
                ['template-dir', null],
            ]);

        $config = $this->configBuilder->buildConfiguration($this->input, $this->configPath);

        $this->assertInstanceOf(BoilerplateConfiguration::class, $config);
        $this->assertSame('src', $config->basePath);
        $this->assertSame('App', $config->namespace);
        $this->assertSame(
            ['controller' => '{basePath}/Controller/{operationName}Controller.php'],
            $config->pathPatterns
        );
        $this->assertSame(['controller' => true, 'repository' => true], $config->generators);
        $this->assertFalse($config->force);
        $this->assertFalse($config->skipExisting);
    }

    public function testBuildConfigurationWithCommandLineOverrides(): void
    {
        // Set manual option to skip OpenAPI configuration and override force/skip-existing
        $this->input->method('getOption')
            ->willReturnMap([
                ['manual', true],
                ['force', true],
                ['skip-existing', true],
                ['template-dir', 'custom/templates'],
            ]);

        $config = $this->configBuilder->buildConfiguration($this->input, $this->configPath);

        $this->assertInstanceOf(BoilerplateConfiguration::class, $config);
        $this->assertTrue($config->force);
        $this->assertTrue($config->skipExisting);
        $this->assertStringEndsWith('/custom/templates', $config->templateDir);
    }

    public function testBuildConfigurationWithTemplateDirFromConfig(): void
    {
        // Set a config with template directory
        $configWithTemplateDir = [
            'base_path' => 'src',
            'namespace' => 'App',
            'templates' => [
                'path' => 'templates',
            ],
        ];

        $this->configLoader->setConfig($this->configPath, $configWithTemplateDir);

        // Set manual option to skip OpenAPI configuration
        $this->input->method('getOption')
            ->willReturnMap([
                ['manual', true],
                ['force', false],
                ['skip-existing', false],
                ['template-dir', null],
            ]);

        $config = $this->configBuilder->buildConfiguration($this->input, $this->configPath);

        $this->assertInstanceOf(BoilerplateConfiguration::class, $config);
        $this->assertStringEndsWith('/templates', $config->templateDir);
    }

    public function testBuildConfigurationWithOpenApiSettings(): void
    {
        $openApiFilePath = 'api/openapi.yaml';
        $operationId = 'getUserById';

        // Set up fileExists for the test parser
        $this->openApiParser->setParseFileCalled();
        $this->openApiParser->setSkipFileExistsCheck();

        // Set up input options
        $this->input->method('getOption')
            ->willReturnMap([
                ['manual', false],
                ['non-interactive', true],
                ['openapi-file', $openApiFilePath],
                ['operation-id', $operationId],
                ['force', false],
                ['skip-existing', false],
                ['template-dir', null],
            ]);

        $config = $this->configBuilder->buildConfiguration($this->input, $this->configPath);

        $this->assertInstanceOf(BoilerplateConfiguration::class, $config);
        $this->assertStringEndsWith($openApiFilePath, $config->openApiFilePath);
        $this->assertSame($operationId, $config->operationId);
    }

    public function testBuildConfigurationWithInteractiveOperationSelection(): void
    {
        $openApiFilePath = 'api/openapi.yaml';

        // Set up fileExists for the test parser
        $this->openApiParser->setParseFileCalled();
        $this->openApiParser->setSkipFileExistsCheck();

        // Set up input options
        $this->input->method('getOption')
            ->willReturnMap([
                ['manual', false],
                ['non-interactive', false],
                ['openapi-file', $openApiFilePath],
                ['operation-id', null],
                ['force', false],
                ['skip-existing', false],
                ['template-dir', null],
            ]);

        // Mock interactive selection
        $this->io->expects($this->once())
            ->method('ask')
            ->with('Select operation by number or provide operationId')
            ->willReturn('1');

        $config = $this->configBuilder->buildConfiguration($this->input, $this->configPath);

        $this->assertInstanceOf(BoilerplateConfiguration::class, $config);
        $this->assertStringEndsWith($openApiFilePath, $config->openApiFilePath);
        $this->assertSame('getUserById', $config->operationId);
    }
}
