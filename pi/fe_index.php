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

$L = intval(t3lib_div::_GP('L'));
if ($L > 0) {
	t3lib_div::_GETset(array('L' => $L));
}


$idAndTarget = rawurldecode(t3lib_div::_GP('id'));
$idParts = t3lib_div::trimExplode(' ',$idAndTarget,1);
$id = intval($idParts[0]);

// Make new instance of TSFE
//$temp_TSFEclassName = t3lib_div::makeInstanceClassName('tslib_fe');
$TSFE = new tslib_fe (
	$GLOBALS['TYPO3_CONF_VARS'],
	$id,
	t3lib_div::_GP('type'),
	t3lib_div::_GP('no_cache'),
	t3lib_div::_GP('cHash'),
	t3lib_div::_GP('jumpurl'),
	t3lib_div::_GP('MP'),
	t3lib_div::_GP('RDCT')
);




// don't cache ajax responses
$TSFE->no_cache = true;

$TSFE->connectToDB();
$TSFE->initFEuser();
$TSFE->checkAlternativeIdMethods();
$TSFE->clear_preview();
$TSFE->determineId();
$TSFE->getCompressedTCarray();
$TSFE->initTemplate();
$TSFE->getConfigArray();

if ($L > 0) {
	$TSFE->settingLanguage();
	$TSFE->settingLocale();
}



// finding the script path from the variable
$ajaxID = (string) t3lib_div::_GP('ajaxID');


require_once(t3lib_extMgm::extPath('tt_news').'pi/class.tx_ttnews.php');
require_once(t3lib_extMgm::extPath('tt_news') . 'lib/class.tx_ttnews_helpers.php');
require_once(t3lib_extMgm::extPath('tt_news').'lib/class.tx_ttnews_typo3ajax.php');
/**
 * TODO: 24.11.2009
 *
 *
 * use t3lib_div::makeInstance
 */


// instantiating the AJAX object
//$ajaxClassName = t3lib_div::makeInstanceClassName('tx_ttnews_typo3ajax');
$ajaxObj = new tx_ttnews_typo3ajax($ajaxID);
$ajaxParams = array();

$tt_newsObj = new tx_ttnews();
$tt_newsObj->hObj = new tx_ttnews_helpers($tt_newsObj);
$tt_newsObj->cObj = t3lib_div::makeInstance('tslib_cObj');
$tt_newsObj->local_cObj = &$tt_newsObj->cObj;

$cObjUid = intval(t3lib_div::_GP('cObjUid'));
$tt_newsObj->cObj->data = $TSFE->sys_page->checkRecord('tt_content',$cObjUid,1);
$tt_newsObj->pi_initPIflexForm();
$tt_newsObj->conf = &$TSFE->tmpl->setup['plugin.']['tt_news.'];

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
$ajaxParams['feUserObj'] = &$TSFE->fe_user;

$ajaxScript = t3lib_extMgm::extPath('tt_news').'lib/class.tx_ttnews_catmenu.php:tx_ttnews_catmenu->ajaxExpandCollapse';


// evaluating the arguments and calling the AJAX method/function
if (empty($ajaxID)) {
	$ajaxObj->setError('No valid ajaxID parameter given.');
} else if (empty($ajaxScript)) {
	$ajaxObj->setError('Registered backend function for ajaxID "'.$ajaxID.'" was not found.');
} else {
	$ret = t3lib_div::callUserFunction($ajaxScript, $ajaxParams, $ajaxObj, false, true);
	//	if ($ret === false) {
	//		$ajaxObj->setError('Registered backend function for ajaxID "'.$ajaxID.'" was not found.');
	//	}
}

$ajaxObj->render();
?>