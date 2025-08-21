<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CsFixer\Command;

use Composer\Console\Application;
use Kununu\CsFixer\Command\CsFixerCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CsFixerCommandTest extends TestCase
{
    private ?string $tempFile = null;

    #[DataProvider('fixerCasesProvider')]
    public function testCsFixerCommand(string $before, string $after): void
    {
        $this->tempFile = sys_get_temp_dir() . '/csfixer_' . uniqid('', true) . '.php';
        if (file_put_contents($this->tempFile, $before) === false) {
            $this->fail('Failed to write temporary file for test: ' . $this->tempFile);
        }

        $application = new Application();
        $application->add(new CsFixerCommand());

        $command = $application->find('kununu:cs-fixer');
        $tester  = new CommandTester($command);

        $exitCode = $tester->execute([
            'files' => [$this->tempFile],
        ]);

        self::assertSame(0, $exitCode);
        self::assertSame($after, $this->contents($this->tempFile));
    }

    public static function fixerCasesProvider(): array
    {
        $casesFile = __DIR__ . '/../_data/fixer_test_cases.php';
        if (!is_file($casesFile)) {
            self::fail(sprintf('Fixture file not found: %s', $casesFile));
        }

        $cases = require $casesFile;

        return array_map(fn ($case) => [$case['before'], $case['after']], $cases);
    }

    private function contents(string $file): string
    {
        $contents = file_get_contents($file);

        if ($contents === false) {
            $this->fail(sprintf('Failed to read file: %s', $file));
        }

        return $contents;
    }

    protected function tearDown(): void
    {
        if ($this->tempFile !== null && is_file($this->tempFile)) {
            unlink($this->tempFile);
        }

        parent::tearDown();
    }
}
