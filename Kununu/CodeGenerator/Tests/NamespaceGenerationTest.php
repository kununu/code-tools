<?php

declare(strict_types=1);

namespace Kununu\CodeGenerator\Tests;

use Kununu\CodeGenerator\Infrastructure\Generator\TwigTemplateGenerator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests for the namespace generation functionality
 */
class NamespaceGenerationTest extends TestCase
{
    private TwigTemplateGenerator $generator;

    protected function setUp(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $this->generator = new TwigTemplateGenerator($filesystem);
    }

    /**
     * @dataProvider namespaceTestCases
     */
    public function testNamespaceGeneration(string $outputPath, string $basePath, string $baseNamespace, string $expectedNamespace): void
    {
        $reflection = new ReflectionClass(TwigTemplateGenerator::class);
        $method = $reflection->getMethod('getDynamicNamespace');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, $outputPath, $basePath, $baseNamespace);

        $this->assertEquals($expectedNamespace, $result, "Failed asserting correct namespace for path: $outputPath");
    }

    /**
     * Provides test cases for namespace generation
     */
    public function namespaceTestCases(): array
    {
        return [
            // Standard cases
            'standard controller' => [
                'src/Controller/UserController.php',
                'src',
                'App',
                'App\\Controller',
            ],
            'nested controller' => [
                'src/Controller/Admin/UserController.php',
                'src',
                'App',
                'App\\Controller\\Admin',
            ],
            'query class' => [
                'src/UseCase/Query/GetUser/Query.php',
                'src',
                'App',
                'App\\UseCase\\Query\\GetUser',
            ],

            // Redundancy cases
            'redundant app in path' => [
                'src/App/Controller/UserController.php',
                'src',
                'App',
                'App\\Controller', // Should not have App\\App\\Controller
            ],
            'redundant namespace segment' => [
                'src/Domain/User/User/Entity.php',
                'src',
                'App',
                'App\\Domain\\User\\Entity', // Should deduplicate User\\User
            ],

            // Test file cases
            'test file' => [
                'tests/Unit/Controller/UserControllerTest.php',
                'src',
                'App',
                'App\\Tests\\Unit\\Controller',
            ],
            'test with redundancy' => [
                'tests/Tests/Unit/Controller/UserControllerTest.php',
                'src',
                'App',
                'App\\Tests\\Unit\\Controller', // Should not have Tests\\Tests
            ],

            // Other special cases
            'root level file' => [
                'src/RootFile.php',
                'src',
                'App',
                'App',
            ],
            'custom base path' => [
                'custom/path/to/file.php',
                'custom/path',
                'Custom\\Namespace',
                'Custom\\Namespace\\to',
            ],
            'empty base path' => [
                'Controller/UserController.php',
                '',
                'App',
                'App\\Controller',
            ],
        ];
    }

    /**
     * @dataProvider normalizeNamespaceTestCases
     */
    public function testNormalizeNamespace(string $baseNamespace, string $namespaceAddition, string $expectedNamespace): void
    {
        $reflection = new ReflectionClass(TwigTemplateGenerator::class);
        $method = $reflection->getMethod('normalizeNamespace');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, $baseNamespace, $namespaceAddition);

        $this->assertEquals($expectedNamespace, $result, "Failed normalizing namespace: $baseNamespace + $namespaceAddition");
    }

    /**
     * Provides test cases for namespace normalization
     */
    public function normalizeNamespaceTestCases(): array
    {
        return [
            'empty addition' => [
                'App',
                '',
                'App',
            ],
            'simple addition' => [
                'App',
                'Controller',
                'App\\Controller',
            ],
            'redundant beginning' => [
                'App',
                'App\\Controller',
                'App\\Controller', // Should remove the redundant App
            ],
            'case insensitive redundancy' => [
                'App',
                'app\\Controller',
                'App\\Controller', // Should handle case-insensitive matches
            ],
            'partial redundancy' => [
                'Vendor\\App',
                'App\\Controller',
                'Vendor\\App\\Controller', // App is redundant but part of Vendor\\App
            ],
            'multi-segment redundancy' => [
                'Core\\Web\\Api',
                'Api\\V1\\Controllers',
                'Core\\Web\\Api\\V1\\Controllers', // Should handle multi-segment matches
            ],
        ];
    }
}
