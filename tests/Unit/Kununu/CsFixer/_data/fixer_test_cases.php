<?php
declare(strict_types=1);

return [
    'declare_strict_types' => [
        'before' => <<<'PHP'
<?php
namespace Tests\Unit\Kununu\CsFixer\_data;

final class DeclaresMissing
{
    public function foo()
    {
        return 1;
    }
}
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CsFixer\_data;

final class DeclaresMissing
{
    public function foo()
    {
        return 1;
    }
}

PHP,
    ],

    'combine_consecutive_unsets' => [
        'before' => <<<'PHP'
<?php
$a = 1; $b = 2;
unset($a);
unset($b);
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
$a = 1;
$b = 2;
unset($a, $b);

PHP,
    ],

    'binary_operator_spaces' => [
        'before' => <<<'PHP'
<?php
$arr = [
    'one'=>1,
    'two'  =>2,
    'three'=> 3
];
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
$arr = [
    'one'  => 1,
    'two'  => 2,
    'three'=> 3,
];

PHP,
    ],

    'explicit_indirect_variable' => [
        'before' => <<<'PHP'
<?php
$name = 'foo';
$$name = 'value';
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
$name = 'foo';
${$name} = 'value';

PHP,
    ],

    'concat_space' => [
        'before' => <<<'PHP'
<?php
$greeting = "Hi"."There";
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
$greeting = 'HiThere';

PHP,
    ],

    'heredoc_to_nowdoc' => [
        'before' => <<<'PHP'
<?php
$doc = <<<EOS
This heredoc has no variables and could be a nowdoc.
EOS;
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
$doc = <<<'EOS'
This heredoc has no variables and could be a nowdoc.
EOS;

PHP,
    ],

    'global_namespace_import' => [
        'before' => <<<'PHP'
<?php
namespace Tests\Unit\Kununu\CsFixer\_data;

final class UseGlobalWithoutImport
{
    public function now(): void
    {
        $dt = new \DateTimeImmutable();
        echo $dt->format('Y');
    }
}
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);

namespace Tests\Unit\Kununu\CsFixer\_data;

use DateTimeImmutable;

final class UseGlobalWithoutImport
{
    public function now(): void
    {
        $dt = new DateTimeImmutable();
        echo $dt->format('Y');
    }
}

PHP,
    ],

    'function_declaration_spacing' => [
        'before' => <<<'PHP'
<?php
class FnSpacing
{
    public function doStuff ()
    {
        $fn = function ($x) {
            return $x;
        };

        return $fn(1);
    }
}
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
class FnSpacing
{
    public function doStuff()
    {
        $fn = static function($x) {
            return $x;
        };

        return $fn(1);
    }
}

PHP,
    ],

    'no_useless_else_and_no_useless_return' => [
        'before' => <<<'PHP'
<?php
class UselessElse
{
    public function check(): bool
    {
        if ($this->isReady()) {
            return true;
        } else {
            return false;
        }

        $foo = 42;
        return $foo;

        return;
    }

    private function isReady(): bool
    {
        return static::$count > 0;
    }
}
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
class UselessElse
{
    public function check(): bool
    {
        if ($this->isReady()) {
            return true;
        }

        return false;

        return 42;
    }

    private function isReady(): bool
    {
        return static::$count > 0;
    }
}

PHP,
    ],

    'ternary_to_null_coalescing' => [
        'before' => <<<'PHP'
<?php
function maybe($x)
{
    return isset($x) ? $x : null;
}
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
function maybe($x)
{
    return $x ?? null;
}

PHP,
    ],

    'return_assignment' => [
        'before' => <<<'PHP'
<?php
function assignThenReturn()
{
    $a = 5;
    return $a;
}
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
function assignThenReturn()
{
    return 5;
}

PHP,
    ],

    'self_static_accessor' => [
        'before' => <<<'PHP'
<?php
final class SelfStatic
{
    private static $count = 0;

    public function isNonZero(): bool
    {
        return static::$count > 0;
    }
}
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
final class SelfStatic
{
    private static $count = 0;

    public function isNonZero(): bool
    {
        return self::$count > 0;
    }
}

PHP,
    ],

    'void_return' => [
        'before' => <<<'PHP'
<?php
class VoidExample
{
    public function doSomething()
    {
        echo "hello";
    }
}
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
class VoidExample
{
    public function doSomething(): void
    {
        echo 'hello';
    }
}

PHP,
    ],

    'yoda_style' => [
        'before' => <<<'PHP'
<?php
function check($a)
{
    if (42 === $a) {
        return true;
    }

    if ('bar' != $a) {
        return false;
    }
}
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
function check($a)
{
    if (42 === $a) {
        return true;
    }

    if ('bar' != $a) {
        return false;
    }
}

PHP,
    ],

    'no_extra_blank_lines' => [
        'before' => <<<'PHP'
<?php
namespace Foo;

use Bar;


final class ExtraLines
{


    public function test()
    {

        return 1;
    }
}
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);

namespace Foo;

final class ExtraLines
{
    public function test()
    {
        return 1;
    }
}

PHP,
    ],

    'native_function_invocation_noop' => [
        'before' => <<<'PHP'
<?php
namespace Foo;

$result = strlen("abc");
$now = time();
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);

namespace Foo;

$result = strlen('abc');
$now = time();

PHP,
    ],

    'single_quote_non_interpolated' => [
        'before' => <<<'PHP'
<?php
$message = "simple string";
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
$message = 'simple string';

PHP,
    ],

    'single_quote_interpolated_no_change' => [
        'before' => <<<'PHP'
<?php
$name = 'Name';
$greeting = "Hello, $name!";
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
$name = 'Name';
$greeting = "Hello, $name!";

PHP,
    ],

    'trailing_comma_in_multiline' => [
        'before' => <<<'PHP'
<?php
$list = [
    'a',
    'b'
];
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
$list = [
    'a',
    'b',
];

PHP,
    ],

    'unused_imports_removed_simple' => [
        'before' => <<<'PHP'
<?php
namespace Acme;

use Foo\Used;
use Foo\Unused;

final class Example
{
    public function test(): void
    {
        new Used();
    }
}
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);

namespace Acme;

use Foo\Used;

final class Example
{
    public function test(): void
    {
        new Used();
    }
}

PHP,
    ],

    'no_superfluous_phpdoc_tags_noop' => [
        'before' => <<<'PHP'
<?php
/**
 * Class Example
 *
 * @package Foo
 * @author Someone
 */
class Example {}
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
/**
 * Class Example
 *
 * @author Someone
 */
class Example
{
}

PHP,
    ],

    'blank_line_after_opening_tag_noop' => [
        'before' => <<<'PHP'
<?php
/** comment directly after open tag */
class OpenTag {}
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
/** comment directly after open tag */
class OpenTag
{
}

PHP,
    ],

    'self_accessor_noop' => [
        'before' => <<<'PHP'
<?php
class SelfAcc
{
    private $count = 0;
    public function inc(): void
    {
        static::$count++;
    }
}
PHP,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
class SelfAcc
{
    private $count = 0;

    public function inc(): void
    {
        ++static::$count;
    }
}

PHP,
    ],
];
