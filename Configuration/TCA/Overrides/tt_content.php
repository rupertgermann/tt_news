<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();
// remove some fields from the tt_content content element
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][9] = 'layout,select_key,pages,recursive';
// add FlexForm field to tt_content
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][9] = 'pi_flexform';
// add tt_news to the "insert plugin" content element (list_type = 9)
ExtensionManagementUtility::addPlugin([
    'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news',
    9,
], 'list_type', 'tt_news');
// add the tt_news record to the insert records content element
ExtensionManagementUtility::addToInsertRecords('tt_news');
ExtensionManagementUtility::addPiFlexFormValue(
    9,
    'FILE:EXT:tt_news/Resources/Private/Flexform/flexform_ds.xml'
);
