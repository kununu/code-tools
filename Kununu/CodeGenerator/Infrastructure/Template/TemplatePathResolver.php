<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Infrastructure\Template;

use Kununu\CodeGenerator\Domain\Service\FileSystem\FileSystemHandlerInterface;
use Kununu\CodeGenerator\Domain\Service\Template\TemplatePathResolverInterface;

final readonly class TemplatePathResolver implements TemplatePathResolverInterface
{
    public function __construct(
        private FileSystemHandlerInterface $fileSystem,
        private ?string $customTemplateDir,
    ) {
    }

    public function resolveTemplatePath(string $templatePath): string
    {
        // Check if the template exists in the custom directory first
        if ($this->templateExistsInCustomDir($templatePath)) {
            return '@custom/' . $templatePath;
        }

        // Fall back to the default template
        return '@default/' . $templatePath;
    }

    public function templateExistsInCustomDir(string $templatePath): bool
    {
        if ($this->customTemplateDir === null) {
            return false;
        }

        $customPath = $this->customTemplateDir . '/' . $templatePath;

        return $this->fileSystem->exists($customPath);
    }

    public function getTemplateSource(string $templatePath): string
    {
        if ($this->customTemplateDir === null) {
            return 'default';
        }

        return $this->templateExistsInCustomDir($templatePath) ? 'custom' : 'default';
    }
}
