<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Infrastructure\Generator;

use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;
use Kununu\CodeGenerator\Domain\Service\CodeGeneratorInterface;
use Kununu\CodeGenerator\Domain\Service\FileSystem\FileSystemHandlerInterface;
use Kununu\CodeGenerator\Domain\Service\Template\StringTransformerInterface;
use Kununu\CodeGenerator\Domain\Service\Template\TemplatePathResolverInterface;
use Kununu\CodeGenerator\Domain\Service\Template\TemplateRegistryInterface;
use Kununu\CodeGenerator\Domain\Service\Template\TemplateRenderingServiceInterface;
use Kununu\CodeGenerator\Infrastructure\Generator\TwigTemplateGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TwigTemplateGeneratorTest extends TestCase
{
    private FileSystemHandlerInterface|MockObject $fileSystem;
    private TemplateRenderingServiceInterface|MockObject $renderer;
    private TemplatePathResolverInterface|MockObject $templatePathResolver;
    private TemplateRegistryInterface|MockObject $templateRegistry;
    private StringTransformerInterface|MockObject $stringTransformer;
    private TwigTemplateGenerator $generator;

    protected function setUp(): void
    {
        $this->fileSystem = $this->createMock(FileSystemHandlerInterface::class);
        $this->renderer = $this->createMock(TemplateRenderingServiceInterface::class);
        $this->templatePathResolver = $this->createMock(TemplatePathResolverInterface::class);
        $this->templateRegistry = $this->createMock(TemplateRegistryInterface::class);
        $this->stringTransformer = $this->createMock(StringTransformerInterface::class);

        $this->generator = new TwigTemplateGenerator(
            $this->fileSystem,
            $this->renderer,
            $this->templatePathResolver,
            $this->templateRegistry,
            $this->stringTransformer
        );
    }

    public function testCreateDefault(): void
    {
        $fileSystem = $this->createMock(FileSystemHandlerInterface::class);
        $fileSystem->method('exists')->willReturn(false);

        $generator = TwigTemplateGenerator::createDefault($fileSystem);

        $this->assertInstanceOf(TwigTemplateGenerator::class, $generator);
        $this->assertInstanceOf(CodeGeneratorInterface::class, $generator);
    }

    public function testGetFilesToGenerate(): void
    {
        $configuration = new BoilerplateConfiguration();
        $configuration->basePath = 'src';
        $configuration->namespace = 'App';
        $configuration->skipExisting = false;
        $configuration->addTemplateVariable('operation_id', 'getUserProfile');
        $configuration->addTemplateVariable('method', 'GET');

        $templates = [
            'controller' => [
                'path'          => 'controller.php.twig',
                'original_path' => 'controller.php.twig',
                'outputPattern' => '{basePath}/Controller/{operationName}Controller.php',
            ],
        ];

        $this->templateRegistry->expects($this->once())
            ->method('getAllTemplates')
            ->willReturn($templates);

        $this->templateRegistry->expects($this->once())
            ->method('shouldGenerateTemplate')
            ->willReturn(true);

        $this->stringTransformer->expects($this->atLeastOnce())
            ->method('extractEntityNameFromOperationId')
            ->with('getUserProfile')
            ->willReturn('User');

        $this->stringTransformer->expects($this->once())
            ->method('generateOutputPath')
            ->willReturn('src/Controller/GetUserProfileController.php');

        $this->stringTransformer->expects($this->once())
            ->method('getDynamicNamespace')
            ->willReturn('App\\Controller');

        $this->fileSystem->expects($this->once())
            ->method('exists')
            ->with('src/Controller/GetUserProfileController.php')
            ->willReturn(false);

        $this->templatePathResolver->expects($this->once())
            ->method('getTemplateSource')
            ->willReturn('default');

        $result = $this->generator->getFilesToGenerate($configuration);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('src/Controller/GetUserProfileController.php', $result[0]['path']);
        $this->assertFalse($result[0]['exists']);
        $this->assertFalse($result[0]['will_be_skipped']);
        $this->assertEquals('controller.php.twig', $result[0]['template']);
        $this->assertEquals('default', $result[0]['template_source']);
        $this->assertEquals('App\\Controller', $result[0]['full_namespace']);
        $this->assertEquals('GetUserProfileController', $result[0]['classname']);
        $this->assertEquals('App\\Controller\\GetUserProfileController', $result[0]['fqcn']);
    }

    public function testGenerate(): void
    {
        $configuration = new BoilerplateConfiguration();
        $configuration->basePath = 'src';
        $configuration->namespace = 'App';
        $configuration->skipExisting = false;
        $configuration->addTemplateVariable('operation_id', 'getUserProfile');
        $configuration->addTemplateVariable('method', 'GET');

        $templates = [
            'controller' => [
                'path'          => '@default/controller.php.twig',
                'original_path' => 'controller.php.twig',
                'outputPattern' => '{basePath}/Controller/{operationName}Controller.php',
            ],
        ];

        $this->templateRegistry->expects($this->any())
            ->method('getAllTemplates')
            ->willReturn($templates);

        $this->templateRegistry->expects($this->atLeastOnce())
            ->method('shouldGenerateTemplate')
            ->willReturn(true);

        $this->stringTransformer->expects($this->atLeastOnce())
            ->method('extractEntityNameFromOperationId')
            ->with('getUserProfile')
            ->willReturn('User');

        $this->stringTransformer->expects($this->atLeastOnce())
            ->method('generateOutputPath')
            ->willReturn('src/Controller/GetUserProfileController.php');

        $this->stringTransformer->expects($this->atLeastOnce())
            ->method('getDynamicNamespace')
            ->willReturn('App\\Controller');

        $this->fileSystem->expects($this->atLeastOnce())
            ->method('exists')
            ->willReturnMap([
                ['src/Controller/GetUserProfileController.php', false],
                ['src/Controller', false],
            ]);

        $this->renderer->expects($this->once())
            ->method('renderTemplate')
            ->with('@default/controller.php.twig', $this->callback(function($variables) {
                return isset($variables['templates']) && $variables['operation_id'] === 'getUserProfile';
            }))
            ->willReturn('<?php class GetUserProfileController {}');

        $this->fileSystem->expects($this->once())
            ->method('createDirectory')
            ->with('src/Controller');

        $this->fileSystem->expects($this->once())
            ->method('writeFile')
            ->with(
                'src/Controller/GetUserProfileController.php',
                '<?php class GetUserProfileController {}'
            );

        $result = $this->generator->generate($configuration);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('src/Controller/GetUserProfileController.php', $result[0]);
    }

    public function testRegisterDefaultTemplates(): void
    {
        $this->templateRegistry->expects($this->atLeast(20))
            ->method('registerTemplate');

        $this->generator = new TwigTemplateGenerator(
            $this->fileSystem,
            $this->renderer,
            $this->templatePathResolver,
            $this->templateRegistry,
            $this->stringTransformer
        );
    }

    #[DataProvider('skipExistingFilesDataProvider')]
    public function testSkipExistingFlag(
        bool $skipExisting,
        bool $fileExists,
        bool $expectedSkipFlag,
    ): void {
        $configuration = new BoilerplateConfiguration();
        $configuration->basePath = 'src';
        $configuration->namespace = 'App';
        $configuration->skipExisting = $skipExisting;
        $configuration->addTemplateVariable('operation_id', 'getUserProfile');
        $configuration->addTemplateVariable('method', 'GET');

        $templates = [
            'controller' => [
                'path'          => 'controller.php.twig',
                'original_path' => 'controller.php.twig',
                'outputPattern' => '{basePath}/Controller/{operationName}Controller.php',
            ],
        ];

        $this->templateRegistry->method('getAllTemplates')->willReturn($templates);
        $this->templateRegistry->method('shouldGenerateTemplate')->willReturn(true);
        $this->stringTransformer->method('extractEntityNameFromOperationId')->willReturn('User');
        $this->stringTransformer
            ->method('generateOutputPath')->willReturn('src/Controller/GetUserProfileController.php');
        $this->stringTransformer->method('getDynamicNamespace')->willReturn('App\\Controller');
        $this->fileSystem->method('exists')->willReturn($fileExists);
        $this->templatePathResolver->method('getTemplateSource')->willReturn('default');

        $result = $this->generator->getFilesToGenerate($configuration);

        $this->assertEquals($expectedSkipFlag, $result[0]['will_be_skipped']);
    }

    public static function skipExistingFilesDataProvider(): array
    {
        return [
            'Skip when file exists and skipExisting is true'            => [true, true, true],
            'Do not skip when file exists but skipExisting is false'    => [false, true, false],
            'Do not skip when file does not exist (skipExisting true)'  => [true, false, false],
            'Do not skip when file does not exist (skipExisting false)' => [false, false, false],
        ];
    }
}
