<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\PHPCodeSniffer\Kununu\Sniffs;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Runner;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * To run your sniffer's test, you need to place the `before.php` and `after.php` (optional) files in a folder
 * named exactly like your sniffer name without `Sniff` under `tests/resources/PHPCodeSniffer`.
 * (ie: MethodSignatureParametersLineBreakMethodSniff => MethodSignatureParametersLineBreakMethod)
 */
abstract class SniffTestCase extends TestCase
{
    private const string FILE_BEFORE = 'before/Fixme.php';
    private const string FILE_AFTER = 'after/Fixme.php';

    /** @return array<int, array<int, list<array<string, mixed>>>> */
    protected function assertSnifferFindsErrors(Sniff $sniffer, int $errorCount): array
    {
        return $this->runFixer($sniffer, $errorCount);
    }

    /** @return array<int, array<int, list<array<string, mixed>>>> */
    protected function assertSnifferFindsFixableErrors(Sniff $sniffer, ?int $errorCount, int $fixableErrorCount): array
    {
        return $this->runFixer($sniffer, $errorCount, $fixableErrorCount);
    }

    protected function assertSnifferCanFixErrors(Sniff $sniffer, ?int $fixableErrorCount = null): void
    {
        $this->runFixer($sniffer, null, $fixableErrorCount, true);
    }

    /** @return array<int, array<int, list<array<string, mixed>>>> */
    private function runFixer(
        Sniff $sniffer,
        ?int $errorCount = null,
        ?int $fixableErrorCount = null,
        bool $fix = false,
    ): array {
        $codeSniffer = new Runner();
        $codeSniffer->config = new Config(['-s']);
        $codeSniffer->init();
        $codeSniffer->ruleset->sniffs = [$sniffer::class => $sniffer];
        $codeSniffer->ruleset->populateTokenListeners();
        $file = new LocalFile($this->getDummyFileBefore($sniffer), $codeSniffer->ruleset, $codeSniffer->config);

        if ($fix) {
            $file->fixer->enabled = true;
        }

        $file->process();

        if ($errorCount !== null) {
            self::assertEquals($errorCount, $file->getErrorCount());
        }

        if ($fixableErrorCount !== null) {
            self::assertEquals($fixableErrorCount, $file->getFixableCount());
        }

        if ($fix) {
            $diff = $file->fixer->generateDiff($this->getDummyFileAfter($sniffer));
            self::assertEquals('', $diff, $diff);
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
        $className = new ReflectionClass($sniffer)->getShortName();
        $className = str_replace('Sniff', '', $className);

        $resourcesPath = sprintf('%s/../../../resources/PHPCodeSniffer/', __DIR__);
        $file = sprintf('%s%s%s%s', $resourcesPath, $className, DS, $fileName);
        if (!file_exists($file)) {
            self::fail(sprintf('File not found: %s.', $file));
        }

        return $file;
    }
}
