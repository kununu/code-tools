<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\Architecture;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

final class ArchitectureCheckTest extends TestCase
{
    use RunsArchitectureCheck;

    private const string BINARY = __DIR__ . '/../../bin/architecture-check';
    private const array NOT_FINAL_CONTROLLER = [
        'testControllersAreFinal' => ['src/Controller/NotFinalController.php' => ['be final']],
    ];

    private string $temporaryDirectory;

    public function testProjectWithoutViolationsPasses(): void
    {
        [$exitCode] = self::runCheck('clean', ['--error-format=json']);

        self::assertEquals(0, $exitCode);
    }

    public function testProjectWithViolationsFails(): void
    {
        [$exitCode] = self::runCheck('service-layered', ['--error-format=json']);

        self::assertEquals(1, $exitCode);
    }

    public function testProjectThatIsNotCqrsIsOnlyCheckedAgainstTheCommonRules(): void
    {
        self::assertEquals(self::NOT_FINAL_CONTROLLER, self::reportedViolations('service-layered'));
    }

    public function testExtensionsInstalledInTheProjectTakeNoPart(): void
    {
        self::assertEquals(self::NOT_FINAL_CONTROLLER, self::reportedViolations('extension-installer'));
    }

    #[DataProvider('rulesInUseAreAnnouncedDataProvider')]
    public function testRulesInUseAreAnnounced(string $project, string $expectedConfig): void
    {
        [, , $errorOutput] = self::runCheck($project, ['--error-format=json']);

        self::assertStringContainsString(
            sprintf('Checking %s/src against ', self::fixtureProject($project)),
            $errorOutput
        );
        self::assertStringContainsString(sprintf('/architecture/%s%s', $expectedConfig, PHP_EOL), $errorOutput);
    }

    /** @return array<string, array{string, string}> */
    public static function rulesInUseAreAnnouncedDataProvider(): array
    {
        return [
            'cqrs'            => ['cqrs', 'cqrs.neon'],
            'hexagonal'       => ['hexagonal', 'common.neon'],
            'service_layered' => ['service-layered', 'common.neon'],
            'unknown'         => ['clean', 'common.neon'],
        ];
    }

    public function testViolationsAreReportedInTheRequestedFormat(): void
    {
        [, $output] = self::runCheck('service-layered', ['--error-format=github']);

        self::assertStringContainsString('::error file=src/Controller/NotFinalController.php,line=6,', $output);
    }

    public function testWorkDirectoryIsRemoved(): void
    {
        self::runCheckIn($this->temporaryDirectory, 'service-layered', ['--error-format=json']);

        self::assertEquals([], glob($this->temporaryDirectory . '/*'));
    }

    #[DataProvider('unsupportedOptionIsRejectedDataProvider')]
    public function testUnsupportedOptionIsRejected(string $option): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(sprintf('Unsupported option "%s"', $option));

        self::runCheck('clean', [$option]);
    }

    /** @return array<string, array{string}> */
    public static function unsupportedOptionIsRejectedDataProvider(): array
    {
        return [
            'configuration'               => ['--configuration=phpstan.neon'],
            'path'                        => ['tests'],
            'error_format_without_value'  => ['--error-format'],
            'error_format_with_bad_value' => ['--error-format=../x'],
            'memory_limit_without_value'  => ['--memory-limit='],
            'memory_limit_with_bad_value' => ['--memory-limit=lots'],
        ];
    }

    #[DataProvider('projectTheRulesCannotCheckIsRejectedDataProvider')]
    public function testProjectTheRulesCannotCheckIsRejected(string $project, string $expectedMessage): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains($expectedMessage);

        self::runCheck($project, []);
    }

    /** @return array<string, array{string, string}> */
    public static function projectTheRulesCannotCheckIsRejectedDataProvider(): array
    {
        return [
            'no_source_directory'          => ['does-not-exist', 'Run this from the project directory.'],
            'no_composer_file'             => ['no-composer-file', '/composer.json" found.'],
            'unreadable_composer_file'     => ['invalid-composer-file', 'Could not read'],
            'source_is_not_in_namespace'   => ['other-namespace', 'need "App\" to be the PSR-4 namespace of "src"'],
            'namespace_has_a_second_root'  => ['second-source-root', 'and of nothing else'],
        ];
    }

    public function testBinaryChecksTheProjectInTheCurrentDirectory(): void
    {
        $process = new Process(
            [PHP_BINARY, self::BINARY, '--error-format=github'],
            self::fixtureProject('service-layered')
        );

        self::assertEquals(1, $process->run());
        self::assertStringContainsString('::error file=src/Controller/NotFinalController.php', $process->getOutput());
        self::assertStringContainsString('/architecture/common.neon', $process->getErrorOutput());
    }

    public function testBinaryReportsThatItCouldNotRun(): void
    {
        $process = new Process([PHP_BINARY, self::BINARY, '--level=0'], self::fixtureProject('clean'));

        self::assertEquals(2, $process->run());
        self::assertStringContainsString('Unsupported option "--level=0"', $process->getErrorOutput());
    }

    protected function setUp(): void
    {
        $this->temporaryDirectory = sprintf(
            '%s/architecture-check-test-%s',
            sys_get_temp_dir(),
            bin2hex(random_bytes(8))
        );
        mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        rmdir($this->temporaryDirectory);
    }
}
