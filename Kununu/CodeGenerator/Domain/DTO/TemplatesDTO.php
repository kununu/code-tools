<?php

declare(strict_types=1);

namespace Kununu\CodeGenerator\Domain\DTO;

use InvalidArgumentException;

final class TemplatesDTO
{
    private array $templates = [];

    public function __construct(array $templates)
    {
        foreach ($templates as $template) {
            if (!$template instanceof TemplateDTO) {
                throw new InvalidArgumentException('All elements must be instances of TemplateDTO.');
            }
            $this->templates[strtolower($template->type)] = $template;
        }
    }

    public function getTemplateByType(string $type): ?TemplateDTO
    {
        return $this->templates[strtolower($type)] ?? null;
    }

    public function getAllTemplates(): array
    {
        return $this->templates;
    }

    public function getTemplateTypes(): array
    {
        return array_keys($this->templates);
    }

    public function hasTemplate(string $type): bool
    {
        return isset($this->templates[strtolower($type)]);
    }
}
