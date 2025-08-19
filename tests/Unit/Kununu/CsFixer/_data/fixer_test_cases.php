<?php
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
PHP
        ,
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

PHP
    ],

    'combine_consecutive_unsets' => [
        'before' => <<<'PHP'
<?php
$a = 1; $b = 2;
unset($a);
unset($b);
PHP
        ,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
$a = 1;
$b = 2;
unset($a, $b);

PHP
    ],

    'binary_operator_spaces' => [
        'before' => <<<'PHP'
<?php
$arr = [
    'one'=>1,
    'two'  =>2,
    'three'=> 3
];
PHP
        ,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
$arr = [
    'one'  => 1,
    'two'  => 2,
    'three'=> 3,
];

PHP
    ],

    'explicit_indirect_variable' => [
        'before' => <<<'PHP'
<?php
$name = 'foo';
$$name = 'value';
PHP
        ,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
$name = 'foo';
${$name} = 'value';

PHP
    ],

    'concat_space' => [
        'before' => <<<'PHP'
<?php
$greeting = "Hi"."There";
PHP
        ,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
$greeting = 'HiThere';

PHP
    ],

    'heredoc_to_nowdoc' => [
        'before' => <<<'PHP'
<?php
$doc = <<<EOS
This heredoc has no variables and could be a nowdoc.
EOS;
PHP
        ,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
$doc = <<<'EOS'
This heredoc has no variables and could be a nowdoc.
EOS;

PHP
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
PHP
        ,
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

PHP
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
PHP
        ,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
class FnSpacing
{
    public function doStuff()
    {
        $fn = function($x) {
            return $x;
        };

        return $fn(1);
    }
}

PHP
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
PHP
        ,
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

PHP
    ],

    'ternary_to_null_coalescing' => [
        'before' => <<<'PHP'
<?php
function maybe($x)
{
    return isset($x) ? $x : null;
}
PHP
        ,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
function maybe($x)
{
    return $x ?? null;
}

PHP
    ],

    'return_assignment' => [
        'before' => <<<'PHP'
<?php
function assignThenReturn()
{
    $a = 5;
    return $a;
}
PHP
        ,
        'after' => <<<'PHP'
<?php
declare(strict_types=1);
function assignThenReturn()
{
    return 5;
}

PHP
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
PHP
        ,
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

PHP
    ],
];
