<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Infrastructure\Template;

use Kununu\CodeGenerator\Domain\Service\Template\TemplatePathResolverInterface;
use Kununu\CodeGenerator\Infrastructure\Template\DefaultTemplateRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DefaultTemplateRegistryTest extends TestCase
{
    private DefaultTemplateRegistry $registry;
    private TemplatePathResolverInterface&MockObject $templatePathResolver;

    protected function setUp(): void
    {
        $this->templatePathResolver = $this->createMock(TemplatePathResolverInterface::class);
        $this->registry = new DefaultTemplateRegistry($this->templatePathResolver);
    }

    public function testRegisterTemplate(): void
    {
        $this->templatePathResolver
            ->expects($this->once())
            ->method('resolveTemplatePath')
            ->with('controller.php.twig')
            ->willReturn('@default/controller.php.twig');

        $this->registry->registerTemplate(
            'controller',
            'controller.php.twig',
            '{basePath}/Controller/{operationName}Controller.php'
        );

        $templates = $this->registry->getAllTemplates();

        $this->assertNotEmpty($templates);
        $this->assertArrayHasKey('controller', $templates);
        $this->assertEquals('@default/controller.php.twig', $templates['controller']['path']);
        $this->assertEquals('controller.php.twig', $templates['controller']['original_path']);
        $this->assertEquals(
            '{basePath}/Controller/{operationName}Controller.php',
            $templates['controller']['outputPattern']
        );
    }

    public function testGetAllTemplatesInitialState(): void
    {
        $templates = $this->registry->getAllTemplates();
        $this->assertIsArray($templates);
    }

    public function testGetAllTemplatesAfterRegistering(): void
    {
        $initialCount = count($this->registry->getAllTemplates());

        $this->templatePathResolver
            ->method('resolveTemplatePath')
            ->willReturnCallback(function($path) {
                return '@default/' . $path;
            });

        $this->registry->registerTemplate('template1', 'file1.php.twig', 'output1');
        $this->registry->registerTemplate('template2', 'file2.php.twig', 'output2');

        $templates = $this->registry->getAllTemplates();
        $expectedCount = $initialCount + 2;

        $this->assertCount($expectedCount, $templates);
        $this->assertArrayHasKey('template1', $templates);
        $this->assertArrayHasKey('template2', $templates);
    }

    #[DataProvider('shouldGenerateTemplateDataProvider')]
    public function testShouldGenerateTemplate(
        string $templateName,
        array $configuration,
        array $variables,
        bool $expected,
    ): void {
        $result = $this->registry->shouldGenerateTemplate($templateName, $configuration, $variables);
        $this->assertEquals($expected, $result);
    }

    public static function shouldGenerateTemplateDataProvider(): array
    {
        return [
            'Template disabled in configuration' => [
                'controller',
                ['generators' => ['controller' => false]],
                ['method'     => 'GET'],
                false,
            ],
            'Template enabled in configuration' => [
                'controller',
                ['generators' => ['other-feature' => false]],
                ['method'     => 'GET'],
                true,
            ],
            'Use case template with GET method' => [
                'query',
                [],
                ['method' => 'GET'],
                true,
            ],
            'Use case template with POST method' => [
                'command',
                [],
                ['method' => 'POST'],
                true,
            ],
            'Query template with POST method' => [
                'query',
                [],
                ['method' => 'POST'],
                false,
            ],
            'Command template with GET method' => [
                'command',
                [],
                ['method' => 'GET'],
                false,
            ],
            'Criteria template with no parameters' => [
                'criteria',
                [],
                ['method' => 'GET'],
                false,
            ],
            'Criteria template with only path parameters' => [
                'criteria',
                [],
                ['method' => 'GET', 'parameters' => [['in' => 'path']]],
                false,
            ],
            'Criteria template with query parameters' => [
                'criteria',
                [],
                ['method' => 'GET', 'parameters' => [['in' => 'query']]],
                true,
            ],
            'Request-data template with request-mapper disabled' => [
                'request-data',
                ['generators' => ['request-mapper' => false]],
                ['method'     => 'POST'],
                false,
            ],
            'Command template with cqrs-command-query disabled' => [
                'command',
                ['generators' => ['cqrs-command-query' => false]],
                ['method'     => 'POST'],
                false,
            ],
            'Command template with command disabled' => [
                'command',
                ['generators' => ['command' => false]],
                ['method'     => 'POST'],
                false,
            ],
            'Repository template with repository disabled' => [
                'query-repository',
                ['generators' => ['repository' => false]],
                ['method'     => 'GET'],
                false,
            ],
            'Read-model template with read-model disabled' => [
                'read-model',
                ['generators' => ['read-model' => false]],
                ['method'     => 'GET'],
                false,
            ],
            'JMS serializer config with xml-serializer disabled' => [
                'jms-serializer-config',
                ['generators' => ['xml-serializer' => false]],
                ['method'     => 'GET'],
                false,
            ],
            'Test template with tests disabled' => [
                'query-unit-test',
                ['generators' => ['tests' => false]],
                ['method'     => 'GET'],
                false,
            ],
            'Use-case template with use-case disabled' => [
                'query',
                ['generators' => ['use-case' => false]],
                ['method'     => 'GET'],
                false,
            ],
        ];
    }

    public function testShouldSkipCriteriaTemplateWithIndirectAccess(): void
    {
        $result = $this->registry->shouldGenerateTemplate('criteria', [], ['method' => 'GET']);
        $this->assertFalse($result);

        $result = $this->registry->shouldGenerateTemplate('criteria', [], ['method' => 'GET', 'parameters' => []]);
        $this->assertFalse($result);

        $result = $this->registry->shouldGenerateTemplate(
            'criteria',
            [],
            ['method' => 'GET', 'parameters' => [['in' => 'path']]]
        );
        $this->assertFalse($result);

        $result = $this->registry->shouldGenerateTemplate(
            'criteria',
            [],
            ['method' => 'GET', 'parameters' => [['in' => 'query']]]
        );
        $this->assertTrue($result);
    }

    public function testTemplateValidForMethodWithIndirectAccess(): void
    {
        $result = $this->registry->shouldGenerateTemplate('query', [], ['method' => 'GET']);
        $this->assertTrue($result);

        $result = $this->registry->shouldGenerateTemplate('command', [], ['method' => 'POST']);
        $this->assertTrue($result);

        $result = $this->registry->shouldGenerateTemplate('query', [], ['method' => 'POST']);
        $this->assertFalse($result);

        $result = $this->registry->shouldGenerateTemplate('command', [], ['method' => 'GET']);
        $this->assertFalse($result);

        $result = $this->registry->shouldGenerateTemplate('controller', [], ['method' => 'GET']);
        $this->assertTrue($result);

        $result = $this->registry->shouldGenerateTemplate('controller', [], ['method' => 'POST']);
        $this->assertTrue($result);
    }

    public function testIsTemplateEnabledInConfigurationWithIndirectAccess(): void
    {
        $result = $this->registry->shouldGenerateTemplate(
            'query',
            ['generators' => ['use-case' => false]],
            ['method'     => 'GET']
        );
        $this->assertFalse($result);

        $result = $this->registry->shouldGenerateTemplate(
            'controller',
            ['generators' => ['controller' => false]],
            ['method'     => 'GET']
        );
        $this->assertFalse($result);

        $result = $this->registry->shouldGenerateTemplate(
            'request-data',
            ['generators' => ['request-mapper' => false]],
            ['method'     => 'POST']
        );
        $this->assertFalse($result);

        $result = $this->registry->shouldGenerateTemplate(
            'controller',
            ['generators' => ['other-feature' => false]],
            ['method'     => 'GET']
        );
        $this->assertTrue($result);
    }

    public function testIsUseCaseTemplateWithIndirectAccess(): void
    {
        $result = $this->registry->shouldGenerateTemplate('query', [], ['method' => 'GET']);
        $this->assertTrue($result);

        $result = $this->registry->shouldGenerateTemplate('controller', [], ['method' => 'GET']);
        $this->assertTrue($result);

        $result = $this->registry->shouldGenerateTemplate('query', [], ['method' => 'POST']);
        $this->assertFalse($result);
    }

    public function testInitializeUseCaseTemplatesIndirectly(): void
    {
        $this->templatePathResolver
            ->method('resolveTemplatePath')
            ->willReturnCallback(function($path) {
                return '@default/' . $path;
            });

        $this->registry->registerTemplate('query', 'query.php.twig', 'output');

        $templates = $this->registry->getAllTemplates();
        $this->assertArrayHasKey('query', $templates);
    }

    public function testMultipleTemplateRegistrations(): void
    {
        $this->templatePathResolver
            ->method('resolveTemplatePath')
            ->willReturnCallback(function($path) {
                return '@default/' . $path;
            });

        $this->registry->registerTemplate('template1', 'file1.php.twig', 'output1');
        $this->registry->registerTemplate('template1', 'file2.php.twig', 'output2');

        $templates = $this->registry->getAllTemplates();

        $this->assertArrayHasKey('template1', $templates);
        $this->assertEquals('file2.php.twig', $templates['template1']['original_path']);
        $this->assertEquals('output2', $templates['template1']['outputPattern']);
    }

    public function testShouldGenerateTemplateWithEmptyVariables(): void
    {
        $result = $this->registry->shouldGenerateTemplate('controller', [], []);
        $this->assertIsBool($result);
    }

    public function testShouldGenerateTemplateWithUnknownTemplate(): void
    {
        $result = $this->registry->shouldGenerateTemplate('non-existent-template', [], ['method' => 'GET']);
        $this->assertTrue($result);
    }
}
