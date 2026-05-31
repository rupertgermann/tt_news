<?php

declare(strict_types=1);

// Src: https://github.com/sabbelasichon/typo3-rector/blob/main/templates/rector.php.dist

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/..',
    ])
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withSets([
        // Rector rules
        SetList::CODE_QUALITY,
        LevelSetList::UP_TO_PHP_83,

        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
        Typo3LevelSetList::UP_TO_TYPO3_14,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_DBAL_40,
    ])
    ->withImportNames(true, true, false, true)
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ])
    ->withSkip([
        Ssch\TYPO3Rector\TYPO313\v4\MigratePluginContentElementAndPluginSubtypesRector::class,
        Ssch\TYPO3Rector\CodeQuality\General\GeneralUtilityMakeInstanceToConstructorPropertyRector::class,
        __DIR__ . '/../.Build/*',
        __DIR__ . '/../.ddev/*',
        __DIR__ . '/../Build/*',
        __DIR__ . '/../config/*',
        __DIR__ . '/../var/*',
    ]);
