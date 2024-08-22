<?php
declare(strict_types=1);

namespace Tests;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Runner;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * To run your sniffer's test, you need to place the `before.php` and `after.php` (optional) files in a folder
 * named exactly like your sniffer name without `Sniff` under _data.
 * (ie: MethodSignatureParametersLineBreakMethodSniff => MethodSignatureParametersLineBreakMethod)
 */
class SniffTestCase extends TestCase
{
    private const FILE_BEFORE = 'before.php';

    private const FILE_AFTER = 'after.php';

    protected function assertSnifferFindsErrors(Sniff $sniffer, int $errorCount): array
    {
        return $this->runFixer($sniffer, $errorCount);
    }

    protected function assertSnifferFindsFixableErrors(Sniff $sniffer, ?int $errorCount, int $fixableErrorCount): array
    {
        return $this->runFixer($sniffer, $errorCount, $fixableErrorCount);
    }

    protected function assertSnifferCanFixErrors(Sniff $sniffer, ?int $fixableErrorCount = null): void
    {
        $this->runFixer($sniffer, null, $fixableErrorCount, true);
    }

    private function runFixer(
        Sniff $sniffer,
        ?int $errorCount = null,
        ?int $fixableErrorCount = null,
        bool $fix = false
    ): array {
        $codeSniffer = new Runner();
        $codeSniffer->config = new Config(['-s']);
        $codeSniffer->init();
        $codeSniffer->ruleset->sniffs = [get_class($sniffer) => $sniffer];
        $codeSniffer->ruleset->populateTokenListeners();
        $file = new LocalFile($this->getDummyFileBefore($sniffer), $codeSniffer->ruleset, $codeSniffer->config);

        if ($fix) {
            $file->fixer->enabled = true;
        }

        $file->process();

        if ($errorCount !== null) {
            $this->assertEquals($errorCount, $file->getErrorCount());
        }

        if ($fixableErrorCount !== null) {
            $this->assertEquals($fixableErrorCount, $file->getFixableCount());
        }

        if ($fix) {
            $diff = $file->fixer->generateDiff($this->getDummyFileAfter($sniffer));
            $this->assertSame('', $diff, $diff);
        }

        $file->cleanUp();

        return $file->getErrors();
    }

    private function getDummyFileBefore(Sniff $sniffer): string
    {
        return $this->getDummyFile($sniffer, self::FILE_BEFORE);
    }

    private function getDummyFileAfter(Sniff $sniffer): string
    {
        return $this->getDummyFile($sniffer, self::FILE_AFTER);
    }

    private function getDummyFile(Sniff $sniffer, string $fileName): string
    {
        $className = (new ReflectionClass($sniffer))->getShortName();
        $className = str_replace('Sniff', '', $className);

        $file = $this->getTestFilesPath() . $className . DS . $fileName;
        if (!file_exists($file)) {
            $this->fail(sprintf('File not found: %s.', $file));
        }

        return $file;
    }

    private function getTestFilesPath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [
                __DIR__,
            '_data',
        ]) . DIRECTORY_SEPARATOR;
    }
}
