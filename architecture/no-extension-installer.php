<?php
declare(strict_types=1);

namespace PHPStan\ExtensionInstaller;

// PHPStan loads every extension that phpstan/extension-installer registered in the project, on top of the
// configuration it is given. An extension can bring rules and parameters such as excludePaths, so the project
// could change this check through one. Declaring the class here first, with nothing registered, keeps them out.
//
// This relies on PHPStan reading the registered extensions from this class (CommandHelper::begin()). The
// "extension-installer" fixture project in tests/ fails if a PHPStan release stops doing so.
final class GeneratedConfig
{
    public const array EXTENSIONS = [];
    public const array NOT_INSTALLED = [];
    public const ?string PHPSTAN_VERSION_CONSTRAINT = null;
}
