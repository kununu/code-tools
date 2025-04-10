<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Application\Command;

use cebe\openapi\exceptions\IOException;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\json\InvalidJsonPointerSyntaxException;
use Exception;
use Kununu\CodeGenerator\Application\Service\ConfigurationBuilder;
use Kununu\CodeGenerator\Application\Service\ConfigurationLoader;
use Kununu\CodeGenerator\Application\Service\FileGenerationHandler;
use Kununu\CodeGenerator\Application\Service\ManualOperationCollector;
use Kununu\CodeGenerator\Application\Service\OpenApiParser;
use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;
use Kununu\CodeGenerator\Domain\Service\CodeGeneratorInterface;
use Kununu\CodeGenerator\Factory\TwigTemplateGeneratorFactory;
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
    private ConfigurationLoader $configLoader;
    private OpenApiParser $openApiParser;
    private CodeGeneratorInterface $codeGenerator;
    private ConfigurationBuilder $configBuilder;
    private FileGenerationHandler $fileGenerationHandler;
    private ManualOperationCollector $manualOperationCollector;

    public function __construct(
        ConfigurationLoader $configLoader,
        OpenApiParser $openApiParser,
        CodeGeneratorInterface $codeGenerator,
    ) {
        parent::__construct();

        $this->configLoader = $configLoader;
        $this->openApiParser = $openApiParser;
        $this->codeGenerator = $codeGenerator;
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'openapi-file',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Path to OpenAPI specification file (YAML or JSON). Can be relative or absolute.',
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
            )
            ->addOption(
                'template-dir',
                't',
                InputOption::VALUE_OPTIONAL,
                'Path to custom template directory'
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

        $this->configBuilder = new ConfigurationBuilder($this->io, $this->configLoader, $this->openApiParser);
        $this->fileGenerationHandler = new FileGenerationHandler($this->io, $this->codeGenerator);
        $this->manualOperationCollector = new ManualOperationCollector($this->io);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Code Generator');

        try {
            $configuration = $this->prepareConfiguration($input);

            // Re-initialize the code generator with the custom template directory if specified
            if ($configuration->templateDir !== null) {
                $filesystem = new Filesystem();
                $this->codeGenerator = TwigTemplateGeneratorFactory::create($filesystem, $configuration->templateDir);
                $this->fileGenerationHandler = new FileGenerationHandler($this->io, $this->codeGenerator);
            }

            $this->collectOperationDetails($input, $configuration);

            return $this->generateFiles($input, $configuration);
        } catch (Exception $e) {
            $this->io->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function prepareConfiguration(InputInterface $input): BoilerplateConfiguration
    {
        $configPath = $input->getOption('config');

        return $this->configBuilder->buildConfiguration($input, $configPath);
    }

    /**
     * @throws IOException|TypeErrorException|UnresolvableReferenceException|InvalidJsonPointerSyntaxException
     */
    private function collectOperationDetails(InputInterface $input, BoilerplateConfiguration $configuration): void
    {
        $manualMode = $this->determineManualMode($input, $configuration);

        if ($manualMode) {
            $operationDetails = $this->manualOperationCollector->collectOperationDetails();
            $configuration->setOperationDetails($operationDetails);
        } else {
            $this->parseOpenApiSpecification($configuration);
        }
    }

    private function determineManualMode(InputInterface $input, BoilerplateConfiguration $configuration): bool
    {
        $manualMode = $input->getOption('manual');

        if (!$input->getOption('non-interactive') && !$manualMode && $configuration->openApiFilePath === null) {
            $manualMode = $this->io->confirm(
                'Would you like to provide operation details manually instead of using OpenAPI?',
                false
            );
        }

        return $manualMode;
    }

    /**
     * @throws IOException|TypeErrorException|UnresolvableReferenceException|InvalidJsonPointerSyntaxException
     */
    private function parseOpenApiSpecification(BoilerplateConfiguration $configuration): void
    {
        if ($configuration->openApiFilePath === null) {
            return;
        }

        $this->openApiParser->parseFile($configuration->openApiFilePath);

        if ($configuration->operationId !== null) {
            $operationDetails = $this->openApiParser->getOperationById($configuration->operationId);
            $configuration->setOperationDetails($operationDetails);
        }
    }

    private function generateFiles(InputInterface $input, BoilerplateConfiguration $configuration): int
    {
        $skipPreview = $this->shouldSkipPreview($input);
        $filesToGenerate = $this->fileGenerationHandler->processFilesToGenerate($configuration, $skipPreview);

        if (empty($filesToGenerate)) {
            return Command::SUCCESS;
        }

        $this->fileGenerationHandler->generateFiles($configuration, $filesToGenerate, $input->getOption('quiet'));

        return Command::SUCCESS;
    }

    private function shouldSkipPreview(InputInterface $input): bool
    {
        return $input->getOption('non-interactive') || $input->getOption('quiet');
    }
}
