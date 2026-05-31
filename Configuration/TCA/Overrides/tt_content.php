<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

// Add tt_news as a new content element type (CType)
ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news',
        'tt_news',
        'tt-news-plugin',
    ],
    'FILE:EXT:tt_news/Resources/Private/Flexform/flexform_ds.xml'
);
