<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Application\Service;

use Kununu\CodeGenerator\Application\Service\ConfigurationBuilder;
use Kununu\CodeGenerator\Domain\Service\ConfigurationLoaderInterface;
use Kununu\CodeGenerator\Domain\Service\OpenApiParserInterface;
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
    private ConfigurationLoaderInterface&MockObject $configLoader;
    private OpenApiParserInterface&MockObject $openApiParser;
    private MockObject&InputInterface $input;
    private string $configPath;
    private array $defaultConfig;

    protected function setUp(): void
    {
        $this->io = $this->createMock(SymfonyStyle::class);
        $this->configLoader = $this->createMock(ConfigurationLoaderInterface::class);
        $this->openApiParser = $this->createMock(OpenApiParserInterface::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->configPath = 'code-generator.yaml';

        $this->defaultConfig = [
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

        $this->configBuilder = new ConfigurationBuilder(
            $this->io,
            $this->configLoader,
            $this->openApiParser
        );
    }

    public function testBuildConfigurationWithBasicSettings(): void
    {
        $this->input->method('getOption')
            ->willReturnMap([
                ['manual', true],
                ['force', false],
                ['skip-existing', false],
                ['template-dir', null],
            ]);

        $this->configLoader->method('loadConfig')->willReturn($this->defaultConfig);

        $config = $this->configBuilder->buildConfiguration($this->input, $this->configPath);

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
        $this->input->method('getOption')
            ->willReturnMap([
                ['manual', true],
                ['force', true],
                ['skip-existing', true],
                ['template-dir', 'custom/templates'],
            ]);

        $this->configLoader->method('loadConfig')->willReturn($this->defaultConfig);
        $config = $this->configBuilder->buildConfiguration($this->input, $this->configPath);

        $this->assertTrue($config->force);
        $this->assertTrue($config->skipExisting);
        $this->assertStringEndsWith('/custom/templates', $config->templateDir);
    }

    public function testBuildConfigurationWithTemplateDirFromConfig(): void
    {
        $configWithTemplateDir = [
            'base_path' => 'src',
            'namespace' => 'App',
            'templates' => [
                'path' => 'templates',
            ],
        ];

        $this->configLoader
            ->method('loadConfig')
            ->with($this->configPath)
            ->willReturn($configWithTemplateDir);

        $this->input->method('getOption')
            ->willReturnMap([
                ['manual', true],
                ['force', false],
                ['skip-existing', false],
                ['template-dir', null],
            ]);

        $config = $this->configBuilder->buildConfiguration($this->input, $this->configPath);

        $this->assertStringEndsWith('/templates', $config->templateDir);
    }

    public function testBuildConfigurationWithOpenApiSettings(): void
    {
        $openApiFilePath = 'api/openapi.yaml';
        $operationId = 'getUserById';

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

        $this->configLoader->method('loadConfig')->willReturn($this->defaultConfig);
        $config = $this->configBuilder->buildConfiguration($this->input, $this->configPath);

        $this->assertStringEndsWith($openApiFilePath, $config->openApiFilePath);
        $this->assertSame($operationId, $config->operationId);
    }

    public function testBuildConfigurationWithInteractiveOperationSelection(): void
    {
        $openApiFilePath = 'api/openapi.yaml';

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
        $this->io
            ->expects($this->once())
            ->method('ask')
            ->with('Select operation by number or provide operationId')
            ->willReturn('1');

        $this->openApiParser
            ->method('listOperations')
            ->willReturn(
                [
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
                ]
            );
        $config = $this->configBuilder->buildConfiguration($this->input, $this->configPath);

        $this->assertStringEndsWith($openApiFilePath, $config->openApiFilePath);
        $this->assertSame('getUserById', $config->operationId);
    }
}
