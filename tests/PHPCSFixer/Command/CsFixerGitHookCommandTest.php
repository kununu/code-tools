<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\PHPCSFixer\Command;

use Kununu\CodeTools\PHPCSFixer\Command\CsFixerGitHookCommand;
use ReflectionMethod;

final class CsFixerGitHookCommandTest extends CommandTestCase
{
    private string $baseDir;
    private string $repoDir;
    private string $oldCwd;
    private ReflectionMethod $method;

    public function testFailsWhenNotAGitRepo(): void
    {
        chdir($this->repoDir);

        $exitCode = $this->tester->execute([]);

        self::assertEquals(CsFixerGitHookCommand::FAILURE, $exitCode);
        self::assertStringContainsString('Not a Git repository or Git not available.', $this->tester->getDisplay());
    }

    public function testInstallsHookSuccessfully(): void
    {
        // Make this dir a real git repo so `git rev-parse` succeeds
        chdir($this->repoDir);
        exec('git init 2>/dev/null');

        $this->createVendorTree();

        $exitCode = $this->tester->execute([]);

        self::assertEquals(CsFixerGitHookCommand::SUCCESS, $exitCode);

        $gitPath = sprintf('%s/.git', $this->repoDir);
        $hook = sprintf('%s/hooks/pre-commit', $gitPath);

        self::assertFileExists($hook);
        self::assertTrue(is_executable($hook), 'pre-commit should be executable');

        $symlinkConfig = sprintf('%s/kununu/.php-cs-fixer.php', $gitPath);
        $symlinkBin = sprintf('%s/kununu/php-cs-fixer', $gitPath);

        self::assertTrue(is_link($symlinkConfig), '.php-cs-fixer.php symlink should exist');
        self::assertTrue(is_link($symlinkBin), 'php-cs-fixer symlink should exist');

        // is_link() also passes for a dangling link, and there is no vendored copy of the
        // template here, so this is what proves the package resolves its own template.
        self::assertFileExists($symlinkConfig, '.php-cs-fixer.php symlink must resolve');
        self::assertFileExists($symlinkBin, 'php-cs-fixer symlink must resolve');

        // A foreign repository is not code-tools itself, so it gets the published template.
        self::assertStringEndsWith(
            '/dist/php-cs-fixer.php.dist',
            (string) realpath($symlinkConfig),
            'a consuming repository must be linked to the published template',
        );
    }

    public function testSelfInstallLinksTheRepositoryOwnConfig(): void
    {
        $packageRoot = dirname(__DIR__, 3);

        // Only meaningful when running from a checkout of code-tools itself.
        self::assertFileExists(sprintf('%s/php-cs-fixer.php.dist', $packageRoot));

        $template = $this->resolveConfigTemplate(sprintf('%s/.git', $packageRoot));

        self::assertEquals(sprintf('%s/php-cs-fixer.php.dist', $packageRoot), $template);
        self::assertFileExists($template);

        // And a repository that is not this package still gets the published template
        $foreign = $this->resolveConfigTemplate(sprintf('%s/.git', $this->repoDir));

        self::assertStringEndsWith('/dist/php-cs-fixer.php.dist', $foreign);
    }

    public function testProjectWithItsOwnConfigIsLinkedToIt(): void
    {
        file_put_contents(sprintf('%s/php-cs-fixer.php', $this->repoDir), "<?php\nreturn null;\n");

        $template = $this->resolveConfigTemplate(sprintf('%s/.git', $this->repoDir));

        self::assertEquals($this->repoDir . '/php-cs-fixer.php', $template);
    }

    public function testScopeMarkerIsWrittenOnlyForAProjectRootedConfig(): void
    {
        chdir($this->repoDir);
        exec('git init 2>/dev/null');

        $this->createVendorTree();

        $marker = sprintf('%s/.git/kununu/filter-by-config', $this->repoDir);

        // Linked to the packaged template: its finder lives in vendor/, so the hook must not
        // ask it which files are in scope, or it would match nothing and fix nothing.
        self::assertEquals(CsFixerGitHookCommand::SUCCESS, $this->tester->execute([]));
        self::assertFileDoesNotExist($marker);

        // Once the project owns a config, the hook may defer to it.
        file_put_contents(sprintf('%s/php-cs-fixer.php', $this->repoDir), "<?php\nreturn null;\n");

        self::assertEquals(CsFixerGitHookCommand::SUCCESS, $this->tester->execute([]));
        self::assertFileExists($marker);
    }

