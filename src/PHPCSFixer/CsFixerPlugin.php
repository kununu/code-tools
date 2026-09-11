<?php
declare(strict_types=1);

namespace Kununu\CodeTools\PHPCSFixer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Kununu\CodeTools\PHPCSFixer\Command\CsFixerGitHookCommand;
use Kununu\CodeTools\PHPCSFixer\Provider\CsFixerCommandProvider;
use RuntimeException;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

final class CsFixerPlugin implements PluginInterface, EventSubscriberInterface, Capable
{
    private ?Composer $composer = null;
    private ?IOInterface $io = null;

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => ['addCsFixerGitHooks'],
            ScriptEvents::POST_UPDATE_CMD  => ['addCsFixerGitHooks'],
        ];
    }

    /**
     * Composer only loads plugins from installed packages, never from the root package, so this
     * repository's own `composer install` can never reach the events above. It wires this method
     * in as a script callable instead. Consumers installing with `--no-plugins`, as the README
     * recommends, can do the same.
     *
     * @throws ExceptionInterface
     */
    public static function installGitHooks(Event $event): void
    {
        $plugin = new self();
        $plugin->activate($event->getComposer(), $event->getIO());
        $plugin->addCsFixerGitHooks();
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public function getCapabilities(): array
    {
        return [
            CommandProvider::class => CsFixerCommandProvider::class,
        ];
    }

    /** @throws ExceptionInterface */
    public function addCsFixerGitHooks(): void
    {
        if ($this->composer === null || $this->io === null) {
            return;
        }

        $command = new CsFixerGitHookCommand();
        $command->setComposer($this->composer);
        $command->setIO($this->io);

        $output = fopen('php://output', 'w');
        if ($output === false) {
            throw new RuntimeException('Unable to open output stream.');
        }

        $command->run(new StringInput(''), new StreamOutput($output));
    }
}
