<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\Sniffs\Files;

use Kununu\Sniffs\Files\LineLengthSniff;
use Tests\SniffTestCase;

class LineLengthSniffTest extends SniffTestCase
{
    public function testMethodSignatureArgumentsSniffer(): void
    {
        $this->assertSnifferFindsErrors(new LineLengthSniff(), 3);
    }
}