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
// remove some fields from the tt_content content element
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][9] = 'layout,select_key,pages,recursive';
// add FlexForm field to tt_content
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][9] = 'pi_flexform';
// add tt_news to the "insert plugin" content element (list_type = 9)
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin([
    'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news',
    9
], 'list_type', 'tt_news');
// add the tt_news record to the insert records content element
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tt_news');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    9,
    'FILE:EXT:tt_news/Resources/Private/Flexform/flexform_ds.xml'
);
