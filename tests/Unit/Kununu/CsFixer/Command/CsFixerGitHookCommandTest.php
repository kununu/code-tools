<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CsFixer\Command;

use Composer\Console\Application;
use Kununu\CsFixer\Command\CsFixerGitHookCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CsFixerGitHookCommandTest extends TestCase
{
    private string $baseDir;
    private string $repoDir;
    private string $oldCwd;

    public function testFailsWhenNotAGitRepo(): void
    {
        $app = new Application();
        $command = new CsFixerGitHookCommand();
        method_exists($app, 'addCommand')
            ? $app->addCommand($command)
            : $app->add($command);

        $command = $app->find('kununu:cs-fixer-git-hook');
        $tester = new CommandTester($command);

        chdir($this->repoDir);

        $exitCode = $tester->execute([]);

        self::assertSame(CsFixerGitHookCommand::FAILURE, $exitCode);
        self::assertStringContainsString('Not a Git repository or Git not available.', $tester->getDisplay());
    }

    public function testInstallsHookSuccessfully(): void
    {
        // 1) Make this dir a real git repo so `git rev-parse` succeeds
        chdir($this->repoDir);
        exec('git init 2>/dev/null');

        // 2) Create the vendor tree where *your current code expects it*:
        //    resolveVendorDir(dirname($rootGitPath))
        //    If rootGitPath is $this->repoDir, it will look under $this->baseDir/vendor
        $vendorBase = $this->baseDir . '/vendor';
        $codeToolsDir = $vendorBase . '/kununu/code-tools';
        $binDir = $vendorBase . '/bin';
        mkdir($codeToolsDir, 0777, true);
        mkdir($binDir, 0777, true);

        // Files the command symlinks to:
        file_put_contents($codeToolsDir . '/php-cs-fixer.php', "<?php\n// stub\n");
        file_put_contents($binDir . '/php-cs-fixer', "#!/usr/bin/env php\n<?php\n");
        @chmod($binDir . '/php-cs-fixer', 0755);

        $app = new Application();
        $command = new CsFixerGitHookCommand();
        method_exists($app, 'addCommand')
            ? $app->addCommand($command)
            : $app->add($command);

        $command = $app->find('kununu:cs-fixer-git-hook');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(CsFixerGitHookCommand::SUCCESS, $exitCode);

        $gitPath = $this->repoDir . '/.git';

        $hook = $gitPath . '/hooks/pre-commit';
        self::assertFileExists($hook);
        self::assertTrue(is_executable($hook), 'pre-commit should be executable');

        $symlinkConfig = $gitPath . '/kununu/.php-cs-fixer.php';
        $symlinkBin = $gitPath . '/kununu/php-cs-fixer';

        self::assertTrue(is_link($symlinkConfig), '.php-cs-fixer.php symlink should exist');
        self::assertTrue(is_link($symlinkBin), 'php-cs-fixer symlink should exist');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/csfixer_git_' . uniqid('', true);
        $this->repoDir = $this->baseDir . '/project';

        mkdir($this->repoDir, 0777, true);

        $this->oldCwd = (string) getcwd();
    }

    protected function tearDown(): void
    {
        chdir($this->oldCwd);
        if (is_dir($this->baseDir)) {
            exec('rm -rf ' . escapeshellarg($this->baseDir));
        }
        parent::tearDown();
    }
}
