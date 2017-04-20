<?php

defined('TYPO3_MODE') or die();

// remove some fields from the tt_content content element
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][9] = 'layout,select_key,pages,recursive';
// add FlexForm field to tt_content
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][9] = 'pi_flexform';
// add tt_news to the "insert plugin" content element (list_type = 9)
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news', 9), 'list_type', 'tt_news');

// add the tt_news record to the insert records content element
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tt_news');

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(9, 'FILE:EXT:tt_news/Resources/Private/Flexform/flexform_ds.xml');
