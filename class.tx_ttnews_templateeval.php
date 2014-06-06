<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Dmitry Dulepov (dmitry@typo3.org)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Hook class for TCEforms.
 *
 * $Id: class.tx_ttnews_templateeval.php 8431 2008-02-29 14:43:11Z liels_bugs $
 *
* @author Dmitry Dulepov <dmitry@typo3.org>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

/**
 * Hook class for BE to show nice template name.
 *
 * @author	Dmitry Dulepov
 */
class tx_ttnews_templateeval {
	/**
	 * Hook to show proper template name in BE if entry was created using older tt_news version
	 *
	 * @param	array	$params	Params to the hook
	 * @return	string	Evaluated value
	 */
	function deevaluateFieldValue($params) {
		if (trim($params['value']) != '' && false === strpos($params['value'], '/')) {
			$params['value'] = 'uploads/tt_news/' . $params['value'];
		}
		return $params['value'];
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.tx_ttnews_templateeval.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.tx_ttnews_templateeval.php']);
}

?>