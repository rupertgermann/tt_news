<?php
defined('TYPO3_MODE') or die();
// Add static extension templates
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('tt_news', 'Configuration/TypoScript/ts_new/', 'News settings');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('tt_news', 'Configuration/TypoScript/css/', 'News CSS-styles');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('tt_news', 'Configuration/TypoScript/rss_feed/',
    'News feeds (RSS,RDF,ATOM)');

