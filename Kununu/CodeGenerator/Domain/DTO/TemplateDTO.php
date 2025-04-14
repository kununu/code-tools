<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Domain\DTO;

final readonly class TemplateDTO
{
    public function __construct(
        public string $type,
        public string $template,
        public array $templateVariables = [],
        public ?string $path = null,
        public ?string $outputPath = null,
        public ?string $namespace = null,
        public ?string $classname = null,
        public ?string $fqcn = null,
        public ?string $filename = null,
        public ?string $dirname = null,
    ) {
    }
}
