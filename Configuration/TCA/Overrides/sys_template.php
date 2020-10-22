<?php
defined('TYPO3_MODE') or die();
// Add static extension templates
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('tt_news', 'Configuration/TypoScript/', 'News settings');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('tt_news', 'Configuration/TypoScript/Css/', 'News CSS-styles');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('tt_news', 'Configuration/TypoScript/Rss/', 'News feeds (RSS,RDF,ATOM)');

