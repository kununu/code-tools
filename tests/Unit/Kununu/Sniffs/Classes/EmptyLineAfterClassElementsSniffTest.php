<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\Sniffs\Classes;

use Kununu\Sniffs\Classes\EmptyLineAfterClassElementsSniff;
use Tests\SniffTestCase;

class EmptyLineAfterClassElementsSniffTest extends SniffTestCase
{
    public function testEmptyLineAfterClassElementsSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new EmptyLineAfterClassElementsSniff(), 2, 2);
    }

    public function testEmptyLineAfterClassElementsFixer(): void
    {
        $this->assertSnifferCanFixErrors(new EmptyLineAfterClassElementsSniff());
    }
}
