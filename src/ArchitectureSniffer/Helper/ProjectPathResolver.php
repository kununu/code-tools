<?php
declare(strict_types=1);

namespace Kununu\CodeTools\ArchitectureSniffer\Helper;

final readonly class ProjectPathResolver
{
    public static function resolve(string $fileName): string
    {
        return self::getProjectDirectory() . "/$fileName";
    }

    private static function getProjectDirectory(): string
    {
        $directory = dirname(__DIR__);

        return explode('/services', $directory)[0] . '/services';
    }
}
