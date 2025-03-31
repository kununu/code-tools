<?php

declare(strict_types=1);

namespace Kununu\CodeGenerator\Application\Service;

use Kununu\CodeGenerator\Domain\DTO\BoilerplateConfiguration;
use Kununu\CodeGenerator\Domain\Service\CodeGeneratorInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FileGenerationHandler
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

            if (!$this->io->confirm('Do you want to proceed with generating these files?', true)) {
                $this->io->warning('Code generation canceled by user.');

                return [];
            }
        }

        $this->handleExistingFiles($configuration, $existingFiles);

        return $filesToGenerate;
    }

    public function generateFiles(BoilerplateConfiguration $configuration, array $filesToGenerate, bool $quiet): array
    {
        $generatedFiles = $this->codeGenerator->generate($configuration);

        if (!$quiet) {
            $this->displayGenerationSummary($configuration, $generatedFiles);
            $this->displayRouteInformation($configuration, $filesToGenerate);
        }

        return $generatedFiles;
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
        foreach ($filesToGenerate as $file) {
            $status = $file['exists'] ? '<comment>(exists)</comment>' : '<info>(new)</info>';
            $this->io->writeln(sprintf(' - %s %s (using %s)', $status, $file['path'], $file['template_path']));
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
            if (!$this->io->confirm(sprintf('File <info>%s</info> exists. Overwrite? [Y/n]', $existingFile), true)) {
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
        $this->io->writeln(sprintf('Methods: <info>[%s]</info>', $configuration->operationDetails['method']));
    }

    private function findControllerName(BoilerplateConfiguration $configuration, array $filesToGenerate): ?string
    {
        foreach ($filesToGenerate as $file) {
            if (str_contains($file['path'], 'Controller')) {
                $controllerPath = $file['path'];
                $relativePath = str_replace($configuration->basePath . '/', '', $controllerPath);
                $relativePath = str_replace('.php', '', $relativePath);

                return $configuration->namespace . '\\' . str_replace('/', '\\', $relativePath);
            }
        }

        return null;
    }
}
