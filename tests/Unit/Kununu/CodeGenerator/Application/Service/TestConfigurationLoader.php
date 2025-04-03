<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Application\Service;

use Kununu\CodeGenerator\Domain\Service\ConfigurationLoaderInterface;

final class TestConfigurationLoader implements ConfigurationLoaderInterface
{
    private array $configByPath = [];
    private array $defaultConfig = [];

    public function loadConfig(string $configPath): array
    {
        if (isset($this->configByPath[$configPath])) {
            return $this->configByPath[$configPath];
        }

        return $this->defaultConfig;
    }

    public function setConfig(string $configPath, array $config): void
    {
        $this->configByPath[$configPath] = $config;
    }

    public function setDefaultConfig(array $config): void
    {
        $this->defaultConfig = $config;
    }
}
