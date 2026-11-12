<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Architecture;

use JsonException;
use RuntimeException;

/**
 * The architecture rules select classes by namespace and only the source directory is analysed, so a project
 * that autoloads production code of the root namespace from anywhere else would pass without being checked.
 *
 * Only what is known to be safe is accepted, because Composer offers many ways to autoload a class.
 */
final readonly class AutoloadGuard
{
    private const string DIRECTORY_INSIDE_SOURCE_PATTERN = '#^%s(/[A-Za-z0-9_-][A-Za-z0-9_.-]*)*\z#';
    private const string ROOT_NAMESPACE = 'App\\';
    private const array SECTIONS_THAT_LOAD_NO_CODE = ['exclude-from-classmap'];
    private const string SUPPORTED_SECTION = 'psr-4';
    private const string TESTS_NAMESPACE = 'App\\Tests\\';

    /** @throws RuntimeException when the project autoloads code the rules cannot see, or this cannot be told */
    public static function guard(string $composerFile, string $sourceDirectory): void
    {
        $autoload = self::autoload($composerFile);
        $namespaces = $autoload[self::SUPPORTED_SECTION] ?? [];

        if (!is_array($namespaces)) {
            throw new RuntimeException(sprintf('Could not read the autoload section of "%s".', $composerFile));
        }

        $directoriesByNamespace = self::directoriesByNamespace($namespaces);
        $problems = [
            ...self::unsupportedSections($autoload),
            ...self::rootNamespaceElsewhere($directoriesByNamespace, $sourceDirectory),
            ...self::namespacesOutsideSource($directoriesByNamespace, $sourceDirectory),
        ];

        if ($problems !== []) {
            throw new RuntimeException(
                sprintf(
                    'The architecture rules only see "%s" as the "%s" namespace. In "%s": %s.',
                    $sourceDirectory,
                    self::ROOT_NAMESPACE,
                    $composerFile,
                    implode('; ', $problems)
                )
            );
        }
    }

    /** @return array<array-key, mixed> */
    private static function autoload(string $composerFile): array
    {
        if (!is_file($composerFile)) {
            throw new RuntimeException(sprintf('No "%s" found.', $composerFile));
        }

        try {
            $composer = json_decode((string) file_get_contents($composerFile), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(sprintf('Could not read "%s".', $composerFile), previous: $exception);
        }

        $autoload = is_array($composer) ? ($composer['autoload'] ?? []) : null;

        return is_array($autoload)
            ? $autoload
            : throw new RuntimeException(sprintf('Could not read the autoload section of "%s".', $composerFile));
    }

    // A prefix that is empty is Composer's fallback for every namespace, and Composer compares prefixes with
    // the case as written while PHP does not care about the case of a class name.
    private static function canHoldProductionCode(string $namespace): bool
    {
        if (str_starts_with($namespace, self::TESTS_NAMESPACE)) {
            return false;
        }

        return $namespace === '' || str_starts_with(strtolower($namespace), strtolower(self::ROOT_NAMESPACE));
    }

    /** @return list<string> what the entry lists, without a leading "./" or a trailing slash */
    private static function directories(mixed $entry): array
    {
        return array_map(
            static fn(mixed $directory): string => is_string($directory)
                ? rtrim(preg_replace('#^(\./)+#', '', $directory) ?? $directory, '/')
                : '',
            array_values((array) $entry)
        );
    }

    /**
     * Composer drops the separators a prefix starts with, so "\App\" and "App\" are one namespace, and a prefix
     * of only a separator is the fallback.
     *
     * @param array<array-key, mixed> $namespaces
     *
     * @return array<array-key, list<string>> keyed by prefix, which PHP turns into an integer when it is numeric
     */
    private static function directoriesByNamespace(array $namespaces): array
    {
        $directoriesByNamespace = [];
        foreach ($namespaces as $namespace => $entry) {
            $namespace = ltrim((string) $namespace, '\\');
            $directoriesByNamespace[$namespace] = [
                ...($directoriesByNamespace[$namespace] ?? []),
                ...self::directories($entry),
            ];
        }

        return $directoriesByNamespace;
    }

    // Only plain directory names are accepted below the source directory: Composer also reads "\" as a
    // separator and resolves "..", so anything else could point elsewhere.
    private static function isInsideSource(string $directory, string $sourceDirectory): bool
    {
        $pattern = sprintf(self::DIRECTORY_INSIDE_SOURCE_PATTERN, preg_quote($sourceDirectory, '#'));

        return preg_match($pattern, $directory) === 1;
    }

    /**
     * @param array<array-key, list<string>> $directoriesByNamespace
     *
     * @return list<string>
     */
    private static function namespacesOutsideSource(array $directoriesByNamespace, string $sourceDirectory): array
    {
        $problems = [];
        foreach ($directoriesByNamespace as $namespace => $directories) {
            $namespace = (string) $namespace;
            if ($namespace === self::ROOT_NAMESPACE || !self::canHoldProductionCode($namespace)) {
                continue;
            }

            foreach ($directories as $directory) {
                if (!self::isInsideSource($directory, $sourceDirectory)) {
                    $problems[] = sprintf('"%s" is loaded from "%s"', $namespace, $directory);
                }
            }
        }

        return $problems;
    }

    /**
     * @param array<array-key, list<string>> $directoriesByNamespace
     *
     * @return list<string>
     */
    private static function rootNamespaceElsewhere(array $directoriesByNamespace, string $sourceDirectory): array
    {
        return ($directoriesByNamespace[self::ROOT_NAMESPACE] ?? []) === [$sourceDirectory]
            ? []
            : [
                sprintf(
                    '"%s" must be the %s namespace of "%s" and of nothing else',
                    self::ROOT_NAMESPACE,
                    self::SUPPORTED_SECTION,
                    $sourceDirectory
                ),
            ];
    }

    /**
     * @param array<array-key, mixed> $autoload
     *
     * @return list<string>
     */
    private static function unsupportedSections(array $autoload): array
    {
        $problems = [];
        foreach ($autoload as $section => $entries) {
            $isKnown = in_array($section, [self::SUPPORTED_SECTION, ...self::SECTIONS_THAT_LOAD_NO_CODE], true);
            if (!$isKnown && $entries !== []) {
                $problems[] = sprintf('"%s" is not supported, only "%s" is', $section, self::SUPPORTED_SECTION);
            }
        }

        return $problems;
    }
}
