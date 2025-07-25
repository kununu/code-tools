<?php
declare(strict_types=1);

namespace Kununu\CsFixer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Kununu\CsFixer\Command\CsFixerConfigCommand;
use Kununu\CsFixer\Command\CsFixerGitHookCommand;
use Kununu\CsFixer\Provider\CsFixerCommandProvider;
use RuntimeException;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

final class CsFixerPlugin implements PluginInterface, EventSubscriberInterface, Capable
{
    private Composer $composer;
    private IOInterface $io;

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => [
                'addCsFixerConfig',
                'addCsFixerGitHooks',
            ],
            ScriptEvents::POST_UPDATE_CMD  => [
                'addCsFixerConfig',
                'addCsFixerGitHooks',
            ],
        ];
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
    public function addCsFixerConfig(): void
    {
        $command = new CsFixerConfigCommand();
        $command->setComposer($this->composer);
        $command->setIO($this->io);

        $stdout = fopen('php://stdout', 'w');
        if ($stdout === false) {
            throw new RuntimeException('Unable to open stdout stream.');
        }

        $command->run(new StringInput(''), new StreamOutput($stdout));
    }

    /** @throws ExceptionInterface */
    public function addCsFixerGitHooks(): void
    {
        $command = new CsFixerGitHookCommand();
        $command->setComposer($this->composer);
        $command->setIO($this->io);

        $stdout = fopen('php://stdout', 'w');
        if ($stdout === false) {
            throw new RuntimeException('Unable to open stdout stream.');
        }

        $command->run(new StringInput(''), new StreamOutput($stdout));
    }
}
