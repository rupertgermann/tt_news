<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();
// Load field definition from 'shared' file
$tempColumns = [];
$tempColumns['tt_news_categorymounts'] = require ExtensionManagementUtility::extPath('tt_news')
    . 'Configuration/TCA/Shared/categorymounts.php';
ExtensionManagementUtility::addTCAcolumns('be_groups', $tempColumns);
ExtensionManagementUtility::addToAllTCAtypes('be_groups', 'tt_news_categorymounts');

unset($tempColumns);