    public function testFailedReinstallKeepsTheExistingSymlinks(): void
    {
        chdir($this->repoDir);
        exec('git init 2>/dev/null');

        $binDir = $this->createVendorTree();

        self::assertEquals(CsFixerGitHookCommand::SUCCESS, $this->tester->execute([]));

        $gitPath = sprintf('%s/.git', $this->repoDir);
        $symlinkConfig = sprintf('%s/kununu/.php-cs-fixer.php', $gitPath);
        $symlinkBin = sprintf('%s/kununu/php-cs-fixer', $gitPath);

        // Make the second symlink target unreachable, then reinstall: the command fails, but it
        // must not leave the repository with the hook half-uninstalled.
        unlink(sprintf('%s/php-cs-fixer', $binDir));

        self::assertEquals(CsFixerGitHookCommand::FAILURE, $this->tester->execute([]));

        self::assertTrue(is_link($symlinkConfig), 'config symlink must survive a failed reinstall');
        self::assertTrue(is_link($symlinkBin), 'binary symlink must survive a failed reinstall');
        self::assertFileExists($symlinkConfig, 'config symlink must still resolve');
    }

    public function testReinstallRemovesExistingHookAndSymlinks(): void
    {
        chdir($this->repoDir);
        exec('git init 2>/dev/null');

        $this->createVendorTree();

        $this->tester->execute([]);

        $exitCode = $this->tester->execute([]);

        self::assertEquals(CsFixerGitHookCommand::SUCCESS, $exitCode);
        self::assertFileExists(sprintf('%s/.git/hooks/pre-commit', $this->repoDir));
        self::assertTrue(is_link(sprintf('%s/.git/kununu/.php-cs-fixer.php', $this->repoDir)));
        self::assertTrue(is_link(sprintf('%s/.git/kununu/php-cs-fixer', $this->repoDir)));
    }

    public function testFailsWhenGitPathIsNotDirectory(): void
    {
        $mainRepo = sprintf('%s/main', $this->baseDir);
        mkdir($mainRepo, 0777, true);
        chdir($mainRepo);
        exec('git init 2>/dev/null');

        file_put_contents(
            sprintf('%s/.git', $this->repoDir),
            sprintf("gitdir: %s/.git\n", $mainRepo),
        );

        chdir($this->repoDir);

        $exitCode = $this->tester->execute([]);

        self::assertEquals(CsFixerGitHookCommand::FAILURE, $exitCode);
        self::assertStringContainsString('.git directory not found', $this->tester->getDisplay());
    }

    public function testFailsWhenHooksDirCannotBeCreated(): void
    {
        chdir($this->repoDir);
        exec('git init 2>/dev/null');
        exec(sprintf('rm -rf %s', escapeshellarg(sprintf('%s/.git/hooks', $this->repoDir))));
        chmod(sprintf('%s/.git', $this->repoDir), 0555);

        $exitCode = $this->tester->execute([]);

        chmod(sprintf('%s/.git', $this->repoDir), 0755);

        self::assertEquals(CsFixerGitHookCommand::FAILURE, $exitCode);
        self::assertStringContainsString('Could not create hooks directory', $this->tester->getDisplay());
    }

    public function testFailsWhenHookCopyFails(): void
    {
        chdir($this->repoDir);
        exec('git init 2>/dev/null');
        chmod(sprintf('%s/.git/hooks', $this->repoDir), 0555);

        $exitCode = $this->tester->execute([]);

        chmod(sprintf('%s/.git/hooks', $this->repoDir), 0755);

        self::assertEquals(CsFixerGitHookCommand::FAILURE, $exitCode);
        self::assertStringContainsString('Failed to copy hook', $this->tester->getDisplay());
    }

    public function testFailsWhenExistingHookCannotBeRemoved(): void
    {
        chdir($this->repoDir);
        exec('git init 2>/dev/null');

        $hooksDir = sprintf('%s/.git/hooks', $this->repoDir);
        file_put_contents(sprintf('%s/pre-commit', $hooksDir), '#!/bin/sh');
        chmod($hooksDir, 0555);

        $exitCode = $this->tester->execute([]);

        chmod($hooksDir, 0755);

        self::assertEquals(CsFixerGitHookCommand::FAILURE, $exitCode);
        self::assertStringContainsString('Could not remove existing hook', $this->tester->getDisplay());
    }

