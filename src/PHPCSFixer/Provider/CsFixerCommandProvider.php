<?php
declare(strict_types=1);

namespace Kununu\CodeTools\PHPCSFixer\Provider;

use Composer\Plugin\Capability\CommandProvider;
use Kununu\CodeTools\PHPCSFixer\Command\CsFixerCommand;
use Kununu\CodeTools\PHPCSFixer\Command\CsFixerGitHookCommand;

final class CsFixerCommandProvider implements CommandProvider
{
    public function getCommands(): array
    {
        return [
            new CsFixerCommand(),
            new CsFixerGitHookCommand(),
        ];
    }
}
