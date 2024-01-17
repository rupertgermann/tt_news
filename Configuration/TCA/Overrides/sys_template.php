<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();
// Add static extension templates
ExtensionManagementUtility::addStaticFile('tt_news', 'Configuration/TypoScript/ts_new/', 'News settings');
ExtensionManagementUtility::addStaticFile('tt_news', 'Configuration/TypoScript/css/', 'News CSS-styles');
ExtensionManagementUtility::addStaticFile(
    'tt_news',
    'Configuration/TypoScript/rss_feed/',
    'News feeds (RSS,RDF,ATOM)'
);
