<?php

/*
 * Copyright notice
 *
 * (c) 2004-2018 Rupert Germann <rupi@gmx.li>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

defined('TYPO3_MODE') or die();
// Add static extension templates
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('tt_news', 'Configuration/TypoScript/ts_new/', 'News settings');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('tt_news', 'Configuration/TypoScript/css/', 'News CSS-styles');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'tt_news',
    'Configuration/TypoScript/rss_feed/',
    'News feeds (RSS,RDF,ATOM)'
);
