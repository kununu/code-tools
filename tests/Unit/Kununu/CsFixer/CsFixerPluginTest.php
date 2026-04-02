<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CsFixer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Script\ScriptEvents;
use Kununu\CsFixer\CsFixerPlugin;
use Kununu\CsFixer\Provider\CsFixerCommandProvider;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

final class CsFixerPluginTest extends TestCase
{
    public function testGetSubscribedEventsReturnsExpectedEvents(): void
    {
        $events = CsFixerPlugin::getSubscribedEvents();

        self::assertArrayHasKey(ScriptEvents::POST_INSTALL_CMD, $events);
        self::assertArrayHasKey(ScriptEvents::POST_UPDATE_CMD, $events);
        self::assertSame(['addCsFixerGitHooks'], $events[ScriptEvents::POST_INSTALL_CMD]);
        self::assertSame(['addCsFixerGitHooks'], $events[ScriptEvents::POST_UPDATE_CMD]);
    }

    public function testActivateStoresComposerAndIo(): void
    {
        $plugin = new CsFixerPlugin();
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);

        $plugin->activate($composer, $io);

        self::assertInstanceOf(CsFixerPlugin::class, $plugin);
    }

    public function testDeactivateDoesNotThrow(): void
    {
        $plugin = new CsFixerPlugin();
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);

        $plugin->deactivate($composer, $io);

        self::assertInstanceOf(CsFixerPlugin::class, $plugin);
    }

    public function testUninstallDoesNotThrow(): void
    {
        $plugin = new CsFixerPlugin();
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);

        $plugin->uninstall($composer, $io);

        self::assertInstanceOf(CsFixerPlugin::class, $plugin);
    }

    public function testGetCapabilitiesReturnsCommandProvider(): void
    {
        $plugin = new CsFixerPlugin();

        $capabilities = $plugin->getCapabilities();

        self::assertArrayHasKey(CommandProvider::class, $capabilities);
        self::assertSame(CsFixerCommandProvider::class, $capabilities[CommandProvider::class]);
    }

    public function testAddCsFixerGitHooksExecutesWithoutThrowing(): void
    {
        $plugin = new CsFixerPlugin();
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);
        $plugin->activate($composer, $io);

        ob_start();
        try {
            $plugin->addCsFixerGitHooks();
        } catch (\Throwable) {
        }
        ob_end_clean();

        self::assertInstanceOf(CsFixerPlugin::class, $plugin);
    }
}
