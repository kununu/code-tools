<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Domain\Service;

interface OpenApiParserInterface
{
    public function parseFile(string $filePath): array;

    public function listOperations(): array;

    public function getOperationById(string $operationId): array;
}
