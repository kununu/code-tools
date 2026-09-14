<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\PHPCodeSniffer\Kununu\Sniffs\Formatting;

use Kununu\CodeTools\Tests\PHPCodeSniffer\Kununu\Sniffs\SniffTestCase;
use Kununu\Sniffs\Formatting\MethodSignatureArgumentsSniff;

final class MethodSignatureArgumentsSniffTest extends SniffTestCase
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
