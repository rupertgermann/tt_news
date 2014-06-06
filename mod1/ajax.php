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
 * $Id: ajax.php 26947 2009-11-25 11:49:38Z rupi $
 *
 */

$TYPO3_AJAX = true;
require('conf.php');
require($BACK_PATH.'init.php');

require_once(PATH_typo3.'classes/class.typo3ajax.php');
require_once(PATH_typo3.'sysext/lang/lang.php');


$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
$GLOBALS['LANG']->init($GLOBALS['BE_USER']->uc['lang']);

	// finding the script path from the variable
$ajaxID = (string) t3lib_div::_GP('ajaxID');
$ajaxScript = $TYPO3_CONF_VARS['BE']['AJAX'][$ajaxID];


	// instantiating the AJAX object
if (t3lib_div::int_from_ver(TYPO3_version) >= 4003000) {
	$ajaxObj = t3lib_div::makeInstance('TYPO3AJAX', $ajaxID);
} else {
	$ajaxClassName = t3lib_div::makeInstanceClassName('TYPO3AJAX');
	$ajaxObj = new $ajaxClassName($ajaxID);
}

$ajaxParams = array();
//print_r(array($_GET, $_POST, $ajaxID, $ajaxScript, $ajaxParams));



	// evaluating the arguments and calling the AJAX method/function
if (empty($ajaxID)) {
	$ajaxObj->setError('No valid ajaxID parameter given.');
} else if (empty($ajaxScript)) {
	$ajaxObj->setError('Registered backend function for ajaxID "'.$ajaxID.'" was not found.');
} else {
	$ret = t3lib_div::callUserFunction($ajaxScript, $ajaxParams, $ajaxObj, false, true);
	if ($ret === false) {
		$ajaxObj->setError('Registered backend function for ajaxID "'.$ajaxID.'" was not found.');
	}
}


//print_r($ajaxObj);

$ajaxObj->render();

?>