<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Domain\Service;

use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;

interface FileGenerationHandlerInterface
{
    public function processFilesToGenerate(BoilerplateConfiguration $configuration, bool $skipPreview): array;

    public function generateFiles(BoilerplateConfiguration $configuration, array $filesToGenerate, bool $quiet): array;
}
