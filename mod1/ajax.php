<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Benjamin Mack
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
 * AJAX dispatcher
 *
 * @author  Rupert Germann <rupi@gmx.li>
 * @author	Benjamin Mack <mack@xnos.org>
 * @package	TYPO3
 *
 * $Id$
 *
 */

$TYPO3_AJAX = true;
require('conf.php');
require($BACK_PATH.'init.php');

$GLOBALS['LANG'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('language');
$GLOBALS['LANG']->init($GLOBALS['BE_USER']->uc['lang']);

	// finding the script path from the variable
$ajaxID = (string) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('ajaxID');
$ajaxScript = $TYPO3_CONF_VARS['BE']['AJAX'][$ajaxID];



$ajaxObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3AJAX', $ajaxID);


$ajaxParams = array();



	// evaluating the arguments and calling the AJAX method/function
if (empty($ajaxID)) {
	$ajaxObj->setError('No valid ajaxID parameter given.');
} else if (empty($ajaxScript)) {
	$ajaxObj->setError('Registered backend function for ajaxID "'.$ajaxID.'" was not found.');
} else {
	$ret = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($ajaxScript, $ajaxParams, $ajaxObj, false, true);
	if ($ret === false) {
		$ajaxObj->setError('Registered backend function for ajaxID "'.$ajaxID.'" was not found.');
	}
}


//print_r($ajaxObj);

$ajaxObj->render();

