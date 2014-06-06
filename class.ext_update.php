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
 * New for tt_news 3.0.0
 * Class for updating tt_news content elements and static template file relations.
 *
 * $Id: class.ext_update.php 26950 2009-11-25 13:01:54Z rupi $
 *
 * @author  Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage tt_news
 */
class ext_update {

	var $tstemplates;
	var $contentElements;
	var $missingHtmlTemplates = array();
	var $movedFields = array();
	var $flexObj;
	var $ll = 'LLL:EXT:tt_news/locallang.xml:updater.';

	var $checkMovedFields = array(
			'pages' => array(
				'old' => 'sDEF',
				'new' => 's_misc'
			),
			'recursive' => array(
				'old' => 'sDEF',
				'new' => 's_misc'
			),
			'listLimit' => array(
				'old' => 's_misc',
				'new' => 's_template'
			),
			'noPageBrowser' => array(
				'old' => 's_misc',
				'new' => 's_template'
			)
		);


	/**
	 * Main function, returning the HTML content of the module
	 *
	 * @return	string		HTML
	 */
	function main() {
		$out = '';
		$this->flexObj = t3lib_div::makeInstance('t3lib_flexformtools');

		// analyze
		$this->tstemplates = $this->getTsTemplates();
		$this->contentElements = $this->getContentElements();
		$this->parseFlexformXML();

		if (t3lib_div::_GP('do_update')) {
			$out .= '<a href="' . t3lib_div::linkThisScript(array('do_update' => '', 'func' => '')) . '">' . $GLOBALS['LANG']->sL($this->ll . 'back') . '</a><br>';

			$func = trim(t3lib_div::_GP('func'));
			if (method_exists($this, $func)) {
				$out .= '
				<div style="padding:15px 15px 20px 0;">
				<div class="typo3-message message-ok">
   				<div class="message-header">' . $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang.xml:updater.updateresults') . '</div>
  				<div class="message-body">
				' . $this->$func() . '
				</div>
				</div></div>';
			} else {
				$out .= '
				<div style="padding:15px 15px 20px 0;">
				<div class="typo3-message message-error">
   					<div class="message-body">ERROR: ' . $func . '() not found</div>
   				</div>
   				</div>';
			}
		} else {
			$out .= '<a href="' . t3lib_div::linkThisScript(array('do_update' => '', 'func' => '')) . '">' . $GLOBALS['LANG']->sL($this->ll . 'reload') . '
			<img style="vertical-align:bottom;" ' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/refresh_n.gif', 'width="18" height="16"') . '></a><br>';

			$out .= $this->displayWarning();

			$out .= '<h3>' . $GLOBALS['LANG']->sL($this->ll . 'actions') . '</h3>';

			// outdated static TS templates
			$out .= $this->displayUpdateOption('searchOutdatedTempl', count($this->tstemplates),'updateStaticTemplateFiles');

			// missing HTML templates
			$out .= $this->displayUpdateOption('searchNonExistingHtml', count($this->missingHtmlTemplates),'clearWrongTemplateInCE');

			// moved pidList (pages)
			$out .= $this->displayUpdateOption('searchMovedPidlist', count($this->movedFields['pages']),'fixfield_movedPidlist');

			// moved recursive
			$out .= $this->displayUpdateOption('searchMovedRecursive', count($this->movedFields['recursive']),'fixfield_movedRecursive');

			// moved noPagegbrowser
			$out .= $this->displayUpdateOption('searchMovedNoPageBrowser', count($this->movedFields['noPageBrowser']),'fixfield_movedNoPageBrowser');

