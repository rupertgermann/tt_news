<?php

declare(strict_types=1);

// Src: https://github.com/sabbelasichon/typo3-rector/blob/v1.6.0/templates/rector.php.dist

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        Typo3LevelSetList::UP_TO_TYPO3_12,
    ]);

    // Disable parallel otherwise non php file processing is not working i.e. typoscript or flexform
    $rectorConfig->disableParallel();

    // Define your target version which you want to support
    $rectorConfig->phpVersion(PhpVersion::PHP_81);

    // If you only want to process one/some TYPO3 extension(s), you can specify its path(s) here.
    // If you use the option --config change __DIR__ to getcwd()
    $rectorConfig->paths([
        __DIR__ . '/..',
    ]);

    // When you use rector, there are rules that require some more actions like creating UpgradeWizards for outdated TCA types.
    // To fully support you, we added some warnings. So watch out for them.

    // If you use importNames(), you should consider excluding some TYPO3 files.
    $rectorConfig->skip([
        __DIR__ . '/../.Build/*',
        __DIR__ . '/../.ddev/*',
        __DIR__ . '/../Build/*',
        __DIR__ . '/../config/*',
        __DIR__ . '/../Configuration/TypoScript/*', // exclude typoscript which would result in false positive processing
        __DIR__ . '/../var/*',
    ]);
};
