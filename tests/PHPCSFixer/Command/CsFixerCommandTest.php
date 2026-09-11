<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\PHPCSFixer\Command;

use Kununu\CodeTools\PHPCSFixer\Command\CsFixerCommand;
use PHPUnit\Framework\Attributes\DataProvider;

final class CsFixerCommandTest extends CommandTestCase
{
    private const string TEMPLATE = <<<'TEXT'
<?php
declare(strict_types=1);

TEXT;

    private ?string $tempFile = null;

    #[DataProvider('csFixerCommandDataProvider')]
    public function testCsFixerCommand(string $before, string $after): void
    {
        $this->tempFile = self::createTempFile($before);
        $exitCode = $this->tester->execute([
            'files' => [
                $this->tempFile,
            ],
        ]);

        self::assertEquals(0, $exitCode);
        self::assertContents($after, $this->tempFile);
    }

    /** @return array<string, array{before: string, after: string}> */
    public static function csFixerCommandDataProvider(): array
    {
        $casesFile = sprintf('%s/../../resources/PHPCSFixer/fixer_test_cases.php', __DIR__);
        if (!is_file($casesFile)) {
            self::fail(sprintf('Fixture file not found: %s', $casesFile));
        }

        $cases = require $casesFile;

        return array_map(
            static fn(array $case): array => ['before' => $case['before'], 'after' => $case['after']],
            $cases,
        );
    }

    public function testCsFixerCommandReturnsFailureWhenNoFilesProvided(): void
    {
        $exitCode = $this->tester->execute([]);

        self::assertEquals(CsFixerCommand::FAILURE, $exitCode);
    }

    public function testCsFixerCommandDefaultsToThePublishedTemplate(): void
    {
        $template = realpath(sprintf('%s/dist/php-cs-fixer.php.dist', dirname(__DIR__, 3)));
        if ($template === false) {
            self::fail('Published PHP CS Fixer template not found.');
        }

        $this->tempFile = self::createTempFile(self::TEMPLATE);

        $exitCode = $this->tester->execute([
            'files'        => [$this->tempFile],
            '--extra-args' => ['--dry-run'],
        ]);

        // The note is wrapped and prefixed by SymfonyStyle, so compare without whitespace or markers
        $display = (string) preg_replace('/[\s!]+/', '', $this->tester->getDisplay());

        self::assertEquals(CsFixerCommand::SUCCESS, $exitCode);
        self::assertStringContainsString($template, $display);
    }

    public function testCsFixerCommandWithExtraArgs(): void
    {
        $this->tempFile = self::createTempFile(self::TEMPLATE);

        $exitCode = $this->tester->execute([
            'files'        => [$this->tempFile],
            '--extra-args' => ['--dry-run'],
        ]);

        self::assertEquals(CsFixerCommand::SUCCESS, $exitCode);
    }

    public function testCsFixerCommandReturnsFailureWhenConfigNotFound(): void
    {
        $this->tempFile = self::createTempFile(self::TEMPLATE);

        $exitCode = $this->tester->execute([
            'files'    => [$this->tempFile],
            '--config' => '/nonexistent/path/config.php',
        ]);

        self::assertEquals(CsFixerCommand::FAILURE, $exitCode);
    }

    public function testCsFixerCommandReturnsFailureWhenProcessFails(): void
    {
        $this->tempFile = self::createTempFile(self::TEMPLATE);

        $exitCode = $this->tester->execute([
            'files'        => [$this->tempFile],
            '--extra-args' => ['--invalid-flag-xyz'],
        ]);

        self::assertEquals(CsFixerCommand::FAILURE, $exitCode);
    }

    protected function getCommand(): CsFixerCommand
    {
        return new CsFixerCommand();
    }

    protected function getCommandName(): string
    {
        return 'kununu:cs-fixer';
    }

    protected function tearDown(): void
    {
        if ($this->tempFile !== null && is_file($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    private static function createTempFile(mixed $content): string
    {
        $tempFile = sprintf('%s/csfixer_%s.php', sys_get_temp_dir(), uniqid('', true));
        if (file_put_contents($tempFile, $content) === false) {
            self::fail(sprintf('Failed to write temporary file for test: %s', $tempFile));
        }

        return $tempFile;
    }

    private static function assertContents(string $expected, string $file): void
    {
        $contents = file_get_contents($file);

        if ($contents === false) {
            self::fail(sprintf('Failed to read file: %s', $file));
        }

        self::assertEquals($expected, $contents);
    }
}
