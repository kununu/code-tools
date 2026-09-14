<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\PHPCodeSniffer\Kununu\Sniffs\Classes;

use Kununu\CodeTools\Tests\PHPCodeSniffer\Kununu\Sniffs\SniffTestCase;
use Kununu\Sniffs\Classes\EmptyLineAfterClassElementsSniff;

final class EmptyLineAfterClassElementsSniffTest extends SniffTestCase
{
    public function testEmptyLineAfterClassElementsSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new EmptyLineAfterClassElementsSniff(), 4, 4);
    }

    public function testEmptyLineAfterClassElementsFixer(): void
    {
        $this->assertSnifferCanFixErrors(new EmptyLineAfterClassElementsSniff());
    }
}
