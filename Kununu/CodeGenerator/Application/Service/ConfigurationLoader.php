<?php

declare(strict_types=1);

namespace Kununu\CodeGenerator\Application\Service;

use Exception;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

final class ConfigurationLoader
{
    private Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function loadConfig(string $configPath): array
    {
        if (!$this->filesystem->exists($configPath)) {
            return $this->getDefaultConfig();
        }

        $extension = pathinfo($configPath, PATHINFO_EXTENSION);

        if ($extension === '') {
            // No extension, try to determine format from content
            $content = file_get_contents($configPath);

            if (str_starts_with(trim($content), '{')) {
                // Looks like JSON
                return $this->parseJson($content, $configPath);
            }

            // Assume YAML
            return $this->parseYaml($content, $configPath);
        }

        return match (strtolower($extension)) {
            'json' => $this->parseJson(file_get_contents($configPath), $configPath),
            'yaml', 'yml' => $this->parseYaml(file_get_contents($configPath), $configPath),
            default => throw new RuntimeException(sprintf('Unsupported configuration file format: %s', $extension)),
        };
    }

    private function parseJson(string $content, string $path): array
    {
        $config = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf(
                'Failed to parse JSON configuration file %s: %s',
                $path,
                json_last_error_msg()
            ));
        }

        return $this->mergeWithDefaults($config);
    }

    private function parseYaml(string $content, string $path): array
    {
        try {
            $config = Yaml::parse($content);

            return $this->mergeWithDefaults($config);
        } catch (Exception $e) {
            throw new RuntimeException(sprintf(
                'Failed to parse YAML configuration file %s: %s',
                $path,
                $e->getMessage()
            ));
        }
    }

    private function getDefaultConfig(): array
    {
        return [
            'base_path'            => 'src',
            'namespace'            => 'App',
            'default_openapi_path' => null,
            'force'                => false,
            'skip_existing'        => false,
            'templates'            => [
                'path' => null, // Will use default templates
            ],
            'path_patterns' => [
                'controller'                   => '{basePath}/Controller/{operationName}Controller.php',
                'query'                        => '{basePath}/UseCase/Query/{operationName}/Query.php',
                'query-handler'                => '{basePath}/UseCase/Query/{operationName}/QueryHandler.php',
                'criteria'                     => '{basePath}/UseCase/Query/{operationName}/Criteria/Criteria.php',
                'read-model'                   => '{basePath}/UseCase/Query/{operationName}/ReadModel/{entityName}.php',
                'command'                      => '{basePath}/UseCase/Command/{operationName}/Command.php',
                'command-handler'              => '{basePath}/UseCase/Command/{operationName}/CommandHandler.php',
                'request-data'                 => '{basePath}/Request/DTO/{operationName}RequestData.php',
                'request-resolver'             => '{basePath}/Request/Resolver/{operationName}Resolver.php',
                'command-dto'                  => '{basePath}/UseCase/Command/{operationName}/DTO/{entityName}.php',
                'query-repository-interface'   => '{basePath}/UseCase/Query/{operationName}/Repository/RepositoryInterface.php',
                'command-repository-interface' => '{basePath}/UseCase/Command/{operationName}/RepositoryInterface.php',
                'query-repository'             => '{basePath}/UseCase/Query/{operationName}/Infrastructure/DoctrineRepository.php',
                'command-repository'           => '{basePath}/UseCase/Command/{operationName}/Infrastructure/DoctrineRepository.php',
                'query-exception'              => '{basePath}/UseCase/Query/{operationName}/Exception/{entityName}NotFoundException.php',
                'query-infrastructure-query'   => '{basePath}/UseCase/Query/{operationName}/Infrastructure/Query/{operationName}.php',
                'query-readme'                 => '{basePath}/UseCase/Query/{operationName}/README.md',
                'command-readme'               => '{basePath}/UseCase/Command/{operationName}/README.md',
            ],
            'generators' => [
                'controller' => true,
                'dto'        => true,
                'command'    => true,
                'repository' => true,
                'tests'      => true,
            ],
        ];
    }

    private function mergeWithDefaults(array $config): array
    {
        return array_merge($this->getDefaultConfig(), $config);
    }
}
