<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\PHPCSFixer\Provider;

use Kununu\CodeTools\PHPCSFixer\Command\CsFixerCommand;
use Kununu\CodeTools\PHPCSFixer\Command\CsFixerGitHookCommand;
use Kununu\CodeTools\PHPCSFixer\Provider\CsFixerCommandProvider;
use PHPUnit\Framework\TestCase;

final class CsFixerCommandProviderTest extends TestCase
{
    public function testGetCommandsReturnsExpectedCommands(): void
    {
        $provider = new CsFixerCommandProvider();

        $commands = $provider->getCommands();

        self::assertCount(2, $commands);
        self::assertInstanceOf(CsFixerCommand::class, $commands[0]);
        self::assertInstanceOf(CsFixerGitHookCommand::class, $commands[1]);
    }
}
