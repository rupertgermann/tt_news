<?php
defined('TYPO3_MODE') or die();
// Load field definition from 'shared' file
$tempColumns = array();
$tempColumns['tt_news_categorymounts'] = require TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tt_news')
                                                 . 'Configuration/TCA/Shared/categorymounts.php';
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_groups', $tempColumns);
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_groups', 'tt_news_categorymounts');

unset($tempColumns);