<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\Sniffs\PHP;

use Kununu\Sniffs\PHP\NoNewLineBeforeDeclareStrictSniff;
use Tests\SniffTestCase;

class NoNewLineBeforeDeclareStrictSniffTest extends SniffTestCase
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
