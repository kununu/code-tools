<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer;

use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

final readonly class DirectoryFinder
{
    private const string ARCHITECTURE_DEFINITION_FILE = '/arch_definition.yaml';

    /**
     * @return array<string, mixed>
     */
    public static function getArchitectureDefinition(): array
    {
        $filePath = self::getArchitectureDefinitionFile();

        if (!file_exists($filePath)) {
            throw new InvalidArgumentException(
                'ArchitectureSniffer definition file not found, please create it at ' . $filePath
            );
        }

        return Yaml::parseFile($filePath);
    }

    public static function getProjectDirectory(): string
    {
        $directory = dirname(__DIR__);

        return explode('/services', $directory)[0] . '/services';
    }

    public static function getArchitectureDefinitionFile(): string
    {
        return self::getProjectDirectory() . self::ARCHITECTURE_DEFINITION_FILE;
    }
}
