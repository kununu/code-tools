<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Architecture;

use Closure;
use Composer\InstalledVersions;
use FilesystemIterator;
use InvalidArgumentException;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Process\Process;

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
    private const string ROOT_NAMESPACE = 'App\\';
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
            self::removeDirectory($workDirectory);
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

    private static function removeDirectory(string $directory): void
    {
        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $entry */
        foreach ($entries as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }

        rmdir($directory);
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

    private function composerFile(): string
    {
        return sprintf('%s/composer.json', $this->projectDirectory);
    }

    private function createWorkDirectory(): string
    {
        $workDirectory = sprintf('%s/architecture-check-%s', $this->temporaryDirectory, bin2hex(random_bytes(8)));

        if (!mkdir($workDirectory, 0700)) {
            throw new RuntimeException(sprintf('Could not create "%s".', $workDirectory));
        }

        return $workDirectory;
    }

    // The rules select classes by namespace and only "src" is analysed, so a project that keeps its code
    // under another namespace or directory would pass without being checked.
    private function guardProject(): void
    {
        if (!is_dir($this->sourceDirectory())) {
            throw new RuntimeException(
                sprintf('No "%s" directory found. Run this from the project directory.', $this->sourceDirectory())
            );
        }

        if ($this->rootNamespaceDirectories() !== [self::SOURCE_DIRECTORY]) {
            throw new RuntimeException(
                sprintf(
                    'The architecture rules need "%s" to be the PSR-4 namespace of "%s", and of nothing else, in "%s".',
                    self::ROOT_NAMESPACE,
                    self::SOURCE_DIRECTORY,
                    $this->composerFile()
                )
            );
        }
    }

    /** @return list<string> */
    private function rootNamespaceDirectories(): array
    {
        if (!is_file($this->composerFile())) {
            throw new RuntimeException(sprintf('No "%s" found.', $this->composerFile()));
        }

        try {
            /** @var array{autoload?: array{psr-4?: array<string, mixed>}} $composer */
            $composer = json_decode(
                (string) file_get_contents($this->composerFile()),
                true,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(sprintf('Could not read "%s".', $this->composerFile()), previous: $exception);
        }

        return array_map(
            static fn(string $directory): string => trim(preg_replace('#^\./#', '', $directory) ?? $directory, '/'),
            array_values(
                array_filter((array) ($composer['autoload']['psr-4'][self::ROOT_NAMESPACE] ?? []), is_string(...))
            )
        );
    }

    private function sourceDirectory(): string
    {
        return sprintf('%s/%s', $this->projectDirectory, self::SOURCE_DIRECTORY);
    }
}
