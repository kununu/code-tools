<?php
declare(strict_types=1);

namespace Kununu;

use Exception;
use PHP_CodeSniffer\Util\Tokens;

class Fixme
{
    public const string FIRST = 'first';
    private const string SECOND = 'second';

    protected string $propertyOne;
    private readonly int $propertyTwo;

    public function __invoke(string $token): void
    {
        try {
            $query = Tokens::tokenName($token);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function someMethod(): void
    {
        $this->propertyOne = 'value';
    }
}

class ExtraBlankAfterConst
{
    public const string A = 'a';


public function doSomething(): void
    {
    }
}

class ExtraBlankAfterProp
{
    private readonly string $prop;


public function doSomething(): void
    {
    }
}

class PropertyThenClose
{
    protected string $onlyProperty;
}

class ConstantThenClose
{
    public const string ONLY = 'only';
}

class NoPropertiesOrConstants
{
    public function firstMethod(): void
    {
    }
}
