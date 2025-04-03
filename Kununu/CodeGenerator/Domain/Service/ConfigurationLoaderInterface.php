<?php

declare(strict_types=1);

namespace Kununu\CodeGenerator\Domain\Service;

interface ConfigurationLoaderInterface
{
    public function loadConfig(string $configPath): array;
}
