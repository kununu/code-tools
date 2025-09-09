<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Selector;

trait RegexTrait
{
    public function makeRegex(string $path, bool $file = false): string
    {
        if (str_contains($path, '*')) {
            if (str_starts_with($path, '\\')) {
                $path = substr($path, 1);
            }

            $path = str_replace('\\', '\\\\', $path);

            return '/' . str_replace('*', '.+', $path) . '/';
        }

        if ($file && !str_starts_with($path, '\\')) {
            return "\\$path";
        }

        return $path;
    }
}
