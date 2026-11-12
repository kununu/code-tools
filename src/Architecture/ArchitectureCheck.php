<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Architecture;

use Closure;
use Composer\InstalledVersions;
use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Process\Process;
use UnexpectedValueException;

final readonly class ArchitectureCheck
{
    private const array OPTION_VALUE_PATTERNS = [
        '--error-format' => '/^[a-zA-Z]+$/',
        '--memory-limit' => '/^(-1|\d+[KMG]?)$/i',
    ];
    // Read by architecture/common.neon to include the PHPAT extension from wherever Composer installed it
    private const string PHPAT_EXTENSION_VARIABLE = 'ARCHITECTURE_CHECK_PHPAT_EXTENSION';
    // Read by architecture/common.neon as PHPStan's tmpDir: a directory per run, so no result is reused
    private const string PHPSTAN_TEMPORARY_DIRECTORY_VARIABLE = 'ARCHITECTURE_CHECK_TMP_DIR';
    private const string SOURCE_DIRECTORY = 'src';
    // Where PHP puts the temporary files PHPStan creates outside its tmpDir, unless php.ini sets sys_temp_dir
    private const string TEMPORARY_DIRECTORY_VARIABLE = 'TMPDIR';
    private const string WITHOUT_EXTENSION_INSTALLER = 'no-extension-installer.php';

    public function __construct(
        private string $projectDirectory,
        private string $phpstanBinary,
        private string $phpatExtension,
        private string $configDirectory,
        private string $temporaryDirectory,
        private Closure $writeError,
    ) {
    }

    /** @param Closure(string): void $writeError receives what is not part of the report, e.g. PHPStan's notices */
    public static function create(string $projectDirectory, string $temporaryDirectory, Closure $writeError): self
    {
        return new self(
            $projectDirectory,
            self::installPath('phpstan/phpstan') . '/phpstan',
            self::installPath('phpat/phpat') . '/extension.neon',
            dirname(__DIR__, 2) . '/architecture',
            $temporaryDirectory,
            $writeError
        );
    }

    /**
     * @param list<string> $options
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function run(array $options): int
    {
        self::guardOptions($options);
        $this->guardProject();

        $rules = sprintf(
            '%s/%s',
            $this->configDirectory,
            self::configFile(Layout::fromSourceDirectory($this->sourceDirectory()))
        );
        ($this->writeError)(sprintf('Checking %s against %s%s', $this->sourceDirectory(), $rules, PHP_EOL));

        $workDirectory = $this->createWorkDirectory();

        try {
            return $this->analyse($workDirectory, $rules, $options);
        } finally {
            $this->removeWorkDirectory($workDirectory);
        }
    }

    private static function configFile(Layout $layout): string
    {
        return match ($layout) {
            Layout::Cqrs => 'cqrs.neon',
            Layout::Hexagonal,
            Layout::ServiceLayered,
            Layout::Unknown => 'common.neon',
        };
    }

    /** @param list<string> $options */
    private static function guardOptions(array $options): void
    {
        foreach ($options as $option) {
            [$name, $value] = [...explode('=', $option, 2), ''];
            $valuePattern = self::OPTION_VALUE_PATTERNS[$name] ?? null;

            if ($valuePattern === null || preg_match($valuePattern, $value) !== 1) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Unsupported option "%s". Use: %s.',
                        $option,
                        implode(
                            ' ',
                            array_map(
                                static fn(string $name): string => sprintf('%s=<value>', $name),
                                array_keys(self::OPTION_VALUE_PATTERNS)
                            )
                        )
                    )
                );
            }
        }
    }

    private static function installPath(string $package): string
    {
        return InstalledVersions::getInstallPath($package)
            ?? throw new RuntimeException(sprintf('Package "%s" is not installed.', $package));
    }

    // Failures are silenced because a PHP warning would be printed into the report; the caller checks the outcome.
    private static function removeDirectory(string $directory): void
    {
        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $entry */
        foreach ($entries as $entry) {
            $entry->isDir() ? @rmdir($entry->getPathname()) : @unlink($entry->getPathname());
        }

        @rmdir($directory);
    }

    /** @param list<string> $options */
    private function analyse(string $workDirectory, string $rules, array $options): int
    {
        $process = new Process(
            [
                PHP_BINARY,
                $this->phpstanBinary,
                'analyse',
                sprintf('--configuration=%s', $rules),
                sprintf('--autoload-file=%s/%s', $this->configDirectory, self::WITHOUT_EXTENSION_INSTALLER),
                '--no-progress',
                ...$options,
                $this->sourceDirectory(),
            ],
            $this->projectDirectory,
            [
                self::PHPAT_EXTENSION_VARIABLE             => $this->phpatExtension,
                self::PHPSTAN_TEMPORARY_DIRECTORY_VARIABLE => sprintf('%s/phpstan', $workDirectory),
                self::TEMPORARY_DIRECTORY_VARIABLE         => $workDirectory,
            ]
        );
        $process->setTimeout(null);

        return $process->run(
            function(string $type, string $buffer): void {
                if ($type === Process::ERR) {
                    ($this->writeError)($buffer);

                    return;
                }

                echo $buffer;
            }
        );
    }

    private function createWorkDirectory(): string
    {
        $workDirectory = sprintf('%s/architecture-check-%s', $this->temporaryDirectory, bin2hex(random_bytes(8)));

        if (!mkdir($workDirectory, 0700)) {
            throw new RuntimeException(sprintf('Could not create "%s".', $workDirectory));
        }

        return $workDirectory;
    }

    private function guardProject(): void
    {
        if (!is_dir($this->sourceDirectory())) {
            throw new RuntimeException(
                sprintf('No "%s" directory found. Run this from the project directory.', $this->sourceDirectory())
            );
        }

        AutoloadGuard::guard(sprintf('%s/composer.json', $this->projectDirectory), self::SOURCE_DIRECTORY);
    }

    // The run already has its result by now, so a failed clean-up is reported and does not change it.
    private function removeWorkDirectory(string $workDirectory): void
    {
        try {
            self::removeDirectory($workDirectory);
        } catch (UnexpectedValueException) {
            // An entry that cannot be read. What is left is reported below, like any other entry that stays.
        }

        if (is_dir($workDirectory)) {
            ($this->writeError)(sprintf('Could not remove "%s".%s', $workDirectory, PHP_EOL));
        }
    }

    private function sourceDirectory(): string
    {
        return sprintf('%s/%s', $this->projectDirectory, self::SOURCE_DIRECTORY);
    }
}
