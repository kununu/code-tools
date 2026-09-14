<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\PHPCSFixer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Kununu\CodeTools\PHPCSFixer\CsFixerPlugin;
use Kununu\CodeTools\PHPCSFixer\Provider\CsFixerCommandProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class CsFixerPluginTest extends TestCase
{
    private string $baseDir;
    private string $repoDir;
    private string $oldCwd;
    private Stub&Composer $composer;
    private Stub&IOInterface $io;
    private CsFixerPlugin $plugin;

    public function testGetSubscribedEventsReturnsExpectedEvents(): void
    {
        $events = CsFixerPlugin::getSubscribedEvents();

        self::assertArrayHasKey(ScriptEvents::POST_INSTALL_CMD, $events);
        self::assertArrayHasKey(ScriptEvents::POST_UPDATE_CMD, $events);
        self::assertEquals(['addCsFixerGitHooks'], $events[ScriptEvents::POST_INSTALL_CMD]);
        self::assertEquals(['addCsFixerGitHooks'], $events[ScriptEvents::POST_UPDATE_CMD]);
    }

    public function testActivateStoresComposerAndIo(): void
    {
        self::assertNull($this->getPropertyValue($this->plugin, 'composer'));
        self::assertNull($this->getPropertyValue($this->plugin, 'io'));

        $this->plugin->activate($this->composer, $this->io);

        self::assertEquals($this->composer, $this->getPropertyValue($this->plugin, 'composer'));
        self::assertEquals($this->io, $this->getPropertyValue($this->plugin, 'io'));
    }

    public function testDeactivateDoesNothing(): void
    {
        self::assertNull($this->getPropertyValue($this->plugin, 'composer'));
        self::assertNull($this->getPropertyValue($this->plugin, 'io'));

        $this->plugin->deactivate($this->composer, $this->io);

        self::assertNull($this->getPropertyValue($this->plugin, 'composer'));
        self::assertNull($this->getPropertyValue($this->plugin, 'io'));
    }

    public function testUninstallDoesNotThrow(): void
    {
        self::assertNull($this->getPropertyValue($this->plugin, 'composer'));
        self::assertNull($this->getPropertyValue($this->plugin, 'io'));

        $this->plugin->uninstall($this->composer, $this->io);

        self::assertNull($this->getPropertyValue($this->plugin, 'composer'));
        self::assertNull($this->getPropertyValue($this->plugin, 'io'));
    }

    public function testGetCapabilitiesReturnsCommandProvider(): void
    {
        $capabilities = $this->plugin->getCapabilities();

        self::assertArrayHasKey(CommandProvider::class, $capabilities);
        self::assertEquals(CsFixerCommandProvider::class, $capabilities[CommandProvider::class]);
    }

    public function testAddCsFixerGitHooksInstallsTheHook(): void
    {
        $this->prepareProject();
        $this->plugin->activate($this->composer, $this->io);

        ob_start();
        $this->plugin->addCsFixerGitHooks();
        $display = (string) ob_get_clean();

        self::assertStringContainsString('installed successfully', $display);
        $this->assertHookInstalled();
    }

    /**
     * The composer.json of this repository wires this static method to post-install-cmd, because
     * Composer never loads the root package as a plugin and so never reaches the event above.
     */
    public function testInstallGitHooksInstallsTheHookFromAComposerScript(): void
    {
        $this->prepareProject();

        $event = new Event(
            ScriptEvents::POST_INSTALL_CMD,
            $this->createStub(Composer::class),
            $this->createStub(IOInterface::class),
        );

        ob_start();
        CsFixerPlugin::installGitHooks($event);
        $display = (string) ob_get_clean();

        self::assertStringContainsString('installed successfully', $display);
        $this->assertHookInstalled();
    }

    protected function setUp(): void
    {
        $this->baseDir = sprintf('%s/csfixer_plugin_%s', sys_get_temp_dir(), uniqid('', true));

        $this->repoDir = sprintf('%s/project', $this->baseDir);
        mkdir($this->repoDir, 0777, true);

        $this->oldCwd = (string) getcwd();
        $this->composer = self::createStub(Composer::class);
        $this->io = self::createStub(IOInterface::class);
        $this->plugin = new CsFixerPlugin();
    }

    protected function tearDown(): void
    {
        chdir($this->oldCwd);
        if (is_dir($this->baseDir)) {
            exec(sprintf('rm -rf %s', escapeshellarg($this->baseDir)));
        }
    }

    /**
     * A throwaway repository to install into. Without it the command would resolve the git root of
     * this very repository and rewrite its hooks as a side effect of running the test suite.
     */
    private function prepareProject(): void
    {
        $binDir = sprintf('%s/vendor/bin', $this->baseDir);
        mkdir($binDir, 0777, true);
        file_put_contents(sprintf('%s/php-cs-fixer', $binDir), "#!/usr/bin/env php\n<?php\n");

        chdir($this->repoDir);
        exec('git init 2>/dev/null');
    }

    private function assertHookInstalled(): void
    {
        $gitPath = sprintf('%s/.git', $this->repoDir);

        self::assertFileExists(sprintf('%s/hooks/pre-commit', $gitPath));
        self::assertFileExists(sprintf('%s/kununu/.php-cs-fixer.php', $gitPath));
        self::assertFileExists(sprintf('%s/kununu/php-cs-fixer', $gitPath));
    }

    private function getPropertyValue(object $object, string $property): mixed
    {
        return new ReflectionClass($object)->getProperty($property)->getValue($object);
    }
}
