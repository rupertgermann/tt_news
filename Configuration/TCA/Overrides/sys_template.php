<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

// Add static extension templates
ExtensionManagementUtility::addStaticFile('tt_news', 'Configuration/TypoScript', 'News Settings');
ExtensionManagementUtility::addStaticFile('tt_news', 'Configuration/TypoScript/Styles', 'News Styles');
ExtensionManagementUtility::addStaticFile('tt_news', 'Configuration/TypoScript/Rss', 'News Rss Feed');
