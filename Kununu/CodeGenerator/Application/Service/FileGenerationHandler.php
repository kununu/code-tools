<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Application\Service;

use Exception;
use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;
use Kununu\CodeGenerator\Domain\Exception\FileGenerationException;
use Kununu\CodeGenerator\Domain\Service\CodeGeneratorInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Handles the generation of files based on templates and user configuration
 *
 * This service is responsible for:
 * - Processing the list of files to be generated
 * - Handling existing files (skip or overwrite)
 * - Delegating the actual file generation to a code generator
 * - Displaying summaries and information about the generation process
 */
final class FileGenerationHandler
{
    private SymfonyStyle $io;
    private CodeGeneratorInterface $codeGenerator;

    public function __construct(SymfonyStyle $io, CodeGeneratorInterface $codeGenerator)
    {
        $this->io = $io;
        $this->codeGenerator = $codeGenerator;
    }

    public function processFilesToGenerate(BoilerplateConfiguration $configuration, bool $skipPreview): array
    {
        $filesToGenerate = $this->codeGenerator->getFilesToGenerate($configuration);

        if (empty($filesToGenerate)) {
            $this->io->warning('No files will be generated with the current configuration.');

            return [];
        }

        $existingFiles = $this->findExistingFiles($filesToGenerate);
        $configuration->existingFiles = $existingFiles;

        if (!$skipPreview) {
            $this->previewFilesToGenerate($filesToGenerate);

            if (!$this->io->confirm('Do you want to proceed with generating these files?')) {
                $this->io->warning('Code generation canceled by user.');

                return [];
            }
        }

        $this->handleExistingFiles($configuration, $existingFiles);

        return $filesToGenerate;
    }

