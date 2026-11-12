<?php
declare(strict_types=1);

// What Composer's autoloader does for a project that installed phpstan/extension-installer.
spl_autoload_register(
    static function(string $class): void {
        if ($class === 'PHPStan\ExtensionInstaller\GeneratedConfig') {
            require __DIR__ . '/phpstan/extension-installer/src/GeneratedConfig.php';
        }
    }
);
