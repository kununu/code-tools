<?php
declare(strict_types=1);

namespace Kununu\CsFixer\Provider;

use Composer\Plugin\Capability\CommandProvider;
use Kununu\CsFixer\Command\CsFixerCommand;
use Kununu\CsFixer\Command\CsFixerConfigCommand;
use Kununu\CsFixer\Command\CsFixerGitHookCommand;

final class CsFixerCommandProvider implements CommandProvider
{
    public function getCommands(): array
    {
        return [
            new CsFixerCommand(),
            new CsFixerConfigCommand(),
            new CsFixerGitHookCommand(),
        ];
    }
}
