<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2009 Rupert Germann <rupi@gmx.li>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * script which receives ajax calls from tt_news
 *
 * @author Rupert Germann <rg@rgdata.de>
 * Copyright (c) 2009
 *
 * @version $Id$
 */


// Exit, if script is called directly (must be included via eID in index_ts.php)
if (!defined ('PATH_typo3conf')) 	die ('Could not access this script directly!');


$TYPO3_AJAX = true;


//print_r(array(TYPO3_REQUESTTYPE_AJAX,TYPO3_REQUESTTYPE,TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX));

$L = intval(GeneralUtility::_GP('L'));
if ($L > 0) {
	GeneralUtility::_GETset(array('L' => $L));
}


$idAndTarget = rawurldecode(GeneralUtility::_GP('id'));
$idParts = GeneralUtility::trimExplode(' ',$idAndTarget,1);
$id = intval($idParts[0]);

// Make new instance of TSFE
//$temp_TSFEclassName = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceClassName('tslib_fe');
//$GLOBALS['TSFE'] = new \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController (
//	$GLOBALS['TYPO3_CONF_VARS'],
//	$id,
//	\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('type'),
//	\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('no_cache'),
//	\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cHash'),
//	\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('jumpurl'),
//	\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('MP'),
//	\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('RDCT')
//);
//
//
$GLOBALS['TSFE'] = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController',
    $GLOBALS['TYPO3_CONF_VARS'], $id, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('type'));
\TYPO3\CMS\Frontend\Utility\EidUtility::initTCA();

// don't cache ajax responses
$GLOBALS['TSFE']->no_cache = true;

$GLOBALS['TSFE']->connectToDB();
$GLOBALS['TSFE']->initFEuser();
//$GLOBALS['TSFE']->checkAlternativeIdMethods();
//$GLOBALS['TSFE']->clear_preview();
$GLOBALS['TSFE']->determineId();
$GLOBALS['TSFE']->initTemplate();
//$GLOBALS['TSFE']->getConfigArray();

if ($L > 0) {
	$GLOBALS['TSFE']->settingLanguage();
	$GLOBALS['TSFE']->settingLocale();
}



// finding the script path from the variable
$ajaxID = (string) GeneralUtility::_GP('ajaxID');


require_once(ExtensionManagementUtility::extPath('tt_news').'pi/class.tx_ttnews.php');
require_once(ExtensionManagementUtility::extPath('tt_news') . 'lib/class.tx_ttnews_helpers.php');
require_once(ExtensionManagementUtility::extPath('tt_news').'lib/class.tx_ttnews_typo3ajax.php');
/**
 * TODO: 24.11.2009
 *
 *
 * use \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance
 */


// instantiating the AJAX object
//$ajaxClassName = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceClassName('tx_ttnews_typo3ajax');
$ajaxObj = new tx_ttnews_typo3ajax($ajaxID);
$ajaxParams = array();

$tt_newsObj = new tx_ttnews();
$tt_newsObj->hObj = new tx_ttnews_helpers($tt_newsObj);
$tt_newsObj->cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
$tt_newsObj->local_cObj = &$tt_newsObj->cObj;

$cObjUid = intval(GeneralUtility::_GP('cObjUid'));
$tt_newsObj->cObj->data = $GLOBALS['TSFE']->sys_page->checkRecord('tt_content',$cObjUid,1);
$tt_newsObj->pi_initPIflexForm();
$tt_newsObj->conf = &$GLOBALS['TSFE']->tmpl->setup['plugin.']['tt_news.'];

// variables needed to get the newscount per category
if (! $tt_newsObj->conf['dontUsePidList']) {
	$tt_newsObj->initPidList();
}
/**
 * For some reasons this is needed for TYPO3 6.1
 *
 * FIXME: there must be a proper way to do this
 *
 */
$TCA['tt_news'] = array (
		'ctrl' => array (
				'enablecolumns' => array (
						'disabled' => 'hidden',
						'starttime' => 'starttime',
						'endtime' => 'endtime',
						'fe_group' => 'fe_group',
				),
		)
);
$TCA['tt_news_cat'] = array (
		'ctrl' => array (
				'enablecolumns' => array (
						'disabled' => 'hidden',
						'starttime' => 'starttime',
						'endtime' => 'endtime',
						'fe_group' => 'fe_group',
				),
		)
);

$tt_newsObj->enableFields = $tt_newsObj->getEnableFields('tt_news');

$tt_newsObj->initCategoryVars();
$tt_newsObj->initCatmenuEnv($tt_newsObj->conf['displayCatMenu.']);


$ajaxParams['tt_newsObj'] = &$tt_newsObj;
$ajaxParams['feUserObj'] = &$GLOBALS['TSFE']->fe_user;

$ajaxScript = ExtensionManagementUtility::extPath('tt_news').'lib/class.tx_ttnews_catmenu.php:tx_ttnews_catmenu->ajaxExpandCollapse';


// evaluating the arguments and calling the AJAX method/function
if (empty($ajaxID)) {
	$ajaxObj->setError('No valid ajaxID parameter given.');
} else if (empty($ajaxScript)) {
	$ajaxObj->setError('Registered backend function for ajaxID "'.$ajaxID.'" was not found.');
} else {
	$ret = GeneralUtility::callUserFunction($ajaxScript, $ajaxParams, $ajaxObj, false, true);
	//	if ($ret === false) {
	//		$ajaxObj->setError('Registered backend function for ajaxID "'.$ajaxID.'" was not found.');
	//	}
}

$ajaxObj->render();
