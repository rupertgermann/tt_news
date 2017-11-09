<?php
defined('TYPO3_MODE') or die();
// Load field definition from 'shared' file
$tempColumns = array();
$tempColumns['tt_news_categorymounts'] = require TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tt_news')
                                                 . 'Configuration/TCA/Shared/categorymounts.php';
// show the category selection only in non-admin be_users records
$tempColumns['tt_news_categorymounts']['displayCond'] = 'FIELD:admin:=:0';
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns);
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tt_news_categorymounts');