    public function generateFiles(BoilerplateConfiguration $configuration, array $filesToGenerate, bool $quiet): array
    {
        try {
            // Ensure operation details are properly formatted
            $this->ensureValidOperationDetailsFormat($configuration);

            $generatedFiles = $this->codeGenerator->generate($configuration);

            if (!$quiet) {
                $this->displayGenerationSummary($configuration, $generatedFiles);
                $this->displayRouteInformation($configuration, $filesToGenerate);
            }

            return $generatedFiles;
        } catch (Exception $e) {
            throw new FileGenerationException(
                sprintf('Error generating files: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    private function findExistingFiles(array $filesToGenerate): array
    {
        $existingFiles = [];
        foreach ($filesToGenerate as $file) {
            if ($file['exists']) {
                $existingFiles[] = $file['path'];
            }
        }

        return $existingFiles;
    }

    private function previewFilesToGenerate(array $filesToGenerate): void
    {
        $this->io->section('Files to be generated:');

        $rows = [];
        foreach ($filesToGenerate as $file) {
            $existsStatus = 'No';
            if ($file['exists']) {
                $existsStatus = isset($file['will_be_skipped']) && $file['will_be_skipped']
                    ? 'Yes (will be skipped)'
                    : 'Yes (will be overwritten)';
            }

            $rows[] = [$file['path'], $existsStatus];
        }

        $this->io->table(['File', 'Exists'], $rows);

        // Show template source information if custom templates are being used
        if (method_exists($this->codeGenerator, 'getTemplateSource')
            && method_exists($this->codeGenerator, 'templateExistsInCustomDir')) {
            $this->io->section('Template sources:');

            $templateRows = [];
            $customTemplatesUsed = false;

            foreach ($filesToGenerate as $file) {
                if (isset($file['template'])) {
                    $templatePath = $file['template'];
                    $source = $file['template_source'] ?? 'default';

                    if ($source === 'custom') {
                        $customTemplatesUsed = true;
                    }

                    $templateRows[] = [$templatePath, $source];
                }
            }

            if (!empty($templateRows)) {
                $this->io->table(['Template', 'Source'], $templateRows);
                if ($customTemplatesUsed) {
                    $this->io->note(
                        'Custom templates are being used when available, fall back to default templates when necessary.'
                    );
                } else {
                    $this->io->note('Using default templates. No custom templates were found.');
                }
            }
        }
    }

    private function handleExistingFiles(BoilerplateConfiguration $configuration, array $existingFiles): void
    {
        if (empty($existingFiles)) {
            return;
        }

        if ($configuration->skipExisting) {
            $this->handleSkipAllExistingFiles($configuration, $existingFiles);
        } elseif (!$configuration->force) {
            $this->handleConfirmEachExistingFile($configuration, $existingFiles);
        }
    }

    private function handleSkipAllExistingFiles(BoilerplateConfiguration $configuration, array $existingFiles): void
    {
        $this->io->section('Skipping all existing files:');
        foreach ($existingFiles as $existingFile) {
            $configuration->addSkipFile($existingFile);
            $this->io->writeln(sprintf(' - <comment>Skipping</comment> %s', $existingFile));
        }
    }

    private function handleConfirmEachExistingFile(BoilerplateConfiguration $configuration, array $existingFiles): void
    {
        $this->io->section('The following files already exist:');

        foreach ($existingFiles as $existingFile) {
            if (!$this->io->confirm(
                sprintf('File <info>%s</info> exists. Overwrite? [Y/n]', $existingFile))
            ) {
                $configuration->addSkipFile($existingFile);
                $this->io->writeln(sprintf(' - <comment>Skipping</comment> %s', $existingFile));
            } else {
                $this->io->writeln(sprintf(' - <info>Will overwrite</info> %s', $existingFile));
            }
        }
    }

    private function displayGenerationSummary(BoilerplateConfiguration $configuration, array $generatedFiles): void
    {
        $this->io->success(sprintf('Generated %d files successfully', count($generatedFiles)));
        foreach ($generatedFiles as $file) {
            $this->io->writeln(sprintf(' - <info>%s</info>', $file));
        }

        if (!empty($configuration->skipFiles)) {
            $this->io->section('Skipped files:');
            foreach ($configuration->skipFiles as $file) {
                $this->io->writeln(sprintf(' - <comment>%s</comment>', $file));
            }
        }
    }

    private function displayRouteInformation(BoilerplateConfiguration $configuration, array $filesToGenerate): void
    {
        if (!isset($configuration->operationDetails)
            || !isset($configuration->operationDetails['path'])
            || !isset($configuration->operationDetails['method'])) {
            return;
        }

        $controllerName = $this->findControllerName($configuration, $filesToGenerate);
        if (!$controllerName) {
            return;
        }

        $this->io->section('Route Information:');
        $this->io->writeln('<comment>Please update your routes file with the following details:</comment>');
        $this->io->writeln(sprintf('Path: <info>%s</info>', $configuration->operationDetails['path']));
        $this->io->writeln(sprintf('Controller: <info>%s</info>', $controllerName));

        // Ensure the method is properly formatted as a string
        $method = is_array($configuration->operationDetails['method'])
            ? implode(', ', $configuration->operationDetails['method'])
            : (string) $configuration->operationDetails['method'];

        $this->io->writeln(sprintf('Methods: <info>[%s]</info>', $method));
    }

    private function findControllerName(BoilerplateConfiguration $configuration, array $filesToGenerate): ?string
    {
        foreach ($filesToGenerate as $file) {
            if (str_contains($file['path'], 'Controller')) {
                $controllerPath = $file['path'];
                $basePath = $configuration->basePath . '/';
                $relativePath = str_replace($basePath, '', $controllerPath);
                $relativePath = str_replace('.php', '', $relativePath);

                // Ensure relativePath is a string before exploding
                if (is_string($relativePath)) {
                    $namespaceParts = explode('/', $relativePath);
                    $namespaceString = implode('\\', $namespaceParts);

                    return $configuration->namespace . '\\' . $namespaceString;
                }

                // Fallback if relativePath is not a string
                return $configuration->namespace . '\\Controller';
            }
        }

        return null;
    }

    // phpcs:disable Kununu.Files.LineLength
    private function ensureValidOperationDetailsFormat(BoilerplateConfiguration $configuration): void
    {
        // Skip if no operation details are set
        if (!isset($configuration->operationDetails)) {
            return;
        }

        // Ensure request body schema has required field if it has properties
        if (isset($configuration->operationDetails['requestBody']['content'])) {
            foreach ($configuration->operationDetails['requestBody']['content'] as $contentType => $content) {
                if (isset($content['schema']['properties'])) {
                    // Ensure required field exists
                    if (!isset($content['schema']['required'])) {
                        $configuration->operationDetails['requestBody']['content'][$contentType]['schema']['required'] = [];
                    }

                    // Mark non-required properties as nullable
                    $this->markNonRequiredPropertiesAsNullable(
                        $configuration->operationDetails['requestBody']['content'][$contentType]['schema']['properties'],
                        $content['schema']['required'] ?? []
                    );
                }
            }
        }

        // Ensure response schema has required field if it has properties
        if (isset($configuration->operationDetails['responses'])) {
            foreach ($configuration->operationDetails['responses'] as $statusCode => $response) {
                if (isset($response['content'])) {
                    foreach ($response['content'] as $contentType => $content) {
                        if (isset($content['schema'])) {
                            // For object type schemas
                            if (isset($content['schema']['type'])
                                && $content['schema']['type'] === 'object'
                                && isset($content['schema']['properties'])) {
                                // Ensure required field exists
                                if (!isset($content['schema']['required'])) {
                                    $configuration->operationDetails['responses'][$statusCode]['content'][$contentType]['schema']['required'] = [];
                                }

                                // Mark non-required properties as nullable
                                $this->markNonRequiredPropertiesAsNullable(
                                    $configuration->operationDetails['responses'][$statusCode]['content'][$contentType]['schema']['properties'],
                                    $content['schema']['required'] ?? []
                                );
                            }

                            // For array of objects
                            if (
                                isset($content['schema']['items']['properties'], $content['schema']['items']['type'], $content['schema']['type']) && $content['schema']['type'] === 'array' && $content['schema']['items']['type'] === 'object') {
                                // Ensure required field exists
                                if (!isset($content['schema']['items']['required'])) {
                                    $configuration->operationDetails['responses'][$statusCode]['content'][$contentType]['schema']['items']['required'] = [];
                                }

                                // Mark non-required properties as nullable
                                $this->markNonRequiredPropertiesAsNullable(
                                    $configuration->operationDetails['responses'][$statusCode]['content'][$contentType]['schema']['items']['properties'],
                                    $content['schema']['items']['required'] ?? []
                                );
                            }
                        }
                    }
                }
            }
        }
    }
    // phpcs:enable Kununu.Files.LineLength

    private function markNonRequiredPropertiesAsNullable(array &$properties, array $requiredProperties): void
    {
        foreach ($properties as $propertyName => &$property) {
            if (!in_array($propertyName, $requiredProperties)) {
                $property['nullable'] = true;
            }
        }
    }
}
