<?php

declare(strict_types=1);

namespace Kununu\CodeGenerator\Application\Command;

use Exception;
use Kununu\CodeGenerator\Application\Service\ConfigurationLoader;
use Kununu\CodeGenerator\Application\Service\OpenApiParser;
use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;
use Kununu\CodeGenerator\Domain\Service\CodeGeneratorInterface;
use Kununu\CodeGenerator\Infrastructure\Generator\TwigTemplateGenerator;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:generate:boilerplate',
    description: 'Generate boilerplate code based on OpenAPI specification',
)]
final class GenerateBoilerplateCommand extends Command
{
    private SymfonyStyle $io;
    private CodeGeneratorInterface $codeGenerator;
    private OpenApiParser $openApiParser;
    private ConfigurationLoader $configLoader;

    public function __construct()
    {
        parent::__construct();

        $filesystem = new Filesystem();
        $this->configLoader = new ConfigurationLoader($filesystem);
        $this->openApiParser = new OpenApiParser();
        $this->codeGenerator = new TwigTemplateGenerator($filesystem);
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'openapi-file',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Path to OpenAPI specification file (YAML or JSON). Can be relative or absolute.',
                'tests/_data/OpenApi/openapi.yaml'
            )
            ->addOption(
                'operation-id',
                'i',
                InputOption::VALUE_OPTIONAL,
                'OperationId from OpenAPI specification to use for generation',
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Path to configuration file',
                '.code-generator.yaml'
            )
            ->addOption(
                'non-interactive',
                null,
                InputOption::VALUE_NONE,
                'Run in non-interactive mode (requires all options to be provided)'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force overwrite existing files without confirmation'
            )
            ->addOption(
                'quiet',
                'q',
                InputOption::VALUE_NONE,
                'Suppress all output except errors'
            )
            ->addOption(
                'no-color',
                null,
                InputOption::VALUE_NONE,
                'Disable colored output'
            )
            ->addOption(
                'skip-existing',
                's',
                InputOption::VALUE_NONE,
                'Skip all existing files without confirmation'
            )
            ->addOption(
                'manual',
                'm',
                InputOption::VALUE_NONE,
                'Skip OpenAPI parsing and provide operation details manually'
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        if ($input->getOption('no-color')) {
            $output->setDecorated(false);
        }

        if ($input->getOption('quiet')) {
            $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Code Generator');

        try {
            // Load configuration file
            $configPath = $input->getOption('config');
            $config = $this->getConfigLoader()->loadConfig($configPath);

            // Collect user inputs into a DTO
            $configuration = $this->collectConfiguration($input, $config);

            // Check if manual mode is enabled
            $manualMode = $input->getOption('manual');

            // If in interactive mode and manual mode is not explicitly set, ask the user
            if (!$input->getOption('non-interactive') && !$manualMode && $configuration->openApiFilePath === null) {
                $manualMode = $this->io->confirm('Would you like to provide operation details manually instead of using OpenAPI?', false);
            }

            if ($manualMode) {
                // Collect operation details manually
                $this->collectManualOperationDetails($configuration);
            } else {
                // Parse OpenAPI specification if required
                if ($configuration->openApiFilePath !== null) {
                    $this->getOpenApiParser()->parseFile($configuration->openApiFilePath);

                    if ($configuration->operationId !== null) {
                        $operationDetails = $this->getOpenApiParser()->getOperationById($configuration->operationId);
                        $configuration->setOperationDetails($operationDetails);
                    }
                }
            }

            // Get list of files that would be generated
            $filesToGenerate = $this->getCodeGenerator()->getFilesToGenerate($configuration);

            if (empty($filesToGenerate)) {
                $this->io->warning('No files will be generated with the current configuration.');

                return Command::SUCCESS;
            }

            // Check for existing files before any generation
            $existingFiles = [];
            foreach ($filesToGenerate as $file) {
                if ($file['exists']) {
                    $existingFiles[] = $file['path'];
                }
            }

            // Store existing files in configuration
            $configuration->existingFiles = $existingFiles;

            // Preview files to be generated and ask for confirmation
            if (!$input->getOption('non-interactive') && !$input->getOption('quiet')) {
                $this->io->section('Files to be generated:');
                foreach ($filesToGenerate as $file) {
                    $status = $file['exists'] ? '<comment>(exists)</comment>' : '<info>(new)</info>';
                    $this->io->writeln(sprintf(' - %s %s (using %s)', $status, $file['path'], $file['template_path']));
                }

                if (!$this->io->confirm('Do you want to proceed with generating these files?', true)) {
                    $this->io->warning('Code generation canceled by user.');

                    return Command::SUCCESS;
                }
            }

            // Check for existing files and handle overwrite logic
            if (!$configuration->force && !empty($existingFiles) && !$configuration->skipExisting) {
                $this->io->section('The following files already exist:');

                foreach ($existingFiles as $existingFile) {
                    // Ask for confirmation for each existing file
                    if (!$this->io->confirm(sprintf('File <info>%s</info> exists. Overwrite? [Y/n]', $existingFile), true)) {
                        // If user doesn't want to overwrite, add to skip list
                        $configuration->addSkipFile($existingFile);
                        $this->io->writeln(sprintf(' - <comment>Skipping</comment> %s', $existingFile));
                    } else {
                        $this->io->writeln(sprintf(' - <info>Will overwrite</info> %s', $existingFile));
                    }
                }
            } elseif ($configuration->skipExisting && !empty($existingFiles)) {
                // If skip-existing is enabled, add all existing files to the skip list
                $this->io->section('Skipping all existing files:');
                foreach ($existingFiles as $existingFile) {
                    $configuration->addSkipFile($existingFile);
                    $this->io->writeln(sprintf(' - <comment>Skipping</comment> %s', $existingFile));
                }
            }

            // Generate code using the template generator
            $generatedFiles = $this->getCodeGenerator()->generate($configuration);

            // Output generation summary
            if (!$input->getOption('quiet')) {
                $this->io->success(sprintf('Generated %d files successfully', count($generatedFiles)));
                foreach ($generatedFiles as $file) {
                    $this->io->writeln(sprintf(' - <info>%s</info>', $file));
                }

                // Show skipped files if any
                if (!empty($configuration->skipFiles)) {
                    $this->io->section('Skipped files:');
                    foreach ($configuration->skipFiles as $file) {
                        $this->io->writeln(sprintf(' - <comment>%s</comment>', $file));
                    }
                }

                // Add route update information if we have operation details
                if (isset($configuration->operationDetails)
                    && isset($configuration->operationDetails['path'])
                    && isset($configuration->operationDetails['method'])) {
                    // Determine controller name from generated files or configuration
                    $controllerName = null;
                    foreach ($filesToGenerate as $file) {
                        if (str_contains($file['path'], 'Controller')) {
                            $controllerPath = $file['path'];
                            // Extract namespace and class name from path
                            $relativePath = str_replace($configuration->basePath . '/', '', $controllerPath);
                            $relativePath = str_replace('.php', '', $relativePath);
                            $controllerName = $configuration->namespace . '\\' . str_replace('/', '\\', $relativePath);
                            break;
                        }
                    }

                    if ($controllerName) {
                        $this->io->section('Route Information:');
                        $this->io->writeln('<comment>Please update your routes file with the following details:</comment>');
                        $this->io->writeln(sprintf('Path: <info>%s</info>', $configuration->operationDetails['path']));
                        $this->io->writeln(sprintf('Controller: <info>%s</info>', $controllerName));
                        $this->io->writeln(sprintf('Methods: <info>[%s]</info>', $configuration->operationDetails['method']));
                    }
                }
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->io->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function collectConfiguration(InputInterface $input, array $config): BoilerplateConfiguration
    {
        $isInteractive = !$input->getOption('non-interactive');
        $configuration = new BoilerplateConfiguration();

        // Set defaults from config
        $configuration->setBasePath($config['base_path'] ?? 'src');
        $configuration->setNamespace($config['namespace'] ?? 'App');

        // Set path patterns from config if they exist
        if (isset($config['path_patterns']) && is_array($config['path_patterns'])) {
            $configuration->setPathPatterns($config['path_patterns']);
        }

        // Set generators configuration from config if they exist
        if (isset($config['generators']) && is_array($config['generators'])) {
            $configuration->setGenerators($config['generators']);
        }

        // Set force option - command line takes precedence over config file
        $forceFromCommandLine = $input->getOption('force');
        $forceFromConfig = $config['force'] ?? false;
        $configuration->setForce($forceFromCommandLine || $forceFromConfig);

        // Set skip-existing option - command line takes precedence over config file
        $skipExistingFromCommandLine = $input->getOption('skip-existing');
        $skipExistingFromConfig = $config['skip_existing'] ?? false;
        $configuration->setSkipExisting($skipExistingFromCommandLine || $skipExistingFromConfig);

        // If manual mode is enabled, we don't need to ask for OpenAPI file
        if (!$input->getOption('manual')) {
            // Handle OpenAPI file path
            $openApiFilePath = $input->getOption('openapi-file');
            if ($openApiFilePath === null && $isInteractive) {
                $openApiFilePath = $this->io->ask(
                    'Path to OpenAPI specification file',
                    $config['default_openapi_path'] ?? null
                );
            }

            // Resolve the path to absolute if it's not null
            if ($openApiFilePath !== null) {
                $openApiFilePath = $this->resolveFilePath($openApiFilePath);
            }

            $configuration->setOpenApiFilePath($openApiFilePath);

            // Handle Operation ID
            $operationId = $input->getOption('operation-id');
            if ($operationId === null && $isInteractive && $openApiFilePath !== null) {
                // If we have the OpenAPI file, we can parse it and show available operations
                $this->getOpenApiParser()->parseFile($openApiFilePath);
                $operations = $this->getOpenApiParser()->listOperations();

                if (empty($operations)) {
                    $this->io->warning('No operations found in the OpenAPI specification');
                } else {
                    $this->io->writeln('Available operations:');
                    foreach ($operations as $index => $op) {
                        $this->io->writeln(sprintf(' %d. <info>%s</info> - %s', $index + 1, $op['id'], $op['summary']));
                    }

                    $selection = $this->io->ask('Select operation by number or provide operationId');
                    if (is_numeric($selection) && isset($operations[(int) $selection - 1])) {
                        $operationId = $operations[(int) $selection - 1]['id'];
                    } else {
                        $operationId = $selection;
                    }
                }
            }
            $configuration->setOperationId($operationId);
        }

        return $configuration;
    }

    /**
     * Collect operation details manually from user input
     */
    private function collectManualOperationDetails(BoilerplateConfiguration $configuration): void
    {
        $this->io->section('Manual Operation Details');
        $this->io->writeln('Please provide the following details for code generation:');

        // Operation ID and basic information
        $operationId = $this->io->ask('Operation ID (e.g., getUserById)', null, function($value) {
            if (empty($value)) {
                throw new RuntimeException('Operation ID cannot be empty');
            }

            return $value;
        });
        $summary = $this->io->ask('Summary (short description)', '');
        $description = $this->io->ask('Description (longer explanation)', '');

        // HTTP method
        $method = $this->io->choice('HTTP Method', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], 'GET');

        // URL path
        $path = $this->io->ask('URL Path (e.g., /users/{userId})', '/', function($value) {
            if (empty($value)) {
                throw new RuntimeException('URL Path cannot be empty');
            }

            return $value;
        });

        // Parameters
        $parameters = [];
        if ($this->io->confirm('Does this operation have path or query parameters?', false)) {
            $this->io->writeln('Enter parameters (leave name empty to finish):');

            while (true) {
                $paramName = $this->io->ask('Parameter name', null);
                if (empty($paramName)) {
                    break;
                }

                $paramIn = $this->io->choice('Parameter location', ['path', 'query', 'header'], 'path');
                $paramRequired = $this->io->confirm('Is this parameter required?', true);
                $paramDesc = $this->io->ask('Parameter description', '');
                $paramType = $this->io->choice('Parameter type', ['string', 'integer', 'number', 'boolean', 'array'], 'string');

                $parameters[] = [
                    'name'        => $paramName,
                    'in'          => $paramIn,
                    'required'    => $paramRequired,
                    'description' => $paramDesc,
                    'schema'      => [
                        'type' => $paramType,
                    ],
                ];

                $this->io->writeln(sprintf('Added parameter: <info>%s</info> (%s)', $paramName, $paramType));
            }
        }

        // Request body
        $requestBody = null;
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && $this->io->confirm('Does this operation have a request body?', true)) {
            $requestBody = [
                'description' => $this->io->ask('Request body description', ''),
                'required'    => $this->io->confirm('Is the request body required?', true),
                'content'     => [],
            ];

            $contentType = $this->io->choice('Content type', ['application/json', 'application/xml', 'multipart/form-data'], 'application/json');

            $properties = [];
            $this->io->writeln('Enter request body properties (leave name empty to finish):');

            while (true) {
                $propName = $this->io->ask('Property name', null);
                if (empty($propName)) {
                    break;
                }

                $propType = $this->io->choice('Property type', ['string', 'integer', 'number', 'boolean', 'array', 'object'], 'string');
                $propDesc = $this->io->ask('Property description', '');
                $propRequired = $this->io->confirm('Is this property required?', true);

                $properties[$propName] = [
                    'type'        => $propType,
                    'description' => $propDesc,
                ];

                // Handle array items
                if ($propType === 'array') {
                    $itemType = $this->io->choice('Array items type', ['string', 'integer', 'number', 'boolean', 'object'], 'string');
                    $properties[$propName]['items'] = [
                        'type' => $itemType,
                    ];
                }

                $this->io->writeln(sprintf('Added property: <info>%s</info> (%s)', $propName, $propType));
            }

            $requestBody['content'][$contentType] = [
                'schema' => [
                    'type'       => 'object',
                    'properties' => $properties,
                ],
            ];
        }

        // Responses
        $responses = [];
        $this->io->writeln('Enter responses (at least one is required):');

        while (empty($responses) || $this->io->confirm('Add another response?', false)) {
            $statusCode = $this->io->ask('Status code', '200', function($value) {
                if (!preg_match('/^[1-5][0-9][0-9]$/', $value)) {
                    throw new RuntimeException('Invalid status code. Must be a 3-digit HTTP status code.');
                }

                return $value;
            });

            $responseDesc = $this->io->ask('Response description', 'Successful operation');

            $response = [
                'description' => $responseDesc,
                'content'     => [],
            ];

            if ($this->io->confirm('Does this response have a body?', true)) {
                $contentType = $this->io->choice('Content type', ['application/json', 'application/xml', 'text/plain'], 'application/json');

                if (in_array($contentType, ['application/json', 'application/xml'])) {
                    $responseType = $this->io->choice('Response schema type', ['object', 'array', 'string', 'integer', 'number', 'boolean'], 'object');

                    $schema = [
                        'type' => $responseType,
                    ];

                    if ($responseType === 'object') {
                        $properties = [];
                        $this->io->writeln('Enter response properties (leave name empty to finish):');

                        while (true) {
                            $propName = $this->io->ask('Property name', null);
                            if (empty($propName)) {
                                break;
                            }

                            $propType = $this->io->choice('Property type', ['string', 'integer', 'number', 'boolean', 'array', 'object'], 'string');
                            $propDesc = $this->io->ask('Property description', '');

                            $properties[$propName] = [
                                'type'        => $propType,
                                'description' => $propDesc,
                            ];

                            // Handle array items
                            if ($propType === 'array') {
                                $itemType = $this->io->choice('Array items type', ['string', 'integer', 'number', 'boolean', 'object'], 'string');
                                $properties[$propName]['items'] = [
                                    'type' => $itemType,
                                ];
                            }

                            $this->io->writeln(sprintf('Added property: <info>%s</info> (%s)', $propName, $propType));
                        }

                        $schema['properties'] = $properties;
                    } elseif ($responseType === 'array') {
                        $itemType = $this->io->choice('Array items type', ['string', 'integer', 'number', 'boolean', 'object'], 'object');
                        $schema['items'] = [
                            'type' => $itemType,
                        ];

                        if ($itemType === 'object') {
                            $properties = [];
                            $this->io->writeln('Enter item properties (leave name empty to finish):');

                            while (true) {
                                $propName = $this->io->ask('Property name', null);
                                if (empty($propName)) {
                                    break;
                                }

                                $propType = $this->io->choice('Property type', ['string', 'integer', 'number', 'boolean', 'array'], 'string');
                                $propDesc = $this->io->ask('Property description', '');

                                $properties[$propName] = [
                                    'type'        => $propType,
                                    'description' => $propDesc,
                                ];

                                $this->io->writeln(sprintf('Added property: <info>%s</info> (%s)', $propName, $propType));
                            }

                            $schema['items']['properties'] = $properties;
                        }
                    }

                    $response['content'][$contentType] = [
                        'schema' => $schema,
                    ];
                } else {
                    // For text/plain and other simple types
                    $response['content'][$contentType] = [
                        'schema' => [
                            'type' => 'string',
                        ],
                    ];
                }
            }

            $responses[$statusCode] = $response;
            $this->io->writeln(sprintf('Added response: <info>%s</info> - %s', $statusCode, $responseDesc));
        }

        // Create operation details structure
        $operationDetails = [
            'id'          => $operationId,
            'path'        => $path,
            'method'      => $method,
            'summary'     => $summary,
            'description' => $description,
            'parameters'  => $parameters,
            'responses'   => $responses,
        ];

        // Add request body if defined
        if ($requestBody !== null) {
            $operationDetails['requestBody'] = $requestBody;
        }

        // Set operation details in configuration
        $configuration->setOperationDetails($operationDetails);
        $this->io->success('Operation details collected successfully');
    }

    /**
     * Get the configuration loader
     *
     * Protected for testing purposes
     */
    protected function getConfigLoader(): ConfigurationLoader
    {
        return $this->configLoader;
    }

    /**
     * Get the OpenAPI parser
     *
     * Protected for testing purposes
     */
    protected function getOpenApiParser(): OpenApiParser
    {
        return $this->openApiParser;
    }

    /**
     * Get the code generator
     *
     * Protected for testing purposes
     */
    protected function getCodeGenerator(): CodeGeneratorInterface
    {
        return $this->codeGenerator;
    }

    private function isAbsolutePath(string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        // On Windows check for drive letter or UNC path
        if (DIRECTORY_SEPARATOR === '\\') {
            return $path[0] === '\\' || (isset($path[1]) && $path[1] === ':');
        }

        // On Unix-like systems check for leading slash
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
