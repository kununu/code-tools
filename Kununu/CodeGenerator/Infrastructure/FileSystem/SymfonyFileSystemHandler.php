<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Infrastructure\FileSystem;

use Kununu\CodeGenerator\Domain\Service\FileSystem\FileSystemHandlerInterface;
use Symfony\Component\Filesystem\Filesystem;

final readonly class SymfonyFileSystemHandler implements FileSystemHandlerInterface
{
    public function __construct(private Filesystem $filesystem)
    {
    }

    public function exists(string $path): bool
    {
        return $this->filesystem->exists($path);
    }

    public function createDirectory(string $path, int $mode = 0755): void
    {
        $this->filesystem->mkdir($path, $mode);
    }

    public function writeFile(string $path, string $content): void
    {
        $this->filesystem->dumpFile($path, $content);
    }
}
