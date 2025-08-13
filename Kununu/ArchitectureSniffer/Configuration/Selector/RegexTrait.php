<?php
declare(strict_types=1);

namespace Kununu\ArchitectureSniffer\Configuration\Selector;

trait RegexTrait
{
    public function makeRegex(string $path): string
    {
        if (str_contains($path, '*')) {
            if (str_starts_with($path, '\\')) {
                $path = substr($path, 1);
            }

            $path = str_replace('\\', '\\\\', $path);

            return '/' . str_replace('*', '.+', $path) . '/';
        }

        return $path;
    }
}
