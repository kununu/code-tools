<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Tests\ArchitectureSniffer\Helper;

use InvalidArgumentException;
use Kununu\CodeTools\ArchitectureSniffer\Helper\TypeChecker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TypeCheckerTest extends TestCase
{
    #[DataProvider('isArrayKeysOfStringsDataProvider')]
    public function testIsArrayKeysOfStrings(mixed $input, bool $expected): void
    {
        self::assertEquals($expected, TypeChecker::isArrayKeysOfStrings($input));
    }

    /** @return array<string, array{mixed, bool}> */
    public static function isArrayKeysOfStringsDataProvider(): array
    {
        return [
            'string_keys'           => [['a' => 1, 'b' => 2], true],
            'empty_array'           => [[], true],
            'integer_keys'          => [[1, 2, 3], false],
            'mixed_keys'            => [['a' => 1, 0 => 2], false],
            'not_an_array_string'   => ['hello', false],
            'not_an_array_int'      => [42, false],
            'not_an_array_null'     => [null, false],
        ];
    }

    #[DataProvider('isArrayOfStringsDataProvider')]
    public function testIsArrayOfStrings(mixed $input, bool $expected): void
    {
        self::assertEquals($expected, TypeChecker::isArrayOfStrings($input));
    }

    /** @return array<string, array{mixed, bool}> */
    public static function isArrayOfStringsDataProvider(): array
    {
        return [
            'all_strings'           => [['a', 'b', 'c'], true],
            'empty_array'           => [[], true],
            'contains_integer'      => [['a', 1], false],
            'contains_null'         => [['a', null], false],
            'not_an_array_string'   => ['hello', false],
            'not_an_array_int'      => [42, false],
            'not_an_array_null'     => [null, false],
        ];
    }

    public function testCastArrayOfStringsReturnsValidArray(): void
    {
        $input = ['foo', 'bar', 'baz'];

        $result = TypeChecker::castArrayOfStrings($input);

        self::assertEquals($result, $input);
    }

    public function testCastArrayOfStringsThrowsOnInvalidInput(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TypeChecker::castArrayOfStrings([1, 2, 3]);
    }

    public function testCastArrayOfStringsThrowsOnNonArray(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TypeChecker::castArrayOfStrings('not-an-array');
    }
}
