<?php
declare(strict_types=1);

namespace CodeGenerator\DTO;

final readonly class FileDTO
{
    public function __construct(
        public string $fileName,
        public string $filePath
    ) {
    }
}
