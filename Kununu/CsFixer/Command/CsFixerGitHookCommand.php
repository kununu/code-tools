<?php
declare(strict_types=1);

namespace Kununu\CsFixer\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class CsFixerGitHookCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('kununu:cs-fixer-git-hook')
            ->setAliases(['cs-fixer-git-hook'])
            ->setDescription('Installs PHP CS Fixer Git Pre-Commit Hook.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Applying PHP CS Fixer Git Pre-Commit Hook');

        $rootPath = $this->getGitRootPath();
        if (null === $rootPath) {
            $io->error('GIT is not available or the repository root could not be determined.');

            return self::FAILURE;
        }

        $gitPath = sprintf('%s/.git', $rootPath);
        if (!is_dir($gitPath)) {
            $io->error(sprintf('GIT folder not found at "%s"', $gitPath));

            return self::FAILURE;
        }

        $this->addGitHook($gitPath, sprintf('%s/../Hooks/git-pre-commit', __DIR__));

        // Add php-cs-fixer config to .git folder.
        $vendorDir = is_dir(sprintf('%s/services/vendor', $rootPath))
            ? '../../services/vendor'
            : '../../vendor';

        $this->addLinkToGitFolder(
            $gitPath,
            sprintf('%s/kununu/code-tools/php-cs-fixer.php', $vendorDir),
            '.php-cs-fixer.php'
        );

        // Add php-cs-fixer binary to .git folder.
        $this->addLinkToGitFolder(
            $gitPath,
            sprintf('%s/bin/php-cs-fixer', $vendorDir),
            'php-cs-fixer'
        );

        $io->success('Git Hook successfully applied.');

        return self::SUCCESS;
    }

    private function getGitRootPath(): ?string
    {
        exec('git rev-parse --show-toplevel 2>/dev/null', $output, $returnVar);

        return (0 === $returnVar && isset($output[0])) ? $output[0] : null;
    }

    private function addGitHook(string $gitPath, string $sourceFile): void
    {
        $hooksDir = sprintf('%s/hooks', $gitPath);
        if (!is_dir($hooksDir) && !mkdir($hooksDir, 0777, true) && !is_dir($hooksDir)) {
            throw new \RuntimeException(sprintf('Could not create hooks directory: "%s"', $hooksDir));
        }

        $hookPath = sprintf('%s/pre-commit', $hooksDir);
        if (file_exists($hookPath) && !unlink($hookPath)) {
            throw new \RuntimeException(sprintf('Could not remove existing hook at: "%s"', $hookPath));
        }

        if (!copy($sourceFile, $hookPath)) {
            throw new \RuntimeException(sprintf('Failed to copy hook file from "%s" to "%s"', $sourceFile, $hookPath));
        }

        if (!chmod($hookPath, 0755)) {
            throw new \RuntimeException(sprintf('Failed to set executable permissions on hook: "%s"', $hookPath));
        }
    }

    private function addLinkToGitFolder(string $gitPath, string $target, string $linkName): void
    {
        $kununuDir = sprintf('%s/kununu', $gitPath);
        if (!is_dir($kununuDir) && !mkdir($kununuDir, 0777, true) && !is_dir($kununuDir)) {
            throw new \RuntimeException(sprintf('Could not create Kununu folder: "%s"', $kununuDir));
        }

        $linkPath = sprintf('%s/%s', $kununuDir, $linkName);

        if (is_link($linkPath) && !unlink($linkPath)) {
            throw new \RuntimeException(sprintf('Could not remove existing symlink: "%s"', $linkPath));
        }

        if (!symlink($target, $linkPath)) {
            throw new \RuntimeException(sprintf('Failed to create symlink from "%s" to "%s"', $linkPath, $target));
        }
    }
}
