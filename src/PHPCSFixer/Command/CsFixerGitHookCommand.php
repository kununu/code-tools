<?php
declare(strict_types=1);

namespace Kununu\CodeTools\PHPCSFixer\Command;

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
                throw new RuntimeException(
                    sprintf(
                        '.git directory not found at "%s".',
                        $gitPath,
                    ),
                );
            }

            $this->installHook($gitPath);
            $this->linkConfigAndBinary($gitPath);

            $io->success('PHP CS Fixer Git pre‑commit hook installed successfully.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $io->error(sprintf('Installation failed: %s', $e->getMessage()));

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
        $hooksDir = sprintf('%s/hooks', $gitPath);
        $sourceHook = sprintf('%s/../Hooks/git-pre-commit', __DIR__);
        $destHook = sprintf('%s/pre-commit', $hooksDir);

        if (!is_dir($hooksDir) && !@mkdir($hooksDir, 0777, true) && !is_dir($hooksDir)) {
            throw new RuntimeException(
                sprintf(
                    'Could not create hooks directory: "%s".',
                    $hooksDir,
                ),
            );
        }

        if (file_exists($destHook) && !@unlink($destHook)) {
            throw new RuntimeException(
                sprintf(
                    'Could not remove existing hook at "%s".',
                    $destHook,
                ),
            );
        }

        if (!@copy($sourceHook, $destHook)) {
            throw new RuntimeException(
                sprintf(
                    'Failed to copy hook from "%s" to "%s".',
                    $sourceHook,
                    $destHook,
                ),
            );
        }

        if (!@chmod($destHook, 0755)) {
            throw new RuntimeException(
                sprintf(
                    'Failed to make hook executable at "%s".',
                    $destHook,
                ),
            );
        }
    }

    private function linkConfigAndBinary(string $gitPath): void
    {
        $vendorDir = $this->resolveVendorDir($gitPath);
        $configTemplate = $this->resolveConfigTemplate($gitPath);

        $this->ensureSymlinkRelative(
            $configTemplate,
            sprintf('%s/kununu/.php-cs-fixer.php', $gitPath),
        );

        $this->markConfigScope($gitPath, $configTemplate);

        $this->ensureSymlinkRelative(
            sprintf('%s/bin/php-cs-fixer', $vendorDir),
            sprintf('%s/kununu/php-cs-fixer', $gitPath),
        );
    }

    private function resolveConfigTemplate(string $gitPath): string
    {
        $packageRoot = realpath($this->getPackageRoot());
        $repoRoot = realpath(dirname($gitPath));

        // Installing into code-tools itself: use this repository's own config, the one
        // `composer cs-fix` uses.
        if ($packageRoot !== false && $packageRoot === $repoRoot) {
            return sprintf('%s/php-cs-fixer.php.dist', $packageRoot);
        }

        // A project that published the template owns its rules, including the paths it excludes.
        if ($repoRoot !== false && is_file($file = sprintf('%s/php-cs-fixer.php', $repoRoot))) {
            return $file;
        }

        // Otherwise the shipped template, so a project with no config of its own is still
        // held to the kununu standard.
        return $this->packagedTemplate();
    }

    private function getPackageRoot(): string
    {
        return sprintf('%s/../../..', __DIR__);
    }

    private function packagedTemplate(): string
    {
        return sprintf('%s/dist/php-cs-fixer.php.dist', $this->getPackageRoot());
    }

    // The hook may only ask the config which files are in scope when that config is rooted at the
    // project. The packaged template's finder points inside vendor/, so it would match nothing.
    private function markConfigScope(string $gitPath, string $configTemplate): void
    {
        $marker = sprintf('%s/kununu/filter-by-config', $gitPath);
        $packaged = realpath($this->packagedTemplate());
        $isPackaged = $packaged !== false && realpath($configTemplate) === $packaged;

        if ($isPackaged) {
            if (is_file($marker)) {
                unlink($marker);
            }

            return;
        }

        if (file_put_contents($marker, "1\n") === false) {
            throw new RuntimeException(sprintf('Failed to write "%s".', $marker));
        }
    }

    private function resolveVendorDir(string $rootGitPath): string
    {
        $repoRoot = basename($rootGitPath) === '.git' ? dirname($rootGitPath) : $rootGitPath;

        // Candidates where vendor/ might live: repo root, repo root/services, parent of repo root, parent/services
        $parentRoot = dirname($repoRoot);
        $candidates = [
            sprintf('%s/vendor', $repoRoot),
            sprintf('%s/services/vendor', $repoRoot),
            sprintf('%s/vendor', $parentRoot),
            sprintf('%s/services/vendor', $parentRoot),
        ];

        foreach ($candidates as $candidate) {
            if (is_dir($candidate)) {
                $resolved = realpath($candidate);

                return $resolved !== false ? $resolved : $candidate;
            }
        }

        throw new RuntimeException('Could not find vendor directory in project root or its parent.');
    }

    private function ensureSymlinkRelative(string $target, string $linkPath): void
    {
        $linkDir = dirname($linkPath);

        if (!is_dir($linkDir) && !@mkdir($linkDir, 0777, true) && !is_dir($linkDir)) {
            throw new RuntimeException(
                sprintf(
                    'Could not create directory for symlink: "%s".',
                    $linkDir,
                ),
            );
        }

        // Resolve first: makeRelativePath() throws on a missing target, and removing the existing
        // link before that would leave the repository with no hook config at all.
        $relativeTarget = $this->makeRelativePath($linkDir, $target);

        if (is_link($linkPath) || file_exists($linkPath)) {
            unlink($linkPath);
        }

        if (!@symlink($relativeTarget, $linkPath)) {
            throw new RuntimeException(
                sprintf(
                    'Failed to create symlink from "%s" to "%s".',
                    $linkPath,
                    $relativeTarget,
                ),
            );
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
