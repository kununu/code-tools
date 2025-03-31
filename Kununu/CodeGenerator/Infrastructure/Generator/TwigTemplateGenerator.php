<?php

declare(strict_types=1);

namespace Kununu\CodeGenerator\Infrastructure\Generator;

use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;
use Kununu\CodeGenerator\Domain\Service\CodeGeneratorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

final class TwigTemplateGenerator implements CodeGeneratorInterface
{
    private Filesystem $filesystem;
    private Environment $twig;
    private array $templates = [];
    private string $defaultTemplateDir;
    private ?string $customTemplateDir;

    public function __construct(Filesystem $filesystem, ?string $customTemplateDir = null)
    {
        $this->filesystem = $filesystem;
        $this->defaultTemplateDir = dirname(__DIR__, 3) . '/CodeGenerator/Templates';
        $this->customTemplateDir = $customTemplateDir;

        // Set up Twig with multiple template directories
        $loader = new FilesystemLoader([]);

        // Add the default template directory
        $loader->addPath($this->defaultTemplateDir, 'default');

        // Add the custom template directory if provided
        if ($this->customTemplateDir !== null && $this->filesystem->exists($this->customTemplateDir)) {
            $loader->addPath($this->customTemplateDir, 'custom');
        }

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

    private function registerTwigFilters(): void
    {
        // Add a proper camel case filter that preserves existing uppercase letters
        $this->twig->addFilter(new TwigFilter('properCapitalize', function($string) {
            return $this->convertOperationIdToClassName($string);
        }));
        $this->twig->addFilter(new TwigFilter('camel', fn($string) => lcfirst($this->pascalCase($string))));
        $this->twig->addFilter(new TwigFilter('pascal', fn($string) => $this->pascalCase($string)));
        $this->twig->addFilter(new TwigFilter('snake', fn($string) => $this->snakeCase($string)));
    }

    public function registerTemplate(string $templateName, string $templatePath, string $outputPattern): void
    {
        $resolvedTemplatePath = $this->getTemplatePath($templatePath);
        $this->templates[$templateName] = [
            'path'          => $resolvedTemplatePath,
            'original_path' => $templatePath,
            'outputPattern' => $outputPattern,
        ];
    }

    private function getTemplatePath(string $templatePath): string
    {
        // Check if the template exists in the custom directory first
        if ($this->customTemplateDir !== null) {
            $customPath = $this->customTemplateDir . '/' . $templatePath;
            if ($this->filesystem->exists($customPath)) {
                return '@custom/' . $templatePath;
            }
        }

        // Fall back to the default template
        return '@default/' . $templatePath;
    }

    public function templateExistsInCustomDir(string $templatePath): bool
    {
        if ($this->customTemplateDir === null) {
            return false;
        }

        $customPath = $this->customTemplateDir . '/' . $templatePath;

        return $this->filesystem->exists($customPath);
    }

    public function getTemplateSource(string $templatePath): string
    {
        if ($this->customTemplateDir === null) {
            return 'default';
        }

        $customPath = $this->customTemplateDir . '/' . $templatePath;

        return $this->filesystem->exists($customPath) ? 'custom' : 'default';
    }

    public function generate(BoilerplateConfiguration $configuration): array
    {
        $generatedFiles = [];
        $variables = $configuration->getTemplateVariables();
        $filesToGenerate = $this->getFilesToGenerate($configuration);
        $existingFiles = [];

        // Collect existing files
        foreach ($filesToGenerate as $file) {
            if ($file['exists']) {
                $existingFiles[] = $file['path'];
            }
        }

        // Store existing files in configuration for the command to use
        $configuration->existingFiles = $existingFiles;

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

            // Skip this file if it's in the skipFiles list
            if (isset($configuration->skipFiles) && in_array($outputPath, $configuration->skipFiles)) {
                continue;
            }

            // Create output directory if it doesn't exist
            $directory = dirname($outputPath);
            if (!$this->filesystem->exists($directory)) {
                $this->filesystem->mkdir($directory, 0755);
            }

            // Render the template
            try {
                $content = $this->twig->render($template['path'], $variables);

                // Write to file
                $this->filesystem->dumpFile($outputPath, $content);
                $generatedFiles[] = $outputPath;
            } catch (LoaderError $e) {
                // If there's an error loading the template, log it and continue
                error_log("Error loading template {$template['path']}: " . $e->getMessage());
            }
        }

        return $generatedFiles;
    }

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

            // Check if the file already exists
            $exists = $this->filesystem->exists($outputPath);

