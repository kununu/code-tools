<?php
declare(strict_types=1);

namespace Kununu\CsFixer\Command;

use Composer\Command\BaseCommand;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Throwable;

final class CsFixerConfigCommand extends BaseCommand
{
    public const SUCCESS = 0;
    public const FAILURE = 1;

    protected function configure(): void
    {
        $this
            ->setName('kununu:cs-fixer-config')
            ->setAliases(['cs-fixer-config'])
            ->setDescription('Runs the shell script to publish the PHP-CS-Fixer config file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Publishing PHP-CS-Fixer Config');

        try {
            $process = new Process([__DIR__ . '/../../../bin/code-tools', 'publish:config', 'cs-fixer']);
            $process->run(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });

            if (!$process->isSuccessful()) {
                throw new RuntimeException('Script failed: ' . $process->getErrorOutput());
            }

            $io->success('Config published successfully.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $io->error('Failed to publish config: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
