<?php

declare(strict_types=1);

// Src: https://github.com/sabbelasichon/typo3-rector/blob/main/templates/rector.php.dist

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/..',
    ])
    ->withPhpVersion(PhpVersion::PHP_81)
    ->withSets([
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
        Typo3LevelSetList::UP_TO_TYPO3_12,
    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ])
    ->withSkip([
        __DIR__ . '/../.Build/*',
        __DIR__ . '/../.ddev/*',
        __DIR__ . '/../Build/*',
        __DIR__ . '/../config/*',
        __DIR__ . '/../var/*',
    ]);
