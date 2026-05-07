<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// Register tt_news as a new content element type (CType)
ExtensionManagementUtility::addPlugin([
    'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news',
    'tt_news',
    'tt-news-plugin',
], 'CType', 'tt_news');

// Assign the FlexForm data structure file to the tt_news content element
ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:tt_news/Resources/Private/Flexform/flexform_ds.xml',
    'tt_news'
);

// Add FlexForm configuration tab to the tt_news content element
ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;Configuration,pi_flexform,',
    'tt_news',
    'after:subheader'
);
