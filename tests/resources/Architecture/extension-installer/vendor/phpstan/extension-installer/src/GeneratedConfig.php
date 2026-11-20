<?php
declare(strict_types=1);

namespace PHPStan\ExtensionInstaller;

final class GeneratedConfig
{
    public const array EXTENSIONS = [
        'acme/excludes-everything' => [
            'install_path'            => __DIR__ . '/../../../acme/excludes-everything',
            'relative_install_path'   => '../../../acme/excludes-everything',
            'extra'                   => ['includes' => ['extension.neon']],
            'version'                 => '1.0.0',
            'phpstanVersionConstraint' => null,
        ],
    ];
    public const array NOT_INSTALLED = [];
    public const ?string PHPSTAN_VERSION_CONSTRAINT = null;
}
