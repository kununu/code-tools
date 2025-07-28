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
