<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Domain\Service\Template;

interface TemplatePathResolverInterface
{
    /**
     * Resolves a template path, checking custom directories first and falling back to default
     */
    public function resolveTemplatePath(string $templatePath): string;

    /**
     * Checks if a template exists in a custom directory
     */
    public function templateExistsInCustomDir(string $templatePath): bool;

    /**
     * Gets the source (custom or default) of a template
     */
    public function getTemplateSource(string $templatePath): string;
}
