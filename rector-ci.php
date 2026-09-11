<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPhpSets()
    ->withAttributesSets(phpunit: true)
    ->withComposerBased(phpunit: true)
    ->withSkip([
        __DIR__ . '/tests/bootstrap.php',
        __DIR__ . '/tests/resources/PHPCodeSniffer',
        __DIR__ . '/rector-ci.php',
    ])
    ->withImportNames();
