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

defined('TYPO3_MODE') || die();
// Load field definition from 'shared' file
$tempColumns = [];
$tempColumns['tt_news_categorymounts'] = require TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tt_news')
    . 'Configuration/TCA/Shared/categorymounts.php';
// show the category selection only in non-admin be_users records
$tempColumns['tt_news_categorymounts']['displayCond'] = 'FIELD:admin:=:0';
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns);
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tt_news_categorymounts');

unset($tempColumns);
