<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\PHPCSFixer\Command;

use Composer\Command\BaseCommand;
use Composer\Console\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends TestCase
{
    protected CommandTester $tester;

    abstract protected function getCommand(): BaseCommand;

    abstract protected function getCommandName(): string;

    protected function setUp(): void
    {
        $application = new Application();
        $application->addCommand($this->getCommand());

        $this->tester = new CommandTester($application->find($this->getCommandName()));
    }
}
