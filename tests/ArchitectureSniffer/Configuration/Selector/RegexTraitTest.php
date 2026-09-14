<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Configuration\Selector;

use Kununu\CodeTools\ArchitectureSniffer\Configuration\Selector\RegexTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RegexTraitTest extends TestCase
{
    use RegexTrait;

    #[DataProvider('makeRegexDataProvider')]
    public function testMakeRegex(string $path, bool $file, string $expected): void
    {
        self::assertEquals($expected, $this->makeRegex($path, $file));
    }

    /** @return array<string, array{string, bool, string}> */
    public static function makeRegexDataProvider(): array
    {
        return [
            'plain_class_path'                    => ['App\\Service\\MyService', false, 'App\\Service\\MyService'],
            'wildcard_converts_to_regex'          => ['App\\*\\MyService', false, '/App\\\\.+\\\\MyService/'],
            'wildcard_with_leading_backslash'     => ['\\App\\*\\MyService', false, '/App\\\\.+\\\\MyService/'],
            'file_mode_prepends_backslash'        => ['App\\Service\\MyService', true, '\\App\\Service\\MyService'],
            'file_mode_with_leading_backslash'    => ['\\App\\Service\\MyService', true, '\\App\\Service\\MyService'],
            'wildcard_ignores_file_mode'          => ['App\\*', false, '/App\\\\.+/'],
            'namespace_path_unchanged'            => ['App\\Repository\\', false, 'App\\Repository\\'],
        ];
    }
}
