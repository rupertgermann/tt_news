<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// Load field definition from 'shared' file
$tempColumns = [];
$tempColumns['tt_news_categorymounts'] = require ExtensionManagementUtility::extPath('tt_news') . 'Configuration/TCA/Shared/categorymounts.php';

// show the category selection only in non-admin be_users records
$tempColumns['tt_news_categorymounts']['displayCond'] = 'FIELD:admin:=:0';
ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns);
ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tt_news_categorymounts');

unset($tempColumns);
