<?php
declare(strict_types=1);

namespace Kununu\CodingStandards;

use Composer\Plugin\Capability\CommandProvider;
use Kununu\CodingStandards\Command\PHPCsFixerCodeCommand;
use Kununu\CodingStandards\Command\PHPCsFixerGitHookCommand;

final class PHPCsFixerCommandProvider implements CommandProvider
{
    public function getCommands(): array
    {
        return [
            new PHPCsFixerCodeCommand(),
            new PHPCsFixerGitHookCommand(),
        ];
    }
}
