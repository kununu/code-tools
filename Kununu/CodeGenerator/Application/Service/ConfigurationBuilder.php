<?php

declare(strict_types=1);

namespace Kununu\CodeGenerator\Application\Service;

use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Service responsible for building and configuring the BoilerplateConfiguration.
 */
class ConfigurationBuilder
{
    private SymfonyStyle $io;
    private ConfigurationLoader $configLoader;
    private OpenApiParser $openApiParser;

    public function __construct(
        SymfonyStyle $io,
        ConfigurationLoader $configLoader,
        OpenApiParser $openApiParser,
    ) {
        $this->io = $io;
        $this->configLoader = $configLoader;
        $this->openApiParser = $openApiParser;
    }

    public function buildConfiguration(InputInterface $input, string $configPath): BoilerplateConfiguration
    {
        $config = $this->configLoader->loadConfig($configPath);

        return $this->collectConfiguration($input, $config);
    }

    public function collectConfiguration(InputInterface $input, array $config): BoilerplateConfiguration
    {
        $isInteractive = !$input->getOption('non-interactive');
        $configuration = new BoilerplateConfiguration();

        $configuration->setBasePath($config['base_path'] ?? 'src');
        $configuration->setNamespace($config['namespace'] ?? 'App');

        if (isset($config['path_patterns']) && is_array($config['path_patterns'])) {
            $configuration->setPathPatterns($config['path_patterns']);
        }

        if (isset($config['generators']) && is_array($config['generators'])) {
            $configuration->setGenerators($config['generators']);
        }

        $forceFromCommandLine = $input->getOption('force');
        $forceFromConfig = $config['force'] ?? false;
        $configuration->setForce($forceFromCommandLine || $forceFromConfig);

        $skipExistingFromCommandLine = $input->getOption('skip-existing');
        $skipExistingFromConfig = $config['skip_existing'] ?? false;
        $configuration->setSkipExisting($skipExistingFromCommandLine || $skipExistingFromConfig);

        if (!$input->getOption('manual')) {
            $this->configureOpenApiSettings($input, $config, $configuration, $isInteractive);
        }

        return $configuration;
    }

    private function configureOpenApiSettings(
        InputInterface $input,
        array $config,
        BoilerplateConfiguration $configuration,
        bool $isInteractive,
    ): void {
        $openApiFilePath = $this->getOpenApiFilePath($input, $config, $isInteractive);
        $configuration->setOpenApiFilePath($openApiFilePath);

        if ($openApiFilePath !== null) {
            $operationId = $this->getOperationId($input, $isInteractive, $openApiFilePath);
            $configuration->setOperationId($operationId);
        }
    }

    private function getOpenApiFilePath(InputInterface $input, array $config, bool $isInteractive): ?string
    {
        $openApiFilePath = $input->getOption('openapi-file');

        if ($openApiFilePath === null && $isInteractive) {
            $openApiFilePath = $this->io->ask(
                'Path to OpenAPI specification file',
                $config['default_openapi_path'] ?? null
            );
        }

        if ($openApiFilePath !== null) {
            $openApiFilePath = $this->resolveFilePath($openApiFilePath);
        }

        return $openApiFilePath;
    }

    private function getOperationId(InputInterface $input, bool $isInteractive, string $openApiFilePath): ?string
    {
        $operationId = $input->getOption('operation-id');

        if ($operationId === null && $isInteractive) {
            $operationId = $this->selectOperationInteractively($openApiFilePath);
        }

        return $operationId;
    }

    private function selectOperationInteractively(string $openApiFilePath): ?string
    {
        $this->openApiParser->parseFile($openApiFilePath);
        $operations = $this->openApiParser->listOperations();

        if (empty($operations)) {
            $this->io->warning('No operations found in the OpenAPI specification');

            return null;
        }

        $this->io->writeln('Available operations:');
        foreach ($operations as $index => $op) {
            $this->io->writeln(sprintf(' %d. <info>%s</info> - %s', $index + 1, $op['id'], $op['summary']));
        }

        $selection = $this->io->ask('Select operation by number or provide operationId');

        if (is_numeric($selection) && isset($operations[(int) $selection - 1])) {
            return $operations[(int) $selection - 1]['id'];
        }

        return $selection;
    }

    private function isAbsolutePath(string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            return $path[0] === '\\' || (isset($path[1]) && $path[1] === ':');
        }

        return $path[0] === '/';
    }

    private function resolveFilePath(string $path): string
    {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return getcwd() . DIRECTORY_SEPARATOR . $path;
    }
}
