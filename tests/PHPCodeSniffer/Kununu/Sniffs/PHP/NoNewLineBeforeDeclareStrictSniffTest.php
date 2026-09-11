<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\PHPCodeSniffer\Kununu\Sniffs\PHP;

use Kununu\CodeTools\Tests\PHPCodeSniffer\Kununu\Sniffs\SniffTestCase;
use Kununu\Sniffs\PHP\NoNewLineBeforeDeclareStrictSniff;

final class NoNewLineBeforeDeclareStrictSniffTest extends SniffTestCase
{
    public function testNoNewLineBeforeDeclareStrictSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new NoNewLineBeforeDeclareStrictSniff(), 1, 1);
    }

    public function testNoNewLineBeforeDeclareStrictFixer(): void
    {
        $this->assertSnifferCanFixErrors(new NoNewLineBeforeDeclareStrictSniff());
    }
}
