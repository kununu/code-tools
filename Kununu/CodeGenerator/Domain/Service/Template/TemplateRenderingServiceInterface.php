<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Domain\Service\Template;

interface TemplateRenderingServiceInterface
{
    /**
     * Renders a template with the given variables
     */
    public function renderTemplate(string $templatePath, array $variables): string;

    /**
     * Registers filters that can be used in templates
     */
    public function registerFilters(array $filters): void;
}
