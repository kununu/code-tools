<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Domain\Service\Template;

interface TemplateRegistryInterface
{
    /**
     * Registers a template with a name, path, and output pattern
     */
    public function registerTemplate(string $templateName, string $templatePath, string $outputPattern): void;

    /**
     * Gets all registered templates
     */
    public function getAllTemplates(): array;

    /**
     * Checks if a template should be generated based on configuration
     */
    public function shouldGenerateTemplate(string $templateName, array $configuration, array $variables): bool;
}
