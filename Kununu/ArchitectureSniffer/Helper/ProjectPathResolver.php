<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Helper;

final readonly class ProjectPathResolver
{
    protected static function getProjectDirectory(): string
    {
        $directory = dirname(__DIR__);

        return explode('/services', $directory)[0] . '/services';
    }

    public static function resolve(string $fileName): string
    {
        return self::getProjectDirectory() . "/$fileName";
    }
}
