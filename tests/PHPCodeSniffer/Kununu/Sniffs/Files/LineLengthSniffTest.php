<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\PHPCodeSniffer\Kununu\Sniffs\Files;

use Kununu\CodeTools\Tests\PHPCodeSniffer\Kununu\Sniffs\SniffTestCase;
use Kununu\Sniffs\Files\LineLengthSniff;

final class LineLengthSniffTest extends SniffTestCase
{
    public function testMethodSignatureArgumentsSniffer(): void
    {
        $this->assertSnifferFindsErrors(new LineLengthSniff(), 4);
    }

    public function testIgnoreUseStatementsCoversUseBranch(): void
    {
        $sniff = new LineLengthSniff();
        $sniff->ignoreUseStatements = true;

        $this->assertSnifferFindsErrors($sniff, 4);
    }
}
