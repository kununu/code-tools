<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\Architecture;

use Kununu\CodeTools\Architecture\AutoloadGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AutoloadGuardTest extends TestCase
{
    private string $composerFile;

    /** @param array<string, mixed> $autoload */
    #[DataProvider('autoloadThatOnlyLoadsSourceIsAcceptedDataProvider')]
    #[DoesNotPerformAssertions]
    public function testAutoloadThatOnlyLoadsSourceIsAccepted(array $autoload): void
    {
        $this->writeComposerFile(json_encode(['autoload' => $autoload], JSON_THROW_ON_ERROR));

        AutoloadGuard::guard($this->composerFile);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function autoloadThatOnlyLoadsSourceIsAcceptedDataProvider(): array
    {
        return [
            'source_directory'               => [['psr-4' => ['App\\' => 'src']]],
            'leading_separator'              => [['psr-4' => ['\\App\\' => 'src']]],
            'trailing_slash'                 => [['psr-4' => ['App\\' => 'src/']]],
            'leading_dot'                    => [['psr-4' => ['App\\' => './src/']]],
            'list_with_only_source'          => [['psr-4' => ['App\\' => ['src/']]]],
            'other_namespace_anywhere'       => [['psr-4' => ['App\\' => 'src', 'Migrations\\' => 'migrations']]],
            'test_namespace_anywhere'        => [['psr-4' => ['App\\' => 'src', 'App\\Tests\\Mock\\' => 'tests/Mock']]],
            'sub_namespace_inside_source'    => [['psr-4' => ['App\\' => 'src', 'App\\Legacy\\' => 'src/Legacy/']]],
            'section_that_loads_no_code'     => [['psr-4' => ['App\\' => 'src'], 'exclude-from-classmap' => ['/x/']]],
            'empty_section_of_another_kind'  => [['psr-4' => ['App\\' => 'src'], 'classmap' => []]],
        ];
    }

    #[DataProvider('autoloadThatMayLoadCodeFromElsewhereIsRejectedDataProvider')]
    public function testAutoloadThatMayLoadCodeFromElsewhereIsRejected(string $composer, string $expectedMessage): void
    {
        $this->writeComposerFile($composer);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains($expectedMessage);

        AutoloadGuard::guard($this->composerFile);
    }

    /** @return array<string, array{string, string}> */
    public static function autoloadThatMayLoadCodeFromElsewhereIsRejectedDataProvider(): array
    {
        $rootNamespace = '"App\" must be the psr-4 namespace of "src" and of nothing else';
        $autoload = static fn(array $autoload): string => json_encode(['autoload' => $autoload], JSON_THROW_ON_ERROR);
        $source = ['App\\' => 'src'];

        return [
            'no_autoload'                           => ['{}', $rootNamespace],
            'no_root_namespace'                     => [$autoload(['psr-4' => ['Acme\\' => 'src']]), $rootNamespace],
            'root_namespace_elsewhere'              => [$autoload(['psr-4' => ['App\\' => 'lib']]), $rootNamespace],
            'root_namespace_twice'                  => [
                $autoload(['psr-4' => ['App\\' => ['src', 'lib']]]),
                $rootNamespace,
            ],
            'root_namespace_absolute'               => [$autoload(['psr-4' => ['App\\' => '/src']]), $rootNamespace],
            'root_namespace_leaves_source'          => [
                $autoload(['psr-4' => ['App\\' => 'src/../lib']]),
                $rootNamespace,
            ],
            'root_namespace_not_a_path'             => [$autoload(['psr-4' => ['App\\' => [1]]]), $rootNamespace],
            'root_namespace_after_separator'        => [
                $autoload(['psr-4' => $source + ['\\App\\' => 'lib']]),
                $rootNamespace,
            ],
            'prefix_with_leading_separator' => [
                $autoload(['psr-4' => $source + ['\\App\\Controller\\' => 'lib/Controller/']]),
                '"App\Controller\" is loaded from "lib/Controller"',
            ],
            'fallback_written_as_separator' => [
                $autoload(['psr-4' => $source + ['\\' => 'lib']]),
                '"" is loaded from "lib"',
            ],
            'sub_namespace_elsewhere'      => [
                $autoload(['psr-4' => $source + ['App\\Controller\\' => 'lib/Controller/']]),
                '"App\Controller\" is loaded from "lib/Controller"',
            ],
            'sub_namespace_partly_elsewhere' => [
                $autoload(['psr-4' => $source + ['App\\Controller\\' => ['src/Controller', 'lib']]]),
                '"App\Controller\" is loaded from "lib"',
            ],
            'sub_namespace_leaves_source'  => [
                $autoload(['psr-4' => $source + ['App\\Controller\\' => 'src/../lib']]),
                '"App\Controller\" is loaded from "src/../lib"',
            ],
            'sub_namespace_leaves_source_with_backslashes' => [
                $autoload(['psr-4' => $source + ['App\\Controller\\' => 'src/x\\..\\..\\lib']]),
                '"App\Controller\" is loaded from "src/x\..\..\lib"',
            ],
            'sub_namespace_leaves_source_with_mixed_separators' => [
                $autoload(['psr-4' => $source + ['App\\Controller\\' => 'src/..\\lib']]),
                '"App\Controller\" is loaded from "src/..\lib"',
            ],
            'sub_namespace_ends_with_newline' => [
                $autoload(['psr-4' => $source + ['App\\Controller\\' => "src\n"]]),
                '"App\Controller\" is loaded from "src' . "\n" . '"',
            ],
            'fallback_ends_with_newline' => [
                $autoload(['psr-4' => $source + ['' => "src\n"]]),
                '"" is loaded from "src' . "\n" . '"',
            ],
            'numeric_prefix' => [
                $autoload(['psr-4' => $source + ['0' => 'lib', '' => 'lib']]),
                '"" is loaded from "lib"',
            ],
            'sub_namespace_absolute'       => [
                $autoload(['psr-4' => $source + ['App\\Controller\\' => '/src/Controller']]),
                '"App\Controller\" is loaded from "/src/Controller"',
            ],
            'sub_namespace_looks_like_source' => [
                $autoload(['psr-4' => $source + ['App\\Controller\\' => 'srcfoo']]),
                '"App\Controller\" is loaded from "srcfoo"',
            ],
            'namespace_in_another_case'    => [
                $autoload(['psr-4' => $source + ['app\\Controller\\' => 'lib']]),
                '"app\Controller\" is loaded from "lib"',
            ],
            'tests_namespace_in_another_case' => [
                $autoload(['psr-4' => $source + ['App\\tests\\' => 'tests']]),
                '"App\tests\" is loaded from "tests"',
            ],
            'fallback_directory'           => [
                $autoload(['psr-4' => $source + ['' => 'lib']]),
                '"" is loaded from "lib"',
            ],
            'psr0_section'                 => [
                $autoload(['psr-4' => $source, 'psr-0' => ['App' => 'lib/']]),
                '"psr-0" is not supported, only "psr-4" is',
            ],
            'classmap_section'             => [
                $autoload(['psr-4' => $source, 'classmap' => ['src/']]),
                '"classmap" is not supported, only "psr-4" is',
            ],
            'files_section'                => [
                $autoload(['psr-4' => $source, 'files' => ['src/functions.php']]),
                '"files" is not supported, only "psr-4" is',
            ],
            'namespaces_are_not_a_map'     => ['{"autoload": {"psr-4": "src"}}', 'Could not read the autoload section'],
            'autoload_is_not_a_map'        => ['{"autoload": "src"}', 'Could not read the autoload section'],
            'not_json'                     => ['{ "autoload": ', 'Could not read "'],
        ];
    }

    public function testMissingComposerFileIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('/composer.json" found.');

        AutoloadGuard::guard($this->composerFile);
    }

    protected function setUp(): void
    {
        $directory = sprintf('%s/autoload-guard-test-%s', sys_get_temp_dir(), bin2hex(random_bytes(8)));
        mkdir($directory);
        $this->composerFile = sprintf('%s/composer.json', $directory);
    }

    protected function tearDown(): void
    {
        if (is_file($this->composerFile)) {
            unlink($this->composerFile);
        }
        rmdir(dirname($this->composerFile));
    }

    private function writeComposerFile(string $content): void
    {
        file_put_contents($this->composerFile, $content);
    }
}
