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
    public function testItFixesBrokenPhpFileAccordingToConfig(string $before, string $after): void
    {
        // Create a temp file with .php extension to avoid scanning/extension pitfalls
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

        $output = $tester->getDisplay();

        self::assertSame(0, $exitCode, 'Expected command to succeed');
        self::assertStringContainsString(
            'PHP CS Fixer completed successfully.',
            $output,
            'Expected success message in output',
        );

        $actual = $this->contents($this->tempFile);

        self::assertSame(
            $after,
            $actual,
            "Fixed file did not match expected output.\n\nCommand output:\n" . $output
        );
    }

    public static function fixerCasesProvider(): array
    {
        $casesFile = __DIR__ . '/../_data/fixer_test_cases.php';
        if (!is_file($casesFile)) {
            self::fail(sprintf('Fixture file not found: %s', $casesFile));
        }

        $cases = require $casesFile;

        $provider = [];
        foreach ($cases as $name => $case) {
            // each case is expected to be ['before' => '...', 'after' => '...']
            $provider[$name] = [
                $case['before'],
                $case['after'],
            ];
        }

        return $provider;
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
