<?php
declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

return $config
    ->addPathToExclude(__DIR__ . '/tests')
    ->ignoreErrorsOnPath(__DIR__ . '/src/PHPCodeSniffer/Kununu/Sniffs/', [ErrorType::UNKNOWN_CLASS])
    ->ignoreErrorsOnPackages(
        [
            'friendsofphp/php-cs-fixer',
            'jetbrains/phpstorm-attributes',
            'phpstan/phpstan',
            'phpstan/phpstan-phpunit',
            'psalm/plugin-phpunit',
            'psalm/plugin-symfony',
            'rector/rector',
            'squizlabs/php_codesniffer',
            'vimeo/psalm',
        ],
        [ErrorType::UNUSED_DEPENDENCY]
    );
