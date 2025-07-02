<?php
declare(strict_types=1);

namespace Kununu\CsFixer\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class CsFixerCommand extends BaseCommand
{
    private const ARGUMENT_FILES = 'files';

    protected function configure(): void
    {
        $this
            ->setName('kununu:cs-fixer')
            ->setAliases(['cs-fixer'])
            ->setDescription('Applies PHP CS Fixer on specified files or directories.')
            ->addArgument(
                self::ARGUMENT_FILES,
                InputArgument::IS_ARRAY,
                'Files or directories to fix'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $files = $input->getArgument(self::ARGUMENT_FILES);
        if (empty($files)) {
            $io->error('No files or directories were provided.');

            return self::FAILURE;
        }

        $vendorDir = $this->requireComposer()->getConfig()->get('vendor-dir');
        if (!is_dir($vendorDir)) {
            $io->error(sprintf('Vendor directory not found at "%s"', $vendorDir));

            return self::FAILURE;
        }

        $configPath = sprintf('%s/../Scripts/php_cs', __DIR__);
        if (!file_exists($configPath)) {
            $io->error(sprintf('PHP CS Fixer config file not found at "%s"', $configPath));

            return self::FAILURE;
        }

        $command = sprintf(
            '%s/bin/php-cs-fixer fix --config=%s %s',
            $vendorDir,
            $configPath,
            implode(' ', $files)
        );

        $io->section('Running PHP CS Fixer...');
        exec($command, $outputExec, $exitCode);

        if (0 !== $exitCode) {
            $io->error('Errors occurred while running PHP CS Fixer.');
            $io->writeln($outputExec);

            return self::FAILURE;
        }

        if (!empty($outputExec)) {
            $io->success('PHP CS Fixer completed with the following output:');
            $io->writeln($outputExec);
        } else {
            $io->success('No files were affected.');
        }

        return self::SUCCESS;
    }
}
