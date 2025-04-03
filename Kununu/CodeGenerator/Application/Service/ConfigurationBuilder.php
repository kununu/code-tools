<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Application\Service;

use Exception;
use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;
use Kununu\CodeGenerator\Domain\Exception\ConfigurationException;
use Kununu\CodeGenerator\Domain\Service\ConfigurationLoaderInterface;
use Kununu\CodeGenerator\Domain\Service\OpenApiParserInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Responsible for collecting configuration from various sources (files, command line, user input)
 * and constructing a complete BoilerplateConfiguration object.
 */
final class ConfigurationBuilder
{
    private SymfonyStyle $io;
    private ConfigurationLoaderInterface $configLoader;
    private OpenApiParserInterface $openApiParser;
    private ?BoilerplateConfiguration $configuration = null;

    public function __construct(
        SymfonyStyle $io,
        ConfigurationLoaderInterface $configLoader,
        OpenApiParserInterface $openApiParser,
    ) {
        $this->io = $io;
        $this->configLoader = $configLoader;
        $this->openApiParser = $openApiParser;
    }

    public function buildConfiguration(InputInterface $input, string $configPath): BoilerplateConfiguration
    {
        $config = $this->configLoader->loadConfig($configPath);
        $this->configuration = new BoilerplateConfiguration();

        $this->initializeBasicConfig($config);
        $this->applyCommandLineOverrides($input, $config);

        if (!$input->getOption('manual')) {
            $this->configureOpenApiSettings($input, $config, !$input->getOption('non-interactive'));
        }

        return $this->configuration;
    }

    private function initializeBasicConfig(array $config): void
    {
        $this->configuration->setBasePath($config['base_path'] ?? 'src');
        $this->configuration->setNamespace($config['namespace'] ?? 'App');

        if (isset($config['path_patterns']) && is_array($config['path_patterns'])) {
            $this->configuration->setPathPatterns($config['path_patterns']);
        }

        if (isset($config['generators']) && is_array($config['generators'])) {
            $this->configuration->setGenerators($config['generators']);
        }

        // Set template directory if specified in config
        if (isset($config['templates']['path']) && $config['templates']['path'] !== null) {
            $templateDir = $config['templates']['path'];
            // Make sure the path is absolute
            if (!str_starts_with($templateDir, '/')) {
                $templateDir = getcwd() . '/' . $templateDir;
            }
            $this->configuration->setTemplateDir($templateDir);
        }

        $this->configuration->setForce($config['force'] ?? false);
        $this->configuration->setSkipExisting($config['skip_existing'] ?? false);
    }

    private function applyCommandLineOverrides(InputInterface $input, array $config): void
    {
        // Template directory command-line override
        if ($input->getOption('template-dir')) {
            $templateDir = $input->getOption('template-dir');
            // Make sure the path is absolute
            if (!str_starts_with($templateDir, '/')) {
                $templateDir = getcwd() . '/' . $templateDir;
            }
            $this->configuration->setTemplateDir($templateDir);
        }

        $forceFromCommandLine = $input->getOption('force');
        if ($forceFromCommandLine) {
            $this->configuration->setForce(true);
        }

        $skipExistingFromCommandLine = $input->getOption('skip-existing');
        if ($skipExistingFromCommandLine) {
            $this->configuration->setSkipExisting(true);
        }
    }

    private function configureOpenApiSettings(
        InputInterface $input,
        array $config,
        bool $isInteractive,
    ): void {
        $openApiFilePath = $this->getOpenApiFilePath($input, $config, $isInteractive);
        $this->configuration->setOpenApiFilePath($openApiFilePath);

        if ($openApiFilePath !== null) {
            $operationId = $this->getOperationId($input, $isInteractive, $openApiFilePath);
            $this->configuration->setOperationId($operationId);
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

    // phpcs:disable Kununu.Files.LineLength
    private function selectOperationInteractively(string $openApiFilePath): ?string
    {
        try {
            $this->openApiParser->parseFile($openApiFilePath);
            $operations = $this->openApiParser->listOperations();

            if (empty($operations)) {
                $this->io->warning('No operations found in the OpenAPI specification');

                return null;
            }

            $this->io->writeln('Available operations:');
            foreach ($operations as $index => $op) {
                $this->io->writeln(
                    sprintf(' %d. <info>%s</info> - %s', $index + 1, $op['id'], $op['summary'])
                );
            }

            $selection = $this->io->ask(
                'Select operation by number or provide operationId',
                null,
                function($value) {
                    if (is_numeric($value) && (int) $value < 1) {
                        throw new ConfigurationException('Invalid selection, please provide a valid operation number or ID');
                    }

                    if (empty($value)) {
                        throw new ConfigurationException('Operation cannot be empty, consider trying manual mode with -m option');
                    }

                    return $value;
                }
            );

            if (is_numeric($selection) && isset($operations[(int) $selection - 1])) {
                return $operations[(int) $selection - 1]['id'];
            }

            return $selection;
        } catch (Exception $e) {
            throw new ConfigurationException(sprintf('Error parsing OpenAPI file: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }
    // phpcs:enable

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
