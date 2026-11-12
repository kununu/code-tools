<?php
declare(strict_types=1);

namespace Kununu\CodeTools\Architecture;

enum Layout
{
    case Cqrs;
    case Hexagonal;
    case ServiceLayered;
    case Unknown;

    public static function fromSourceDirectory(string $sourceDirectory): self
    {
        return match (true) {
            is_dir("$sourceDirectory/UseCase")     => self::Cqrs,
            is_dir("$sourceDirectory/Application") => self::Hexagonal,
            is_dir("$sourceDirectory/Service")     => self::ServiceLayered,
            default                                => self::Unknown,
        };
    }
}
