<?php

declare(strict_types=1);

namespace Tests\Unit\Kununu\CodeGenerator\Application\Service;

use Kununu\CodeGenerator\Domain\Exception\ParserException;
use Kununu\CodeGenerator\Domain\Service\OpenApiParserInterface;

final class TestOpenApiParser implements OpenApiParserInterface
{
    private bool $parseFileWasCalled = false;
    private array $operations = [];
    private array $operationsById = [];
    private array $parseFileResult = [];
    private bool $skipFileExistsCheck = false;

    public function parseFile(string $openApiFilePath): array
    {
        $this->parseFileWasCalled = true;

        if (!$this->skipFileExistsCheck && !file_exists($openApiFilePath)) {
            throw new ParserException("File not found: {$openApiFilePath}");
        }

        return $this->parseFileResult;
    }

    public function listOperations(): array
    {
        if (!$this->parseFileWasCalled) {
            throw new ParserException('OpenAPI specification not loaded. Call parseFile() first.');
        }

        return $this->operations;
    }

    public function getOperationById(string $operationId): array
    {
        if (!$this->parseFileWasCalled) {
            throw new ParserException('OpenAPI specification not loaded. Call parseFile() first.');
        }

        if (!isset($this->operationsById[$operationId])) {
            throw new ParserException("Operation with ID '{$operationId}' not found");
        }

        return $this->operationsById[$operationId];
    }

    public function setParseFileResult(array $result): void
    {
        $this->parseFileResult = $result;
    }

    public function setOperations(array $operations): void
    {
        $this->operations = $operations;

        // Index operations by ID for quick lookup
        foreach ($operations as $operation) {
            if (isset($operation['id'])) {
                $this->operationsById[$operation['id']] = $operation;
            }
        }
    }

    public function setParseFileCalled(bool $value = true): void
    {
        $this->parseFileWasCalled = $value;
    }

    public function setSkipFileExistsCheck(bool $value = true): void
    {
        $this->skipFileExistsCheck = $value;
    }
}
