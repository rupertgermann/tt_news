<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Mathias Bolt Lesniak <mathias@lilio.com>
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
 * Function for adding new display items to tt_news in the backend
 *
 * $Id: class.tx_ttnews_itemsProcFunc.php 26878 2009-11-24 10:17:01Z rupi $
 *
 * @author	Mathias Bolt Lesniak <mathias@lilio.com>
 * @author Rupert Germann <rupi@gmx.li>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   57: class tx_ttnews_itemsProcFunc
 *   64:     function user_insertExtraCodes($config)
 *
 * TOTAL FUNCTIONS: 1
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

 /**
  * 'itemsProcFunc' for the 'tt_news' extension.
  *
  * @author	Mathias Bolt Lesniak <mathias@lilio.com>
  * @author Rupert Germann <rupi@gmx.li>
  * @package TYPO3
  * @subpackage tt_news
  */
class tx_ttnews_itemsProcFunc {
/**
 * insert 'codes', found in the ['what_to_display'] array to the selector in the BE.
 *
 * @param	array		$config: extension configuration array
 * @return	array		$config array with extra codes merged in
 */
	function user_insertExtraCodes($config) {
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['what_to_display'])) {
			$config['items'] = array_merge($config['items'], $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['what_to_display']);
		}
		return $config;
	}

}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_itemsProcFunc.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_itemsProcFunc.php']);
}
?>