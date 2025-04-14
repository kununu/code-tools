<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Domain\Service\FileSystem;

interface FileSystemHandlerInterface
{
    /**
     * Checks if a file or directory exists
     */
    public function exists(string $path): bool;

    /**
     * Creates a directory recursively
     */
    public function createDirectory(string $path, int $mode = 0755): void;

    /**
     * Writes content to a file
     */
    public function writeFile(string $path, string $content): void;
}
