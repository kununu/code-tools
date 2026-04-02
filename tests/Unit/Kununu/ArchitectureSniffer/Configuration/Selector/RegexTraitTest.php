<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer\Configuration\Selector;

use Kununu\ArchitectureSniffer\Configuration\Selector\RegexTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RegexTraitTest extends TestCase
{
    use RegexTrait;

    #[DataProvider('makeRegexProvider')]
    public function testMakeRegex(string $path, bool $file, string $expected): void
    {
        self::assertSame($expected, $this->makeRegex($path, $file));
    }

    public static function makeRegexProvider(): array
    {
        return [
            'plain class path'                    => ['App\\Service\\MyService', false, 'App\\Service\\MyService'],
            'wildcard converts to regex'          => ['App\\*\\MyService', false, '/App\\\\.+\\\\MyService/'],
            'wildcard with leading backslash'     => ['\\App\\*\\MyService', false, '/App\\\\.+\\\\MyService/'],
            'file mode prepends backslash'        => ['App\\Service\\MyService', true, '\\App\\Service\\MyService'],
            'file mode with leading backslash'    => ['\\App\\Service\\MyService', true, '\\App\\Service\\MyService'],
            'wildcard ignores file mode'          => ['App\\*', false, '/App\\\\.+/'],
            'namespace path unchanged'            => ['App\\Repository\\', false, 'App\\Repository\\'],
        ];
    }
}
