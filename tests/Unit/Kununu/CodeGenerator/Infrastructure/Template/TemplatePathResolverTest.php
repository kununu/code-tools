<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Infrastructure\Template;

use Kununu\CodeGenerator\Domain\Service\FileSystem\FileSystemHandlerInterface;
use Kununu\CodeGenerator\Infrastructure\Template\TemplatePathResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TemplatePathResolverTest extends TestCase
{
    private FileSystemHandlerInterface|MockObject $fileSystem;
    private TemplatePathResolver $resolver;

    protected function setUp(): void
    {
        $this->fileSystem = $this->createMock(FileSystemHandlerInterface::class);
    }

    #[DataProvider('resolveTemplatePathDataProvider')]
    public function testResolveTemplatePath(
        string $templatePath,
        ?string $customTemplateDir,
        bool $existsInCustom,
        string $expected,
    ): void {
        $this->resolver = new TemplatePathResolver($this->fileSystem, $customTemplateDir);

        if ($customTemplateDir !== null) {
            $this->fileSystem
                ->expects($this->once())
                ->method('exists')
                ->with($customTemplateDir . '/' . $templatePath)
                ->willReturn($existsInCustom);
        }

        $result = $this->resolver->resolveTemplatePath($templatePath);

        $this->assertEquals($expected, $result);
    }

    public static function resolveTemplatePathDataProvider(): array
    {
        return [
            'Template exists in custom dir' => [
                'controller.php.twig',
                '/custom/templates',
                true,
                '@custom/controller.php.twig',
            ],
            'Template does not exist in custom dir' => [
                'controller.php.twig',
                '/custom/templates',
                false,
                '@default/controller.php.twig',
            ],
            'No custom template dir' => [
                'controller.php.twig',
                null,
                false,
                '@default/controller.php.twig',
            ],
            'Nested template path' => [
                'admin/controller.php.twig',
                '/custom/templates',
                true,
                '@custom/admin/controller.php.twig',
            ],
        ];
    }

    #[DataProvider('templateExistsInCustomDirDataProvider')]
    public function testTemplateExistsInCustomDir(
        string $templatePath,
        ?string $customTemplateDir,
        bool $exists,
        bool $expected,
    ): void {
        $this->resolver = new TemplatePathResolver($this->fileSystem, $customTemplateDir);

        if ($customTemplateDir !== null) {
            $this->fileSystem
                ->expects($this->once())
                ->method('exists')
                ->with($customTemplateDir . '/' . $templatePath)
                ->willReturn($exists);
        }

        $result = $this->resolver->templateExistsInCustomDir($templatePath);

        $this->assertEquals($expected, $result);
    }

    public static function templateExistsInCustomDirDataProvider(): array
    {
        return [
            'Template exists in custom dir' => [
                'controller.php.twig',
                '/custom/templates',
                true,
                true,
            ],
            'Template does not exist in custom dir' => [
                'controller.php.twig',
                '/custom/templates',
                false,
                false,
            ],
            'No custom template dir' => [
                'controller.php.twig',
                null,
                false,
                false,
            ],
            'Nested template path' => [
                'admin/controller.php.twig',
                '/custom/templates',
                true,
                true,
            ],
        ];
    }

    #[DataProvider('getTemplateSourceDataProvider')]
    public function testGetTemplateSource(
        string $templatePath,
        ?string $customTemplateDir,
        bool $existsInCustom,
        string $expected,
    ): void {
        $this->resolver = new TemplatePathResolver($this->fileSystem, $customTemplateDir);

        if ($customTemplateDir !== null) {
            $this->fileSystem
                ->expects($this->once())
                ->method('exists')
                ->with($customTemplateDir . '/' . $templatePath)
                ->willReturn($existsInCustom);
        }

        $result = $this->resolver->getTemplateSource($templatePath);

        $this->assertEquals($expected, $result);
    }

    public static function getTemplateSourceDataProvider(): array
    {
        return [
            'Template exists in custom dir' => [
                'controller.php.twig',
                '/custom/templates',
                true,
                'custom',
            ],
            'Template does not exist in custom dir' => [
                'controller.php.twig',
                '/custom/templates',
                false,
                'default',
            ],
            'No custom template dir' => [
                'controller.php.twig',
                null,
                false,
                'default',
            ],
            'Nested template path' => [
                'admin/controller.php.twig',
                '/custom/templates',
                true,
                'custom',
            ],
        ];
    }
}
