<?php
declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

return $config
    ->addPathToExclude(__DIR__ . '/tests')
    ->ignoreErrorsOnPath(__DIR__ . '/Kununu/Sniffs/', [ErrorType::UNKNOWN_CLASS])
    ->ignoreErrorsOnExtensions(
        [
            'ext-mbstring',
            'ext-tokenizer',
        ],
        [ErrorType::SHADOW_DEPENDENCY]
    )
    ->ignoreErrorsOnPackages(
        [
            'friendsofphp/php-cs-fixer',
            'phpstan/phpstan',
            'rector/rector',
            'squizlabs/php_codesniffer',
        ],
        [ErrorType::UNUSED_DEPENDENCY]
    );
