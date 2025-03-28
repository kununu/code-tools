<?php

declare(strict_types=1);

namespace Kununu\CodeGenerator\Infrastructure\Generator;

use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;
use Kununu\CodeGenerator\Domain\Service\CodeGeneratorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

final class TwigTemplateGenerator implements CodeGeneratorInterface
{
    private Filesystem $filesystem;
    private Environment $twig;
    private array $templates = [];
    private string $templateDir;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->templateDir = dirname(__DIR__, 3) . '/CodeGenerator/Templates';

        $loader = new FilesystemLoader($this->templateDir);
        $this->twig = new Environment($loader, [
            'cache'            => false,
            'debug'            => true,
            'strict_variables' => true,
            'autoescape'       => false,
        ]);

        // Add custom filters
        $this->registerTwigFilters();

        // Register default templates
        $this->registerDefaultTemplates();
    }

    /**
     * Register custom Twig filters
     */
    private function registerTwigFilters(): void
    {
        // Add a proper camel case filter that preserves existing uppercase letters
        $this->twig->addFilter(new TwigFilter('properCapitalize', function($string) {
            return $this->convertOperationIdToClassName($string);
        }));
    }

    public function registerTemplate(string $templateName, string $templatePath, string $outputPattern): void
    {
        $this->templates[$templateName] = [
            'path'          => $templatePath,
            'outputPattern' => $outputPattern,
        ];
    }

    public function generate(BoilerplateConfiguration $configuration): array
    {
        $generatedFiles = [];
        $variables = $configuration->getTemplateVariables();

        // Ensure we have an entity_name variable
        if (!isset($variables['entity_name']) && isset($variables['operation_id'])) {
            $variables['entity_name'] = $this->extractEntityNameFromOperationId($variables['operation_id']);
        }

        foreach ($this->templates as $templateName => $template) {
            // Determine if we should generate this file based on configuration
            if (!$this->shouldGenerateFile($templateName, $configuration)) {
                continue;
            }

            // Get output pattern - prefer custom pattern from config if available
            $outputPattern = $template['outputPattern'];
            if (!empty($configuration->pathPatterns) && isset($configuration->pathPatterns[$templateName])) {
                $outputPattern = $configuration->pathPatterns[$templateName];
            }

            // Generate output path from pattern
            $outputPath = $this->generateOutputPath(
                $outputPattern,
                $configuration->basePath,
                $variables
            );

            // Create output directory if it doesn't exist
            $directory = dirname($outputPath);
            if (!$this->filesystem->exists($directory)) {
                $this->filesystem->mkdir($directory, 0755);
            }

            // Render the template
            $content = $this->twig->render($template['path'], $variables);

            // Write to file
            $this->filesystem->dumpFile($outputPath, $content);
            $generatedFiles[] = $outputPath;
        }

        return $generatedFiles;
    }

    /**
     * Get a list of files that would be generated without actually generating them
     */
    public function getFilesToGenerate(BoilerplateConfiguration $configuration): array
    {
        $filesToGenerate = [];
        $variables = $configuration->getTemplateVariables();

        // Ensure we have an entity_name variable
        if (!isset($variables['entity_name']) && isset($variables['operation_id'])) {
            $variables['entity_name'] = $this->extractEntityNameFromOperationId($variables['operation_id']);
        }

        foreach ($this->templates as $templateName => $template) {
            // Determine if we should generate this file based on configuration
            if (!$this->shouldGenerateFile($templateName, $configuration)) {
                continue;
            }

            // Get output pattern - prefer custom pattern from config if available
            $outputPattern = $template['outputPattern'];
            if (!empty($configuration->pathPatterns) && isset($configuration->pathPatterns[$templateName])) {
                $outputPattern = $configuration->pathPatterns[$templateName];
            }

            // Generate output path from pattern
            $outputPath = $this->generateOutputPath(
                $outputPattern,
                $configuration->basePath,
                $variables
            );

            $filesToGenerate[] = [
                'path'          => $outputPath,
                'template'      => $templateName,
                'template_path' => $template['path'],
            ];
        }

        return $filesToGenerate;
    }

    private function registerDefaultTemplates(): void
    {
        $this->registerTemplate('services-config', 'misc/services.yaml.twig', '{basePath}/UseCase/{cqrsType}/{operationName}/Resources/config/services.yaml');
        // Controller template
        $this->registerTemplate('controller', 'controller.php.twig', '{basePath}/Controller/{operationName}Controller.php');

        // Register all templates - they'll be filtered at generation time based on HTTP method
        // Query related templates
        $this->registerTemplate('query', 'query/query.php.twig', '{basePath}/UseCase/Query/{operationName}/Query.php');
        $this->registerTemplate('query-handler', 'query/handler.php.twig', '{basePath}/UseCase/Query/{operationName}/QueryHandler.php');
        $this->registerTemplate('criteria', 'query/criteria.php.twig', '{basePath}/UseCase/Query/{operationName}/Criteria/Criteria.php');
        $this->registerTemplate('read-model', 'query/read_model.php.twig', '{basePath}/UseCase/Query/{operationName}/ReadModel/{entityName}.php');
        $this->registerTemplate('query-repository-interface', 'repository/interface.php.twig', '{basePath}/UseCase/Query/{operationName}/RepositoryInterface.php');
        $this->registerTemplate('query-exception', 'query/exception.php.twig', '{basePath}/UseCase/Query/{operationName}/Exception/{entityName}NotFoundException.php');
        $this->registerTemplate('query-infrastructure-query', 'query/infrastructure_query.php.twig', '{basePath}/UseCase/Query/{operationName}/Infrastructure/Query/{operationName}.php');
        $this->registerTemplate('query-serializer-xml', 'query/serializer.xml.twig', '{basePath}/UseCase/Query/{operationName}/ReadModel/serializer/serializer.xml');
        $this->registerTemplate('query-readme', 'query/readme.md.twig', '{basePath}/UseCase/Query/{operationName}/README.md');
        $this->registerTemplate('jms-serializer-config', 'query/jms_serializer.yaml.twig', '{basePath}/UseCase/Query/{operationName}/Resources/config/jms_serializer.yaml');

        // Command related templates
        $this->registerTemplate('command', 'command/command.php.twig', '{basePath}/UseCase/Command/{operationName}/Command.php');
        $this->registerTemplate('command-handler', 'command/handler.php.twig', '{basePath}/UseCase/Command/{operationName}/CommandHandler.php');
        $this->registerTemplate('request-data', 'request/request_data.php.twig', '{basePath}/Request/DTO/{operationName}RequestData.php');
        $this->registerTemplate('request-resolver', 'request/resolver.php.twig', '{basePath}/Request/Resolver/{operationName}Resolver.php');
        $this->registerTemplate('command-dto', 'command/dto.php.twig', '{basePath}/UseCase/Command/{operationName}/DTO/{entityName}.php');
        $this->registerTemplate('command-repository-interface', 'repository/interface.php.twig', '{basePath}/UseCase/Command/{operationName}/RepositoryInterface.php');
        $this->registerTemplate('command-readme', 'command/readme.md.twig', '{basePath}/UseCase/Command/{operationName}/README.md');

        // Register repository implementation templates - they'll be filtered based on HTTP method
        $this->registerTemplate('query-repository', 'repository/implementation.php.twig', '{basePath}/UseCase/Query/{operationName}/Infrastructure/DoctrineRepository.php');
        $this->registerTemplate('command-repository', 'repository/implementation.php.twig', '{basePath}/UseCase/Command/{operationName}/Infrastructure/DoctrineRepository.php');
    }

    private function shouldGenerateFile(string $templateName, BoilerplateConfiguration $configuration): bool
    {
        $variables = $configuration->getTemplateVariables();
        $method = $variables['method'] ?? '';

        // Filter templates based on HTTP method
        $queryTemplates = ['query', 'query-handler', 'criteria', 'read-model', 'query-repository-interface', 'query-repository', 'query-exception', 'query-infrastructure-query', 'query-readme', 'jms-serializer-config'];
        $commandTemplates = ['command', 'command-handler', 'request-data', 'request-resolver', 'command-dto', 'command-repository-interface', 'command-repository', 'command-readme'];

        if (strtoupper($method) === 'GET' && in_array($templateName, $commandTemplates)) {
            return false;
        }

        if (strtoupper($method) !== 'GET' && in_array($templateName, $queryTemplates)) {
            return false;
        }

        // XML Serializer templates
        if ($templateName === 'query-serializer-xml' && strtoupper($method) !== 'GET') {
            return false;
        }

        // Check generators configuration
        if (!empty($configuration->generators)) {
            // Controller templates
            if ($templateName === 'controller'
                && isset($configuration->generators['controller'])
                && $configuration->generators['controller'] === false) {
                return false;
            }

            // DTO templates
            if (in_array($templateName, ['request-data', 'request-resolver'])
                && isset($configuration->generators['dto'])
                && $configuration->generators['dto'] === false) {
                return false;
            }

            // Command templates
            if (in_array($templateName, ['command', 'command-handler', 'command-dto', 'command-repository-interface', 'command-repository'])
                && isset($configuration->generators['command'])
                && $configuration->generators['command'] === false) {
                return false;
            }

            // Repository templates
            if (in_array($templateName, ['query-repository-interface', 'query-repository', 'command-repository-interface', 'command-repository'])
                && isset($configuration->generators['repository'])
                && $configuration->generators['repository'] === false) {
                return false;
            }

            // XML or jms Serializer templates
            if (($templateName === 'query-serializer-xml' || $templateName === 'jms-serializer-config')
                && isset($configuration->generators['xml-serializer'])
                && $configuration->generators['xml-serializer'] === false) {
                return false;
            }
        }

        return true;
    }

    private function generateOutputPath(string $pattern, string $basePath, array $variables): string
    {
        // Replace {basePath} placeholder
        $outputPath = str_replace('{basePath}', $basePath, $pattern);

        if (isset($variables['cqrsType'])) {
            $outputPath = str_replace('{cqrsType}', $variables['cqrsType'], $outputPath);
        }

        // Replace operation-related placeholders
        if (isset($variables['operation_id'])) {
            $operationName = $this->convertOperationIdToClassName($variables['operation_id']);
            $outputPath = str_replace('{operationName}', $operationName, $outputPath);
        }

        // Replace entity placeholder
        if (isset($variables['entity_name'])) {
            $outputPath = str_replace('{entityName}', $variables['entity_name'], $outputPath);
        } elseif (isset($variables['operation_id'])) {
            // Extract entity name from operation ID if not provided
            $entityName = $this->extractEntityNameFromOperationId($variables['operation_id']);
            $outputPath = str_replace('{entityName}', $entityName, $outputPath);
        }

        return $outputPath;
    }

    private function convertOperationIdToClassName(string $operationId): string
    {
        // Properly capitalize each word in camelCase strings
        // e.g., "getToneOfVoiceSettings" becomes "GetToneOfVoiceSettings"
        $parts = preg_split('/(?=[A-Z])/', $operationId, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts)) {
            return ucfirst($operationId);
        }

        // Properly capitalize the first part which likely doesn't start with uppercase
        $parts[0] = ucfirst($parts[0]);

        return implode('', $parts);
    }

    private function extractEntityNameFromOperationId(string $operationId): string
    {
        // Extract entity name from operation ID
        // Example: 'updateUserProfile' would return 'User'

        // Remove common prefixes
        $name = preg_replace('/^(get|create|update|delete|find|list)/', '', $operationId);

        // Remove common suffixes
        $name = preg_replace('/(List|Collection|Item|By.*)$/', '', $name);

        // Return the first part of the remaining string (likely the entity name)
        $matches = [];
        if (preg_match('/^([A-Z][a-z0-9]+)/', ucfirst($name), $matches)) {
            return $matches[1];
        }

        return ucfirst($name);
    }
}
