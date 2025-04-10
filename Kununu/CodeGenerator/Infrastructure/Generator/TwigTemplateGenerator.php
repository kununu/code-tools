<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Infrastructure\Generator;

use Exception;
use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;
use Kununu\CodeGenerator\Domain\DTO\TemplateDTO;
use Kununu\CodeGenerator\Domain\DTO\TemplatesDTO;
use Kununu\CodeGenerator\Domain\Service\CodeGeneratorInterface;
use Kununu\CodeGenerator\Domain\Service\FileSystem\FileSystemHandlerInterface;
use Kununu\CodeGenerator\Domain\Service\Template\StringTransformerInterface;
use Kununu\CodeGenerator\Domain\Service\Template\TemplatePathResolverInterface;
use Kununu\CodeGenerator\Domain\Service\Template\TemplateRegistryInterface;
use Kununu\CodeGenerator\Domain\Service\Template\TemplateRenderingServiceInterface;
use Kununu\CodeGenerator\Infrastructure\Template\DefaultTemplateRegistry;
use Kununu\CodeGenerator\Infrastructure\Template\StringTransformer;
use Kununu\CodeGenerator\Infrastructure\Template\TemplatePathResolver;
use Kununu\CodeGenerator\Infrastructure\Template\TwigTemplateRenderer;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final readonly class TwigTemplateGenerator implements CodeGeneratorInterface
{
    public function __construct(
        private FileSystemHandlerInterface $fileSystem,
        private TemplateRenderingServiceInterface $renderer,
        private TemplatePathResolverInterface $templatePathResolver,
        private TemplateRegistryInterface $templateRegistry,
        private StringTransformerInterface $stringTransformer,
    ) {
        $this->registerDefaultTemplates();
    }

    public static function createDefault(
        FileSystemHandlerInterface $fileSystem,
        ?string $customTemplateDir = null,
    ): self {
        $defaultTemplateDir = dirname(__DIR__, 3) . '/CodeGenerator/Templates';

        // Set up Twig with multiple template directories
        $loader = new FilesystemLoader([]);

        // Add the default template directory
        $loader->addPath($defaultTemplateDir, 'default');

        // Add the custom template directory if provided
        if ($customTemplateDir !== null && $fileSystem->exists($customTemplateDir)) {
            $loader->addPath($customTemplateDir, 'custom');
        }

        $twig = new Environment($loader, [
            'cache'            => false,
            'debug'            => true,
            'strict_variables' => true,
            'autoescape'       => false,
        ]);

        // Create services
        $templatePathResolver = new TemplatePathResolver(
            $fileSystem,
            $customTemplateDir
        );

        $templateRegistry = new DefaultTemplateRegistry(
            $templatePathResolver
        );

        $renderer = new TwigTemplateRenderer($twig);

        $stringTransformer = new StringTransformer();

        // Register filters
        $renderer->registerFilters([
            'properCapitalize' => [$stringTransformer, 'operationIdToClassName'],
            'snake_to_camel'   => [$stringTransformer, 'snakeToCamelCase'],
        ]);

        return new self(
            $fileSystem,
            $renderer,
            $templatePathResolver,
            $templateRegistry,
            $stringTransformer,
        );
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
            $variables['entity_name'] = $this->stringTransformer
                ->extractEntityNameFromOperationId($variables['operation_id']);
        }

        // Create TemplateDTO objects for each template
        $templatesDTO = $this->buildTemplateDTOs($configuration, $variables);

        // Add templates DTO to variables for each template
        foreach ($templatesDTO->getAllTemplates() as $templateDTO) {
            $templateVariables = $templateDTO->templateVariables;
            $templateVariables['templates'] = $templatesDTO;

            try {
                $content = $this->renderer->renderTemplate($templateDTO->template, $templateVariables);

                // Create output directory if it doesn't exist
                if ($templateDTO->outputPath !== null) {
                    $directory = dirname($templateDTO->outputPath);
                    if (!$this->fileSystem->exists($directory)) {
                        $this->fileSystem->createDirectory($directory);
                    }

                    // Write to file
                    $this->fileSystem->writeFile($templateDTO->outputPath, $content);
                    $generatedFiles[] = $templateDTO->outputPath;
                }
            } catch (Exception $e) {
                // If there's an error loading the template, log it and continue
                error_log(sprintf('Error loading template %s: %s', $templateDTO->template, $e->getMessage()));
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
            $variables['entity_name'] = $this->stringTransformer
                ->extractEntityNameFromOperationId($variables['operation_id']);
        }

        $templatesDTO = $this->buildTemplateDTOs($configuration, $variables);

        // Convert DTOs to array format for backward compatibility
        foreach ($templatesDTO->getAllTemplates() as $templateDTO) {
            $exists = false;

            // Check if outputPath is not null before checking if it exists
            if ($templateDTO->outputPath !== null) {
                $exists = $this->fileSystem->exists($templateDTO->outputPath);
            }

            $willBeSkipped = $exists && $configuration->skipExisting;
            $templateSource = '';

            // Make sure template path is not null before getting the source
            if ($templateDTO->path !== null) {
                $templateSource = $this->templatePathResolver->getTemplateSource($templateDTO->path);
            }

            $filesToGenerate[] = [
                'path'            => $templateDTO->outputPath,
                'exists'          => $exists,
                'will_be_skipped' => $willBeSkipped,
                'template'        => $templateDTO->path,
                'template_path'   => $templateDTO->template,
                'template_source' => $templateSource,
                'full_namespace'  => $templateDTO->namespace,
                'classname'       => $templateDTO->classname,
                'fqcn'            => $templateDTO->fqcn,
            ];
        }

        return $filesToGenerate;
    }

    private function extractNameFromPath(string $path, int $flag): string
    {
        if (empty($path)) {
            return '';
        }

        $result = pathinfo($path, $flag);

        return is_string($result) ? $result : '';
    }

    private function buildTemplateDTOs(BoilerplateConfiguration $configuration, array $variables): TemplatesDTO
    {
        $templateDTOs = [];
        $configArray = [
            'generators'   => $configuration->generators,
            'pathPatterns' => $configuration->pathPatterns,
            'skipFiles'    => $configuration->skipFiles ?? [],
        ];

        $templates = $this->templateRegistry->getAllTemplates();

        foreach ($templates as $templateName => $template) {
            // Determine if we should generate this file based on configuration
            if (!$this->templateRegistry->shouldGenerateTemplate($templateName, $configArray, $variables)) {
                continue;
            }

            // Get output pattern - prefer custom pattern from config if available
            $outputPattern = $template['outputPattern'];
            if (!empty($configuration->pathPatterns) && isset($configuration->pathPatterns[$templateName])) {
                $outputPattern = $configuration->pathPatterns[$templateName];
            }

            // Generate output path from pattern
            $outputPath = $this->stringTransformer->generateOutputPath(
                $outputPattern,
                $configuration->basePath,
                $variables
            );

            // Skip this file if it's in the skipFiles list
            if (isset($configuration->skipFiles) && in_array($outputPath, $configuration->skipFiles)) {
                continue;
            }

            // Create a copy of template variables for this specific template
            $templateVariables = $variables;

            // Set the dynamic namespace based on the output path
            $templateVariables['full_namespace'] = $this->stringTransformer->getDynamicNamespace(
                $outputPath,
                $configuration->basePath,
                $configuration->namespace
            );

            // Get the classname from the output path
            $classname = $this->extractNameFromPath($outputPath, PATHINFO_FILENAME);
            $templateVariables['classname'] = $classname;
            $filename = $this->extractNameFromPath($outputPath, PATHINFO_BASENAME);
            $templateVariables['filename'] = $filename;

            // Build the FQCN by combining namespace and classname
            $fqcn = $templateVariables['full_namespace'] . '\\' . $classname;

            // Add templates variable to access other templates
            $templateDTOs[] = new TemplateDTO(
                $templateName,
                $template['path'],
                $templateVariables,
                $template['original_path'],
                $outputPath,
                $templateVariables['full_namespace'],
                $classname,
                $fqcn,
                $filename,
                dirname($outputPath)
            );
        }

        return new TemplatesDTO($templateDTOs);
    }

    // phpcs:disable Kununu.Files.LineLength
    private function registerDefaultTemplates(): void
    {
        // Register all templates - they'll be filtered at generation time based on HTTP method
        // Shared templates
        $this->templateRegistry->registerTemplate('query-infrastructure-query', 'shared/infrastructure_query.php.twig', '{basePath}/UseCase/{cqrsType}/{operationName}/Infrastructure/Query/{operationName}.php');

        // Controller template
        $this->templateRegistry->registerTemplate('controller', 'controller.php.twig', '{basePath}/Controller/{operationName}Controller.php');

        // Query related templates
        $this->templateRegistry->registerTemplate('query', 'query/query.php.twig', '{basePath}/UseCase/Query/{operationName}/Query.php');
        $this->templateRegistry->registerTemplate('query-handler', 'query/handler.php.twig', '{basePath}/UseCase/Query/{operationName}/QueryHandler.php');
        $this->templateRegistry->registerTemplate('criteria', 'query/criteria.php.twig', '{basePath}/UseCase/Query/{operationName}/Criteria/Criteria.php');
        $this->templateRegistry->registerTemplate('read-model', 'query/read_model.php.twig', '{basePath}/UseCase/Query/{operationName}/ReadModel/{entityName}.php');
        $this->templateRegistry->registerTemplate('query-repository-interface', 'repository/interface.php.twig', '{basePath}/UseCase/Query/{operationName}/RepositoryInterface.php');
        $this->templateRegistry->registerTemplate('query-exception', 'query/exception.php.twig', '{basePath}/UseCase/Query/{operationName}/Exception/{entityName}NotFoundException.php');
        $this->templateRegistry->registerTemplate('query-serializer-xml', 'query/serializer.xml.twig', '{basePath}/UseCase/Query/{operationName}/ReadModel/serializer/serializer.xml');
        $this->templateRegistry->registerTemplate('query-readme', 'query/readme.md.twig', '{basePath}/UseCase/Query/{operationName}/README.md');
        $this->templateRegistry->registerTemplate('jms-serializer-config', 'query/jms_serializer.yaml.twig', '{basePath}/UseCase/Query/{operationName}/Resources/config/jms_serializer.yaml');
        $this->templateRegistry->registerTemplate('services-config', 'misc/services.yaml.twig', '{basePath}/UseCase/{cqrsType}/{operationName}/Resources/config/services.yaml');

        // Command related templates
        $this->templateRegistry->registerTemplate('command', 'command/command.php.twig', '{basePath}/UseCase/Command/{operationName}/Command.php');
        $this->templateRegistry->registerTemplate('command-handler', 'command/handler.php.twig', '{basePath}/UseCase/Command/{operationName}/CommandHandler.php');
        $this->templateRegistry->registerTemplate('request-data', 'request/request_data.php.twig', '{basePath}/Request/DTO/{operationName}RequestData.php');
        $this->templateRegistry->registerTemplate('request-resolver', 'request/resolver.php.twig', '{basePath}/Request/Resolver/{operationName}Resolver.php');
        $this->templateRegistry->registerTemplate('command-dto', 'command/dto.php.twig', '{basePath}/UseCase/Command/{operationName}/DTO/{entityName}.php');
        $this->templateRegistry->registerTemplate('command-repository-interface', 'repository/interface.php.twig', '{basePath}/UseCase/Command/{operationName}/RepositoryInterface.php');
        $this->templateRegistry->registerTemplate('command-readme', 'command/readme.md.twig', '{basePath}/UseCase/Command/{operationName}/README.md');

        // Register repository implementation templates - they'll be filtered based on HTTP method
        $this->templateRegistry->registerTemplate('query-repository', 'repository/implementation.php.twig', '{basePath}/UseCase/Query/{operationName}/Infrastructure/DoctrineRepository.php');
        $this->templateRegistry->registerTemplate('command-repository', 'repository/implementation.php.twig', '{basePath}/UseCase/Command/{operationName}/Infrastructure/DoctrineRepository.php');

        // Register test templates
        $this->templateRegistry->registerTemplate('query-unit-test', 'tests/unit_test.php.twig', '{basePath}/../tests/Unit/UseCase/Query/{operationName}/QueryHandlerTest.php');
        $this->templateRegistry->registerTemplate('command-unit-test', 'tests/unit_test.php.twig', '{basePath}/../tests/Unit/UseCase/Command/{operationName}/CommandHandlerTest.php');
        $this->templateRegistry->registerTemplate('controller-functional-test', 'tests/functional_test.php.twig', '{basePath}/../tests/Functional/Controller/{operationName}ControllerTest.php');
    }
    // phpcs:enable Kununu.Files.LineLength
}
