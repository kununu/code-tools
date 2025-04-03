<?php

declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Application\Service;

use Kununu\CodeGenerator\Application\Service\ConfigurationLoader;
use Kununu\CodeGenerator\Domain\Exception\ConfigurationException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

#[Group('code-generator')]
final class ConfigurationLoaderTest extends TestCase
{
    private ConfigurationLoader $configLoader;
    private MockObject&Filesystem $filesystem;
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->configLoader = new ConfigurationLoader($this->filesystem);
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

    public function testLoadsDefaultConfigWhenConfigFileDoesNotExist(): void
    {
        $nonExistentPath = '/path/to/nonexistent/config.yaml';

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($nonExistentPath)
            ->willReturn(false);

        $config = $this->configLoader->loadConfig($nonExistentPath);

        $this->assertArrayHasKey('base_path', $config);
        $this->assertArrayHasKey('namespace', $config);
        $this->assertArrayHasKey('path_patterns', $config);
        $this->assertArrayHasKey('generators', $config);
        $this->assertEquals('src', $config['base_path']);
        $this->assertEquals('App', $config['namespace']);
    }

    public function testUnsupportedConfigurationFormatThrowsException(): void
    {
        $filePath = $this->fixturesDir . '/test_config.txt';
        file_put_contents($filePath, 'invalid config');

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($filePath)
            ->willReturn(true);

        $this->expectException(ConfigurationException::class);

        $this->configLoader->loadConfig($filePath);
    }

    /**
     * @dataProvider invalidConfigProvider
     */
    public function testInvalidConfigurationThrowsException(string $extension, string $content): void
    {
        $filePath = $this->fixturesDir . '/test_invalid.' . $extension;
        file_put_contents($filePath, $content);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($filePath)
            ->willReturn(true);

        $this->expectException(ConfigurationException::class);

        $this->configLoader->loadConfig($filePath);
    }

    public static function invalidConfigProvider(): array
    {
        return [
            'invalid json' => ['json', '{ invalid: json }'],
            'invalid yaml' => ['yaml', "base_path: 'src'\n  invalid yaml indent"],
        ];
    }

    /**
     * @dataProvider validConfigProvider
     */
    public function testValidConfigurationLoads(string $extension, string $content, array $expectedConfig): void
    {
        $filePath = $this->fixturesDir . '/test_valid.' . $extension;
        file_put_contents($filePath, $content);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($filePath)
            ->willReturn(true);

        $config = $this->configLoader->loadConfig($filePath);

        foreach ($expectedConfig as $key => $value) {
            $this->assertArrayHasKey($key, $config);
            $this->assertEquals($value, $config[$key]);
        }
    }

    public static function validConfigProvider(): array
    {
        return [
            'json config' => [
                'json',
                '{"base_path": "custom/src", "namespace": "Custom\\\\Namespace"}',
                ['base_path' => 'custom/src', 'namespace' => 'Custom\\Namespace'],
            ],
            'yaml config' => [
                'yaml',
                "base_path: custom/src\nnamespace: Custom\\Namespace",
                ['base_path' => 'custom/src', 'namespace' => 'Custom\\Namespace'],
            ],
        ];
    }

    public function testConfigurationMergesWithDefaults(): void
    {
        $customConfig = [
            'base_path' => 'custom/src',
            'namespace' => 'Custom\\Namespace',
        ];

        $jsonConfig = json_encode($customConfig);
        $filePath = $this->fixturesDir . '/test_partial.json';
        file_put_contents($filePath, $jsonConfig);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($filePath)
            ->willReturn(true);

        $config = $this->configLoader->loadConfig($filePath);

        $this->assertEquals('custom/src', $config['base_path']);
        $this->assertEquals('Custom\\Namespace', $config['namespace']);

        // These should be from defaults
        $this->assertArrayHasKey('path_patterns', $config);
        $this->assertArrayHasKey('generators', $config);
        $this->assertArrayHasKey('controller', $config['path_patterns']);
    }

    public function testNoExtensionDetectsJsonFromContent(): void
    {
        $customConfig = [
            'base_path' => 'custom/src',
            'namespace' => 'Custom\\Namespace',
        ];

        $jsonConfig = json_encode($customConfig);
        $filePath = $this->fixturesDir . '/test_config_no_extension';
        file_put_contents($filePath, $jsonConfig);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($filePath)
            ->willReturn(true);

        $config = $this->configLoader->loadConfig($filePath);

        $this->assertEquals('custom/src', $config['base_path']);
        $this->assertEquals('Custom\\Namespace', $config['namespace']);
    }

    public function testNoExtensionDetectsYamlFromContent(): void
    {
        $yamlConfig = "base_path: custom/src\nnamespace: Custom\\Namespace";
        $filePath = $this->fixturesDir . '/test_config_no_extension';
        file_put_contents($filePath, $yamlConfig);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($filePath)
            ->willReturn(true);

        $config = $this->configLoader->loadConfig($filePath);

        $this->assertEquals('custom/src', $config['base_path']);
        $this->assertEquals('Custom\\Namespace', $config['namespace']);
    }
}