    public function testFailsWhenVendorDirNotFound(): void
    {
        chdir($this->repoDir);
        exec('git init 2>/dev/null');

        $exitCode = $this->tester->execute([]);

        self::assertEquals(CsFixerGitHookCommand::FAILURE, $exitCode);
        self::assertStringContainsString('Could not find vendor directory', $this->tester->getDisplay());
    }

    public function testScopeMarkerIsRemovedWhenTheProjectConfigGoesAway(): void
    {
        chdir($this->repoDir);
        exec('git init 2>/dev/null');
        $this->createVendorTree();

        $config = sprintf('%s/php-cs-fixer.php', $this->repoDir);
        $marker = sprintf('%s/.git/kununu/filter-by-config', $this->repoDir);

        file_put_contents($config, "<?php\nreturn null;\n");

        self::assertEquals(CsFixerGitHookCommand::SUCCESS, $this->tester->execute([]));
        self::assertFileExists($marker);

        // The project dropped its own config, so the hook falls back to the packaged template and
        // must stop narrowing staged files through a config that is no longer there.
        unlink($config);

        self::assertEquals(CsFixerGitHookCommand::SUCCESS, $this->tester->execute([]));
        self::assertFileDoesNotExist($marker);
    }

    public function testFailsWhenSymlinkDirectoryCannotBeCreated(): void
    {
        chdir($this->repoDir);
        exec('git init 2>/dev/null');
        $this->createVendorTree();

        // The hooks directory already exists and stays writable, so installation gets past the hook
        // itself and only then fails to create the directory holding the symlinks.
        $gitPath = sprintf('%s/.git', $this->repoDir);
        chmod($gitPath, 0555);

        $exitCode = $this->tester->execute([]);

        chmod($gitPath, 0755);

        self::assertEquals(CsFixerGitHookCommand::FAILURE, $exitCode);
        self::assertStringContainsString('Could not create directory', $this->tester->getDisplay());
    }

    public function testFailsWhenSymlinkCannotBeCreated(): void
    {
        chdir($this->repoDir);
        exec('git init 2>/dev/null');
        $this->createVendorTree();

        // Here the directory is already there, so it is the symlink itself that cannot be written.
        $kununuDir = sprintf('%s/.git/kununu', $this->repoDir);
        mkdir($kununuDir, 0777, true);
        chmod($kununuDir, 0555);

        $exitCode = $this->tester->execute([]);

        chmod($kununuDir, 0755);

        self::assertEquals(CsFixerGitHookCommand::FAILURE, $exitCode);
        self::assertStringContainsString('Failed to create symlink', $this->tester->getDisplay());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sprintf('%s/csfixer_git_%s', sys_get_temp_dir(), uniqid('', true));

        $this->repoDir = sprintf('%s/project', $this->baseDir);
        mkdir($this->repoDir, 0777, true);

        $this->oldCwd = (string) getcwd();
        $this->method = new ReflectionMethod(CsFixerGitHookCommand::class, 'resolveConfigTemplate');
    }

    protected function tearDown(): void
    {
        chdir($this->oldCwd);
        if (is_dir($this->baseDir)) {
            exec(sprintf('chmod -R 755 %s 2>/dev/null', escapeshellarg($this->baseDir)));
            exec(sprintf('rm -rf %s', escapeshellarg($this->baseDir)));
        }
    }

    protected function getCommand(): CsFixerGitHookCommand
    {
        return new CsFixerGitHookCommand();
    }

    protected function getCommandName(): string
    {
        return 'kununu:cs-fixer-git-hook';
    }

    // The vendor tree where the command looks for it: resolveVendorDir() walks up from the
    // repository, so for $this->repoDir that is $this->baseDir/vendor. Returns the bin directory,
    // which holds the binary the command symlinks to.
    private function createVendorTree(): string
    {
        $vendorBase = sprintf('%s/vendor', $this->baseDir);
        $binDir = sprintf('%s/bin', $vendorBase);

        mkdir(sprintf('%s/kununu/code-tools', $vendorBase), 0777, true);
        mkdir($binDir, 0777, true);

        file_put_contents(sprintf('%s/php-cs-fixer', $binDir), "#!/usr/bin/env php\n<?php\n");
        @chmod(sprintf('%s/php-cs-fixer', $binDir), 0755);

        return $binDir;
    }

    private function resolveConfigTemplate(string $gitPath): mixed
    {
        return $this->method->invoke($this->getCommand(), $gitPath);
    }
}
