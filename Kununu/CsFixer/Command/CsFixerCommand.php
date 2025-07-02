<?php
declare(strict_types=1);

namespace Kununu\CsFixer\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Throwable;

final class CsFixerCommand extends BaseCommand
{
    public const FAILURE = 1;
    public const SUCCESS = 0;

    private const ARGUMENT_FILES = 'files';
    private const OPTION_CONFIG = 'config';
    private const OPTION_EXTRA_ARGS = 'extra-args';

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
            )
            ->addOption(
                self::OPTION_CONFIG,
                'c',
                InputOption::VALUE_OPTIONAL,
                'Path to a PHP CS Fixer config file'
            )
            ->addOption(
                self::OPTION_EXTRA_ARGS,
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Additional arguments to pass to PHP CS Fixer'
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

        $vendorDir = $this->getVendorDir();
        if ($vendorDir === null) {
            $io->error('Could not resolve the vendor directory.');
            return self::FAILURE;
        }

        $phpCsFixerBinary = $vendorDir . '/bin/php-cs-fixer';

        if (!is_file($phpCsFixerBinary) || !is_executable($phpCsFixerBinary)) {
            $io->error(sprintf(
                'PHP CS Fixer binary not found or not executable at "%s".',
                $phpCsFixerBinary
            ));
            return self::FAILURE;
        }

        $configSource = $input->getOption(self::OPTION_CONFIG) ?: __DIR__ . '/../../../php-cs-fixer.php';
        $configPath = realpath($configSource);

        if ($configPath === false || !is_file($configPath)) {
            $io->error(sprintf('Config file "%s" not found.', $configSource));
            return self::FAILURE;
        }

        $io->note(sprintf('Using config file: %s', $configPath));

        $fixerArgs = $input->getOption(self::OPTION_EXTRA_ARGS) ?: [];

        if (!empty($fixerArgs)) {
            $io->note(sprintf(
                'Passing additional fixer arguments: %s',
                implode(' ', $fixerArgs)
            ));
        }

        $process = new Process(
            array_merge(
                [$phpCsFixerBinary, 'fix', '--config=' . $configPath],
                $fixerArgs,
                $files
            )
        );

        $process->setTimeout(null);

        $io->section('Running PHP CS Fixer...');

        $process->run(static fn($type, $buffer) => $io->write($buffer));

        if (!$process->isSuccessful()) {
            $io->error('PHP CS Fixer encountered errors.');
            return self::FAILURE;
        }

        $io->success('PHP CS Fixer completed successfully.');

        return self::SUCCESS;
    }

    private function getVendorDir(): ?string
    {
        try {
            $vendorDir = $this->requireComposer()->getConfig()->get('vendor-dir');

            if (is_dir($vendorDir)) {
                return realpath($vendorDir) ?: $vendorDir;
            }
        } catch (Throwable) {}

        $fallback = realpath(__DIR__ . '/../../../../../');

        return is_dir($fallback) ? $fallback : null;
    }
}
