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

final class CsFixerGitHookCommand extends BaseCommand
{
    public const int SUCCESS = 0;
    public const int FAILURE = 1;

    protected function configure(): void
    {
        $this
            ->setName('kununu:cs-fixer-git-hook')
            ->setAliases(['cs-fixer-git-hook'])
            ->setDescription('Installs PHP CS Fixer as a Git pre-commit hook.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Installing PHP CS Fixer Git Pre‑Commit Hook');

        try {
            $rootPath = $this->getGitRootPath();
            $gitPath = $rootPath . '/.git';

            if (!is_dir($gitPath)) {
                throw new RuntimeException(sprintf(
                    '.git directory not found at "%s".',
                    $gitPath
                ));
            }

            $this->installHook($gitPath);
            $this->linkConfigAndBinary($gitPath);

            $io->success('PHP CS Fixer Git pre‑commit hook installed successfully.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $io->error('Installation failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    private function getGitRootPath(): string
    {
        $cwd = getcwd();
        if ($cwd === false) {
            throw new RuntimeException('Could not determine current working directory.');
        }

        // Mark the directory as safe
        $process = new Process(['git', 'config', '--global', '--add', 'safe.directory', $cwd]);
        $process->run();

        $process = new Process(['git', 'rev-parse', '--show-toplevel']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException('Not a Git repository or Git not available.');
        }

        return trim($process->getOutput());
    }

    private function installHook(string $gitPath): void
    {
        $hooksDir = $gitPath . '/hooks';
        $sourceHook = __DIR__ . '/../Hooks/git-pre-commit';
        $destHook = $hooksDir . '/pre-commit';

        if (!is_dir($hooksDir) && !mkdir($hooksDir, 0777, true) && !is_dir($hooksDir)) {
            throw new RuntimeException(sprintf(
                'Could not create hooks directory: "%s".',
                $hooksDir
            ));
        }

        if (file_exists($destHook) && !unlink($destHook)) {
            throw new RuntimeException(sprintf(
                'Could not remove existing hook at "%s".',
                $destHook
            ));
        }

        if (!copy($sourceHook, $destHook)) {
            throw new RuntimeException(sprintf(
                'Failed to copy hook from "%s" to "%s".',
                $sourceHook,
                $destHook
            ));
        }

        if (!chmod($destHook, 0755)) {
            throw new RuntimeException(sprintf(
                'Failed to make hook executable at "%s".',
                $destHook
            ));
        }
    }

    private function linkConfigAndBinary(string $gitPath): void
    {
        $vendorDir = $this->resolveVendorDir($gitPath);

        $this->ensureSymlinkRelative(
            $vendorDir . '/kununu/code-tools/php-cs-fixer.php',
            $gitPath . '/kununu/.php-cs-fixer.php'
        );

        $this->ensureSymlinkRelative(
            $vendorDir . '/bin/php-cs-fixer',
            $gitPath . '/kununu/php-cs-fixer'
        );
    }

    private function resolveVendorDir(string $rootGitPath): string
    {
        $repoRoot = basename($rootGitPath) === '.git' ? dirname($rootGitPath) : $rootGitPath;

        // Candidates where vendor/ might live: repo root, repo root/services, parent of repo root, parent/services
        $parentRoot = dirname($repoRoot);
        $candidates = [
            $repoRoot . '/vendor',
            $repoRoot . '/services/vendor',
            $parentRoot . '/vendor',
            $parentRoot . '/services/vendor',
        ];

        foreach ($candidates as $candidate) {
            if (is_dir($candidate)) {
                return realpath($candidate) ?: $candidate;
            }
        }

        throw new RuntimeException('Could not find vendor directory in project root or its parent.');
    }

    private function ensureSymlinkRelative(string $target, string $linkPath): void
    {
        $linkDir = dirname($linkPath);

        if (!is_dir($linkDir) && !mkdir($linkDir, 0777, true) && !is_dir($linkDir)) {
            throw new RuntimeException(sprintf(
                'Could not create directory for symlink: "%s".',
                $linkDir
            ));
        }

        if (is_link($linkPath) || file_exists($linkPath)) {
            unlink($linkPath);
        }

        $relativeTarget = $this->makeRelativePath($linkDir, $target);

        if (!symlink($relativeTarget, $linkPath)) {
            throw new RuntimeException(sprintf(
                'Failed to create symlink from "%s" to "%s".',
                $linkPath,
                $relativeTarget
            ));
        }
    }

    private function makeRelativePath(string $from, string $to): string
    {
        $fromReal = realpath($from);
        $toReal = realpath($to);

        if ($fromReal === false || $toReal === false) {
            throw new RuntimeException('Invalid path(s) provided.');
        }

        $from = explode(DIRECTORY_SEPARATOR, $fromReal);
        $to = explode(DIRECTORY_SEPARATOR, $toReal);

        while (count($from) && count($to) && ($from[0] === $to[0])) {
            array_shift($from);
            array_shift($to);
        }

        return str_repeat('../', count($from)) . implode('/', $to);
    }
}