            // Determine if the file will be skipped based on configuration
            $willBeSkipped = $exists && $configuration->skipExisting;

            // Add to the list of files to generate
            $filesToGenerate[] = [
                'path'            => $outputPath,
                'exists'          => $exists,
                'will_be_skipped' => $willBeSkipped,
                'template'        => $template['original_path'],
                'template_path'   => $template['path'],
                'template_source' => $this->getTemplateSource($template['original_path']),
            ];
        }

        return $filesToGenerate;
    }

    private function registerDefaultTemplates(): void
    {
        // Register all templates - they'll be filtered at generation time based on HTTP method
        // Controller template
        $this->registerTemplate('controller', 'controller.php.twig', '{basePath}/Controller/{operationName}Controller.php');

        // Query related templates
        $this->registerTemplate('query', 'query/query.php.twig', '{basePath}/UseCase/Query/{operationName}/Query.php');
        $this->registerTemplate('query-handler', 'query/handler.php.twig', '{basePath}/UseCase/Query/{operationName}/QueryHandler.php');
        $this->registerTemplate('criteria', 'query/criteria.php.twig', '{basePath}/UseCase/Query/{operationName}/Criteria/Criteria.php');
        $this->registerTemplate('read-model', 'query/read_model.php.twig', '{basePath}/UseCase/Query/{operationName}/ReadModel/{entityName}.php');
        $this->registerTemplate('query-repository-interface', 'repository/interface.php.twig', '{basePath}/UseCase/Query/{operationName}/RepositoryInterface.php');
        $this->registerTemplate('query-exception', 'query/exception.php.twig', '{basePath}/UseCase/Query/{operationName}/Exception/{entityName}NotFoundException.php');
        $this->registerTemplate('query-serializer-xml', 'query/serializer.xml.twig', '{basePath}/UseCase/Query/{operationName}/ReadModel/serializer/serializer.xml');
        $this->registerTemplate('query-readme', 'query/readme.md.twig', '{basePath}/UseCase/Query/{operationName}/README.md');
        $this->registerTemplate('jms-serializer-config', 'query/jms_serializer.yaml.twig', '{basePath}/UseCase/Query/{operationName}/Resources/config/jms_serializer.yaml');
        $this->registerTemplate('query-infrastructure-query', 'query/infrastructure_query.php.twig', '{basePath}/UseCase/Query/{operationName}/Infrastructure/Query/{operationName}.php');
        $this->registerTemplate('services-config', 'misc/services.yaml.twig', '{basePath}/UseCase/{cqrsType}/{operationName}/Resources/config/services.yaml');

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

        // Register test templates
        $this->registerTemplate('query-unit-test', 'tests/unit_test.php.twig', '{basePath}/../tests/Unit/UseCase/Query/{operationName}/QueryHandlerTest.php');
        $this->registerTemplate('command-unit-test', 'tests/unit_test.php.twig', '{basePath}/../tests/Unit/UseCase/Command/{operationName}/CommandHandlerTest.php');
        $this->registerTemplate('controller-functional-test', 'tests/functional_test.php.twig', '{basePath}/../tests/Functional/Controller/{operationName}ControllerTest.php');
    }

    private function shouldGenerateFile(string $templateName, BoilerplateConfiguration $configuration): bool
    {
        $variables = $configuration->getTemplateVariables();
        $method = $variables['method'] ?? '';

        // Filter templates based on HTTP method
        $queryTemplates = ['query', 'query-handler', 'criteria', 'read-model', 'query-repository-interface', 'query-repository', 'query-exception', 'query-readme', 'jms-serializer-config', 'query-unit-test'];
        $commandTemplates = ['command', 'command-handler', 'request-data', 'request-resolver', 'command-dto', 'command-repository-interface', 'command-repository', 'command-readme', 'command-unit-test'];

        if (strtoupper($method) === 'GET' && in_array($templateName, $commandTemplates)) {
            return false;
        }

        if (strtoupper($method) !== 'GET' && in_array($templateName, $queryTemplates)) {
            return false;
        }

        // Skip criteria template if there are no query parameters
        if ($templateName === 'criteria'
            && (!isset($variables['parameters'])
             || empty($variables['parameters'])
             || empty(array_filter($variables['parameters'], fn($param) => $param['in'] === 'query')))) {
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

            // Test templates
            if (in_array($templateName, ['query-unit-test', 'command-unit-test', 'controller-functional-test'])
                && isset($configuration->generators['tests'])
                && $configuration->generators['tests'] === false) {
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
