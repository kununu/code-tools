<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Factory;

use Kununu\CodeGenerator\Infrastructure\FileSystem\SymfonyFileSystemHandler;
use Kununu\CodeGenerator\Infrastructure\Generator\TwigTemplateGenerator;
use Symfony\Component\Filesystem\Filesystem;

final class TwigTemplateGeneratorFactory
{
    public static function create(
        ?Filesystem $filesystem = null,
        ?string $customTemplateDir = null,
    ): TwigTemplateGenerator {
        $filesystem = $filesystem ?? new Filesystem();
        $fileSystemHandler = new SymfonyFileSystemHandler($filesystem);

        return TwigTemplateGenerator::createDefault($fileSystemHandler, $customTemplateDir);
    }
}
