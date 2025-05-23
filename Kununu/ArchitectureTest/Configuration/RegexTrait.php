<?php
declare(strict_types=1);

namespace Kununu\ArchitectureTest\Configuration;

trait RegexTrait
{
    public function makeRegex(string $path): string
    {
        if (str_contains($path, '*')) {
            $path = str_replace('\\', '\\\\', $path);

            return '/' . str_replace('*', '.+', $path) . '/';
        }

        return $path;
    }
}
