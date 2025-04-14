<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Domain\Service;

use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;

interface CodeGeneratorInterface
{
    public function generate(BoilerplateConfiguration $configuration): array;

    public function getFilesToGenerate(BoilerplateConfiguration $configuration): array;
}
