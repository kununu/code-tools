<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\Sniffs\Formatting;

use Kununu\Sniffs\Formatting\MethodSignatureArgumentsSniff;
use Tests\SniffTestCase;

class MethodSignatureArgumentsSniffTest extends SniffTestCase
{
    public function testMethodSignatureArgumentsSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new MethodSignatureArgumentsSniff(), 2, 2);
    }

    public function testMethodSignatureArgumentsFixer(): void
    {
        $this->assertSnifferCanFixErrors(new MethodSignatureArgumentsSniff());
    }
}