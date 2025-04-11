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
    private TemplatePathResolverInterface|MockObject $templatePathResolver;
    private DefaultTemplateRegistry $registry;

    protected function setUp(): void
    {
        $this->templatePathResolver = $this->createMock(TemplatePathResolverInterface::class);
        $this->registry = new DefaultTemplateRegistry($this->templatePathResolver);
    }

    public function testRegisterTemplate(): void
    {
        $templateName = 'controller';
        $templatePath = 'controller.php.twig';
        $outputPattern = '{basePath}/Controller/{operationName}Controller.php';
        $resolvedPath = '@default/controller.php.twig';

        $this->templatePathResolver
            ->expects($this->once())
            ->method('resolveTemplatePath')
            ->with($templatePath)
            ->willReturn($resolvedPath);

        $this->registry->registerTemplate($templateName, $templatePath, $outputPattern);

        $templates = $this->registry->getAllTemplates();

        $this->assertArrayHasKey($templateName, $templates);
        $this->assertEquals($resolvedPath, $templates[$templateName]['path']);
        $this->assertEquals($templatePath, $templates[$templateName]['original_path']);
        $this->assertEquals($outputPattern, $templates[$templateName]['outputPattern']);
    }

    public function testGetAllTemplates(): void
    {
        $this->assertIsArray($this->registry->getAllTemplates());
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
            'GET method with controller template' => [
                'controller',
                [],
                ['method' => 'GET'],
                true,
            ],
            'POST method with controller template' => [
                'controller',
                [],
                ['method' => 'POST'],
                true,
            ],
            'GET method with query template' => [
                'query',
                [],
                ['method' => 'GET'],
                true,
            ],
            'POST method with query template' => [
                'query',
                [],
                ['method' => 'POST'],
                false,
            ],
            'GET method with command template' => [
                'command',
                [],
                ['method' => 'GET'],
                false,
            ],
            'POST method with command template' => [
                'command',
                [],
                ['method' => 'POST'],
                true,
            ],
            'GET method with XML serializer template' => [
                'query-serializer-xml',
                [],
                ['method' => 'GET'],
                true,
            ],
            'POST method with XML serializer template' => [
                'query-serializer-xml',
                [],
                ['method' => 'POST'],
                false,
            ],
            'Template disabled in configuration' => [
                'controller',
                ['generators' => ['controller' => false]],
                ['method'     => 'GET'],
                false,
            ],
            'Template enabled in configuration' => [
                'controller',
                ['generators' => ['controller' => true]],
                ['method'     => 'GET'],
                true,
            ],
            'Use case template with GET method' => [
                'use-case-query',
                [],
                ['method' => 'GET'],
                true,
            ],
            'Use case template with POST method' => [
                'use-case-command',
                [],
                ['method' => 'POST'],
                true,
            ],
        ];
    }

    public function testInitializeUseCaseTemplates(): void
    {
        $this->templatePathResolver
            ->expects($this->once())
            ->method('resolveTemplatePath')
            ->with('use-case-query.php.twig')
            ->willReturn('@default/use-case-query.php.twig');

        $this->registry->registerTemplate(
            'use-case-query',
            'use-case-query.php.twig',
            '{basePath}/UseCase/Query/{operationName}.php'
        );

        $templates = $this->registry->getAllTemplates();

        $this->assertNotEmpty($templates);
        $this->assertArrayHasKey('use-case-query', $templates);
    }
}