			// moved listlimit
			$out .= $this->displayUpdateOption('searchMovedListLimit', count($this->movedFields['listLimit']),'fixfield_movedListLimit');
		}


		if (t3lib_div::int_from_ver(TYPO3_version) < 4003000) {
				// add flashmessages styles
			$cssPath = $GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('tt_news');
			$out = '<link rel="stylesheet" type="text/css" href="' . $cssPath . 'compat/flashmessages.css" media="screen" />' . $out;
		}


		return $out;
	}


	function displayUpdateOption($k, $count, $func) {

		$msg = $GLOBALS['LANG']->sL($this->ll . 'msg_' . $k) . ' ';
		$msg .= '<br><strong>' . str_replace('###COUNT###', $count, $GLOBALS['LANG']->sL($this->ll . 'foundMsg_' . $k)) . '</strong>';
		if ($count == 0) {
			$i = 'ok';

		} else {
			$i = 'warning2';
		}
		$msg .= ' <img ' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/icon_' . $i . '.gif', 'width="18" height="16"') . '>';

		if ($count) {
			$msg .= '<p style="margin:5px 0;">' . $GLOBALS['LANG']->sL($this->ll . 'question_' . $k) . '<p>';
			$msg .=  '<p style="margin-bottom:10px;"><em>'.$GLOBALS['LANG']->sL($this->ll . 'questionInfo_' . $k) . '</em><p>';
			$msg .= $this->getButton($func);
		} else {
			$msg .= '<br>' . $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang.xml:updater.nothingtodo');

		}

		$out = $this->wrapForm($msg,$GLOBALS['LANG']->sL($this->ll . 'lbl_' . $k));
		$out .= '<br><br>';

		return $out;
	}


	function displayWarning() {
		$out = '
		<div style="padding:15px 15px 20px 0;">
			<div class="typo3-message message-warning">
   				<div class="message-header">' . $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang.xml:updater.warningHeader') . '</div>
  				<div class="message-body">
					' . $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang.xml:updater.warningMsg') . '
				</div>
			</div>
		</div>';

		return $out;
	}


	function fixfield_movedPidlist() {
		return $this->fixMovedFfField('pages');
	}


	function fixfield_movedRecursive() {
		return $this->fixMovedFfField('recursive');
	}


	function fixfield_movedNoPageBrowser() {
		return $this->fixMovedFfField('noPageBrowser');
	}


	function fixfield_movedListLimit() {
		return $this->fixMovedFfField('listLimit');;
	}


	function fixMovedFfField($fN) {
		$msg = array();
		$conf = $this->checkMovedFields[$fN];
		foreach ($this->movedFields[$fN] as $id => $val) {
			$tmpArr = $this->contentElements[$id]['ff_parsed'];

			$oldVal = $this->pi_getFFvalue($tmpArr, $fN, $conf['old']);
			$tmpArr['data'][$conf['new']]['lDEF'][$fN]['vDEF'] = $oldVal;
			unset($tmpArr['data'][$conf['old']]['lDEF'][$fN]);

			$newff = $this->flexObj->flexArray2Xml($tmpArr, 1);

			$table = 'tt_content';
			$where = 'uid=' . $id;
			$fields_values = array('pi_flexform' => $newff);
			if ($GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $fields_values)) {
				$msg[] = 'Updated contentElement uid: ' . $id . ', pid: ' . $this->contentElements[$id]['pid'] . ', fixed field: ' . $val;
			}
		}
		return implode('<br>', $msg);
	}


	function clearWrongTemplateInCE() {
		$msg = array();
		foreach ($this->missingHtmlTemplates as $id => $val) {
			$tmpArr = $this->contentElements[$id]['ff_parsed'];
			$tmpArr['data']['s_template']['lDEF']['template_file']['vDEF'] = '';
			$newff = $this->flexObj->flexArray2Xml($tmpArr, 1);

			$table = 'tt_content';
			$where = 'uid=' . $id;
			$fields_values = array('pi_flexform' => $newff);
			if ($GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $fields_values)) {
				$msg[] = 'Updated contentElement uid: ' . $id . ', pid: ' . $this->contentElements[$id]['pid'] . ', removed template: ' . $val;
			}
		}
		return implode('<br>', $msg);
	}


	function updateStaticTemplateFiles() {
		$msg = array();
		foreach ($this->tstemplates as $ts) {
			$oldincFile = $ts['include_static_file'];

			$s = array('EXT:tt_news/static/ts_old', 'EXT:tt_news/static/ts_new', 'EXT:tt_news/static/rss_feed', 'EXT:tt_news/static/css');
			$r = array('EXT:tt_news/pi/static/ts_old', 'EXT:tt_news/pi/static/ts_new', 'EXT:tt_news/pi/static/rss_feed',
					'EXT:tt_news/pi/static/css');
			$newincfile = str_replace($s, $r, $oldincFile);

			$table = 'sys_template';

			$where = 'uid=' . $ts['uid'];
			$fields_values = array('include_static_file' => $newincfile);
			if ($GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $fields_values)) {
				$msg[] = 'Updated template "' . $ts['title'] . '" uid: ' . $ts['uid'] . ', page: ' . $ts['pid'];
			}
		}
		return implode('<br>', $msg);
	}


	function wrapForm($content, $fsLabel) {
		$out = '<form action="">
			<fieldset style="background:#f4f4f4;margin-right:15px;">
			<legend>' . $fsLabel . '</legend>
			' . $content . '

			</fieldset>
			</form>';
		return $out;
	}


	function getButton($func, $lbl = 'DO IT') {

		$params = array('do_update' => 1, 'func' => $func);

		$onClick = "document.location='" . t3lib_div::linkThisScript($params) . "'; return false;";
		$button = '<input type="submit" value="' . $lbl . '" onclick="' . htmlspecialchars($onClick) . '">';

		return $button;
	}


	function getTsTemplates() {
		$select_fields = '*';
		$from_table = 'sys_template';
		$where_clause = 'deleted=0 AND include_static_file LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%EXT:tt_news/static/%', $from_table);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select_fields, $from_table, $where_clause);

		$resultRows = array();
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$resultRows[] = $row;
		}

		return $resultRows;
	}


	function getContentElements() {
		$select_fields = '*';
		$from_table = 'tt_content';
		$where_clause = 'CType=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('list', $from_table) . '
					AND list_type=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('9', $from_table) . ' AND deleted=0';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select_fields, $from_table, $where_clause);

		if ($res) {
			$resultRows = array();
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$resultRows[$row['uid']] = array('ff' => $row['pi_flexform'], 'title' => $row['title'], 'pid' => $row['pid']);
			}
		}
		return $resultRows;
	}


	function parseFlexformXML() {

		foreach ($this->contentElements as $id => $row) {
			$tmpArr = t3lib_div::xml2array($row['ff']);
			$this->contentElements[$id]['ff_parsed'] = $tmpArr;
			$this->getMovedFfFields($tmpArr, $id);
			$this->getMissingHtmlTemplates($tmpArr, $id);
		}
	}


	function getMovedFfFields(&$xmlArr, $id) {

		foreach ($this->checkMovedFields as $fn => $conf) {
			$oldVal = $this->pi_getFFvalue($xmlArr, $fn, $conf['old']);
			$newVal = $this->pi_getFFvalue($xmlArr, $fn, $conf['new']);
			if ($oldVal && ! $newVal) {
				$this->movedFields[$fn][$id] = '"' . $fn . '" (value = ' . $oldVal . ') moved value from sheet "' . $conf['old'] . '" to "' . $conf['new'] . '"';
			}
		}
	}


	function getMissingHtmlTemplates(&$xmlArr, $id) {
		$template_file = $this->pi_getFFvalue($xmlArr, 'template_file', 's_template');
		if ($template_file) {
			$absfile = PATH_site . $this->getPath($template_file);
			if (! is_file($absfile)) {
				$this->missingHtmlTemplates[$id] = $template_file;
			}
		}
	}


	function pi_getFFvalue($T3FlexForm_array, $fieldName, $sheet = 'sDEF', $lang = 'lDEF', $value = 'vDEF') {
		$sheetArray = is_array($T3FlexForm_array) ? $T3FlexForm_array['data'][$sheet][$lang] : '';
		if (is_array($sheetArray)) {
			return $this->pi_getFFvalueFromSheetArray($sheetArray, explode('/', $fieldName), $value);
		}
	}


	/**
	 * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
	 *
	 * @param	array		Multidimensiona array, typically FlexForm contents
	 * @param	array		Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array an return element number X (whether this is right behavior is not settled yet...)
	 * @param	string		Value for outermost key, typ. "vDEF" depending on language.
	 * @return	mixed		The value, typ. string.
	 * @access private
	 * @see pi_getFFvalue()
	 */
	function pi_getFFvalueFromSheetArray($sheetArray, $fieldNameArr, $value) {

		$tempArr = $sheetArray;
		foreach ($fieldNameArr as $k => $v) {
			if (t3lib_div::testInt($v)) {
				if (is_array($tempArr)) {
					$c = 0;
					foreach ($tempArr as $values) {
						if ($c == $v) {
							#debug($values);
							$tempArr = $values;
							break;
						}
						$c++;
					}
				}
			} else {
				$tempArr = $tempArr[$v];
			}
		}
		return $tempArr[$value];
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$path: ...
	 * @return	[type]		...
	 */
	function getPath($path) {
		$tmpP = explode('/', $path);
		if (substr($tmpP[0], 0, 4) === 'EXT:') {
			$tt = explode(':', $tmpP[0]);
			$tmpP[0] = substr(t3lib_extMgm::siteRelPath($tt[1]), 0, - 1);
			$iconPath = implode('/', $tmpP);
		} else {
			$iconPath = $path;
		}

		return $iconPath;
	}


	/**
	 * Checks how many rows are found and returns true if there are any
	 * (this function is called from the extension manager)
	 *
	 * @param	string		$what: what should be updated
	 * @return	boolean
	 */
	function access($what = 'all') {
		return TRUE;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.ext_update.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.ext_update.php']);
}
?>