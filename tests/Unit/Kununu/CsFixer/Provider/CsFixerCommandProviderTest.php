<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CsFixer\Provider;

use Kununu\CsFixer\Command\CsFixerCommand;
use Kununu\CsFixer\Command\CsFixerGitHookCommand;
use Kununu\CsFixer\Provider\CsFixerCommandProvider;
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
