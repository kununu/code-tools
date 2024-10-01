<?php
declare(strict_types=1);

namespace CodeGenerator\DTO;

use InvalidArgumentException;

final class FilesDTO
{
    private array $files = [];

    public function __construct(array $files)
    {
        foreach ($files as $file) {
            if (!$file instanceof FileDTO) {
                throw new InvalidArgumentException('All elements must be instances of FileDTO.');
            }
            $this->files[strtolower($file->type)] = $file;
        }
    }

    public function getFileByType(string $type): ?FileDTO
    {
        return $this->files[strtolower($type)] ?? null;
    }

    public function getAllFiles(bool $sorted = true): array
    {
        $files = $this->files;

        if ($sorted) {
            usort($files, static fn(FileDTO $a, FileDTO $b) => strcmp($a->filePath, $b->filePath));
        }

        return $files;
    }
}
