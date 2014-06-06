<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2009 Rupert Germann <rupi@gmx.li>
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
 * Class that renders fields for the extensionmanager configuration
 *
 * $Id: class.ext_update.php 18789 2009-04-07 19:41:24Z rupi $
 *
 * @author  Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage tt_news
 */
class tx_ttnews_tsparserext {


	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function displayMessage(&$params, &$tsObj) {

		$out = '';

		if (t3lib_div::int_from_ver(TYPO3_version) < 4003000) {
				// 4.3.0 comes with flashmessages styles. For older versions we include the needed styles here
			$cssPath = $GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('tt_news');
			$out .= '<link rel="stylesheet" type="text/css" href="' . $cssPath . 'compat/flashmessages.css" media="screen" />';
		}

		if (t3lib_div::int_from_ver(TYPO3_version) < 4005000) {
			$link = 'index.php?&amp;id=0&amp;CMD[showExt]=tt_news&amp;SET[singleDetails]=updateModule';
		} else {
			$link = 'mod.php?&amp;id=0&amp;M=tools_em&amp;CMD[showExt]=tt_news&amp;SET[singleDetails]=updateModule';
		}


		$out .= '
		<div style="position:absolute;top:10px;right:10px; width:300px;">
			<div class="typo3-message message-information">
   				<div class="message-header">' . $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang.xml:extmng.updatermsgHeader') . '</div>
  				<div class="message-body">
  					' . $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang.xml:extmng.updatermsg') . '<br />
  					<a style="text-decoration:underline;" href="' . $link . '">
  					' . $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang.xml:extmng.updatermsgLink') . '</a>
  				</div>
  			</div>
  		</div>
  		';

		return $out;
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_tsparserext.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_tsparserext.php']);
}
?>