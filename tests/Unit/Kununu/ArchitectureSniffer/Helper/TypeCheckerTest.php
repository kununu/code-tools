<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\ArchitectureSniffer\Helper;

use InvalidArgumentException;
use Kununu\ArchitectureSniffer\Helper\TypeChecker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TypeCheckerTest extends TestCase
{
    #[DataProvider('isArrayKeysOfStringsProvider')]
    public function testIsArrayKeysOfStrings(mixed $input, bool $expected): void
    {
        self::assertSame($expected, TypeChecker::isArrayKeysOfStrings($input));
    }

    public static function isArrayKeysOfStringsProvider(): array
    {
        return [
            'string keys'           => [['a' => 1, 'b' => 2], true],
            'empty array'           => [[], true],
            'integer keys'          => [[1, 2, 3], false],
            'mixed keys'            => [['a' => 1, 0 => 2], false],
            'not an array (string)' => ['hello', false],
            'not an array (int)'    => [42, false],
            'not an array (null)'   => [null, false],
        ];
    }

    #[DataProvider('isArrayOfStringsProvider')]
    public function testIsArrayOfStrings(mixed $input, bool $expected): void
    {
        self::assertSame($expected, TypeChecker::isArrayOfStrings($input));
    }

    public static function isArrayOfStringsProvider(): array
    {
        return [
            'all strings'           => [['a', 'b', 'c'], true],
            'empty array'           => [[], true],
            'contains integer'      => [['a', 1], false],
            'contains null'         => [['a', null], false],
            'not an array (string)' => ['hello', false],
            'not an array (int)'    => [42, false],
            'not an array (null)'   => [null, false],
        ];
    }

    public function testCastArrayOfStringsReturnsValidArray(): void
    {
        $input = ['foo', 'bar', 'baz'];

        $result = TypeChecker::castArrayOfStrings($input);

        self::assertSame($input, $result);
    }

    public function testCastArrayOfStringsThrowsOnInvalidInput(): void
    {
        self::expectException(InvalidArgumentException::class);

        TypeChecker::castArrayOfStrings([1, 2, 3]);
    }

    public function testCastArrayOfStringsThrowsOnNonArray(): void
    {
        self::expectException(InvalidArgumentException::class);

        TypeChecker::castArrayOfStrings('not-an-array');
    }
}
