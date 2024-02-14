<?php

declare(strict_types=1);

// Src: https://github.com/sabbelasichon/typo3-rector/blob/main/templates/rector.php.dist

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/..',
    ])
    ->withPhpSets(
        true
    )
    ->withSets([
        Typo3LevelSetList::UP_TO_TYPO3_12,
    ])
    ->withPhpVersion(PhpVersion::PHP_81)
    ->withSkip([
        __DIR__ . '/../.Build/*',
        __DIR__ . '/../.ddev/*',
        __DIR__ . '/../Build/*',
        __DIR__ . '/../config/*',
        __DIR__ . '/../var/*',
    ]);
