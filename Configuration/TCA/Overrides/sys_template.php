<?php
defined('TYPO3_MODE') or die();
// Add static extension templates
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('tt_news', 'pi/static/ts_new/', 'News settings');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('tt_news', 'pi/static/css/', 'News CSS-styles');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('tt_news', 'pi/static/rss_feed/',
    'News feeds (RSS,RDF,ATOM)');

