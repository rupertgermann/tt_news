<?php
/**
 * Copyright notice
 *
 *          (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
 *          All rights reserved
 *
 *          This script is part of the TYPO3 project. The TYPO3 project is
 *          free software; you can redistribute it and/or modify
 *          it under the terms of the GNU General Public License as published by
 *          the Free Software Foundation; either version 2 of the License, or
 *          (at your option) any later version.
 *
 *          The GNU General Public License can be found at
 *          http://www.gnu.org/copyleft/gpl.html.
 *          A copy is found in the textfile GPL.txt and important notices to the license
 *          from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *          This script is distributed in the hope that it will be useful,
 *          but WITHOUT ANY WARRANTY; without even the implied warranty of
 *          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *          GNU General Public License for more details.
 *
 *          This copyright notice MUST APPEAR in all copies of the script!
 */
/**
 * Creates a language-selector menu with three flags, an english, a danish and a german
 * flag for each language supported on the site.
 *
 *
 * $Id: tt_news_languageMenu.php 2944 2005-05-15 19:18:18Z rupi $
 *
 * @author Rupert Germann <rupi@gmx.li>
 */

/**
 * language menu that keeps the links vars from tt_news
 *
 * @param	[type]		$content: ...
 * @return	void
 * @access public
 */
function user_languageMenu($content) {
	// image files for deafultlanguage
	$defaultflag = array(
		'on' => '<img src="media/uploads/flag_uk.gif" width="21" height="13" hspace="5" border="0" alt="" />',
		'off' => '<img src="media/uploads/flag_uk_d.gif" width="21" height="13" hspace="5" border="0" alt="" />',
	) ;


	// image files for additional languages. the numbers are the uids of the system languages
	$flagimages = array(
		'1' => array(
			'on' => '<img src="media/uploads/flag_dk.gif" width="21" height="13" hspace="5" border="0" alt="" />',
			'off' => '<img src="media/uploads/flag_dk_d.gif" width="21" height="13" hspace="5" border="0" alt="" />'
			),
		'2' => array(
			'on' => '<img src="media/uploads/flag_de.gif" width="21" height="13" hspace="5" border="0" alt="" />',
			'off' => '<img src="media/uploads/flag_de_d.gif" width="21" height="13" hspace="5" border="0" alt="" />'
			)
		);
	// Pointer for the active language
	$pointer = '<img src="t3lib/gfx/content_client.gif" width="7" height="10" alt="" />';




	// First, select all pages_language_overlay records on the current page. Each represents a possibility for a language.
	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages_language_overlay', 'pid=' . intval($GLOBALS['TSFE']->id) . $GLOBALS['TSFE']->sys_page->enableFields('pages_language_overlay'), 'sys_language_uid');

	$langArr = array();
	while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
		$langArr[$row['sys_language_uid']] = $row['title'];
	}

	$queryString = explode('&', t3lib_div::implodeArrayForUrl('', $GLOBALS['_GET'])) ;
	if ($queryString) {
		while (list(, $val) = each($queryString)) {
			$tmp = explode('=', $val);
			$paramArray[$tmp[0]] = $val;
		}
		$excludeList = 'id,L,tx_ttnews[pointer]';
		while (list($key, $val) = each($paramArray)) {
			if (!$val || ($excludeList && t3lib_div::inList($excludeList, $key))) {
				unset($paramArray[$key]);
			}
		}
		$tmpParams = implode($paramArray, '&');

		$newsAddParams = $tmpParams?'&' . $tmpParams:'';
	}
	// unset the global linkVar "L" for the language menu because it's build new in this script
	$linkVarsBak = $GLOBALS['TSFE']->linkVars;
	$tmplinkVars = t3lib_div::trimExplode('&', $GLOBALS['TSFE']->linkVars) ;
	if ($tmplinkVars) {
		while (list($kl, $vl) = each($tmplinkVars)) {
			if (!$vl || preg_match('/L=[0-9]/', $vl)) {
				unset($tmplinkVars[$kl]);
			}
		}
		$GLOBALS['TSFE']->linkVars = implode('&', $tmplinkVars) ;
	}

	$tmpLang = $GLOBALS['TSFE']->sys_language_uid;

	$flags = array();
	// flag for the default language
	if ($GLOBALS['TSFE']->page['l18n_cfg']==1){ // = "Hide default Translation" is activated
		$flags[0] = $defaultflag['off'];
	} else {
		$flags[0] = ($tmpLang == 0?$pointer:'') . $GLOBALS['TSFE']->cObj->typolink($defaultflag['on'], array('parameter' => $GLOBALS['TSFE']->id . ' _top', 'additionalParams' => (!preg_match('/&L=[0-9]/', $newsAddParams)?$newsAddParams . '&L=0':$newsAddParams)));
	}

	// flags for the additional language
	if (is_array($flagimages)) {
		foreach ($flagimages as $fk => $fv) {
			if ($langArr[$fk]) {
				$flags[$fk] = ($tmpLang == $fk?$pointer:'') . $GLOBALS['TSFE']->cObj->typolink($fv['on'], array('parameter' => $GLOBALS['TSFE']->id . ' _top', 'additionalParams' => (!preg_match('/&L=[0-9]/', $newsAddParams)?$newsAddParams . '&L=' . $fk:$newsAddParams)));
			} else {
				$flags[$fk] = $fv['off'];
			}
		}
	}
	$content = '<div class="langmenu"><p>'
	 . implode('', $flags) . '</p></div>';
	// restore link vars
	$GLOBALS['TSFE']->linkVars = $linkVarsBak;
	return $content;
}

?>
