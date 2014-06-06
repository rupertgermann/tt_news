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
 * tt_news helper functions
 *
 * $Id: class.tx_ttnews_div.php 8958 2008-04-20 14:11:42Z rupertgermann $
 *
 * @author	Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage tt_news
 */
class tx_ttnews_helpers {

	var $pObj;


	function tx_ttnews_helpers(&$pObj) {
		$this->pObj = &$pObj;
	}


	/**
	 * checks for each field of a list of items if it exists in the tt_news table and returns the validated fields
	 *
	 * @param	string		$fieldlist: a list of fields to ckeck
	 * @return	string		the list of validated fields
	 */
	function validateFields($fieldlist, $existingFields) {
		$checkedFields = array();
		$fArr = t3lib_div::trimExplode(',', $fieldlist, 1);
		foreach ($fArr as $fN) {
			if (in_array($fN, $existingFields)) {
				$checkedFields[] = $fN;
			}
		}
		$checkedFieldlist = implode($checkedFields, ',');
		return $checkedFieldlist;
	}


	/**
	 * Checks the visibility of a list of category-records
	 *
	 * @param	string		$recordlist: comma seperated list of category uids
	 * @return	string		$clearedlist: the cleared list
	 */
	function checkRecords($recordlist) {
		if ($recordlist) {
			$tempRecs = t3lib_div::trimExplode(',', $recordlist, 1);
			// debug($temp);
			$newtemp = array();
			foreach ($tempRecs as $val) {
				if ($val === '0') {
					$this->pObj->nocat = true;
				}
				$val = intval($val);
				if ($val) {
					$test = $GLOBALS['TSFE']->sys_page->checkRecord('tt_news_cat', $val, 1); // test, if the record is visible
					if ($test) {
						$newtemp[] = $val;
					}
				}
			}

			if (! count($newtemp)) {
				// select category 'null' if no visible category was found
				$newtemp[] = 'null';
			}
			$clearedlist = implode(',', $newtemp);
			return $clearedlist;
		}

	}


	/**
	 * Searches the category rootline (up) for a single view pid. If nothing is found in the current
	 * category, the single view pid of the parent categories is taken (recusivly).
	 *
	 * @param int $currentCategory: Uid of the current category
	 * @return int first found single view pid
	 */
	function getRecursiveCategorySinglePid($currentCategory) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,parent_category,single_pid', 'tt_news_cat', 'tt_news_cat.uid=' . $currentCategory . $this->pObj->SPaddWhere . $this->pObj->enableCatFields);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		if ($row['single_pid'] > 0) {
			return $row['single_pid'];
		} elseif ($row['parent_category'] > 0) {
			return $this->getRecursiveCategorySinglePid($row['parent_category']);
		}
	}


	/**
	 * extends a given list of categories by their subcategories. This function returns a nested array with subcategories (the function getSubCategories() return only a commaseparated list of category UIDs)
	 *
	 * @param	string		$catlist: list of categories which will be extended by subcategories
	 * @param	string		$fields: list of fields for the query
	 * @param	integer		$cc: counter to detect recursion in nested categories
	 * @param	[type]		$cc: ...
	 * @return	array		all categories in a nested array
	 */
	function getSubCategoriesForMenu($catlist, $fields, $addWhere, $cc = 0) {
		$pcatArr = array();

		$from_table = 'tt_news_cat';
		$where_clause = 'tt_news_cat.parent_category IN (' . $catlist . ')' . $this->pObj->SPaddWhere . $this->pObj->enableCatFields;
		$orderBy = 'tt_news_cat.' . $this->pObj->config['catOrderBy'];

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					$fields,
					$from_table,
					$where_clause,
					'', $orderBy);

		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$cc++;
			if ($cc > 10000) {
				$GLOBALS['TT']->setTSlogMessage('tt_news: one or more recursive categories where found');
				return $pcatArr;
			}
			$subcats = $this->getSubCategoriesForMenu($row['uid'], $fields, $addWhere, $cc);
			$pcatArr[] = is_array($subcats) ? array_merge($row, $subcats) : '';
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $pcatArr;
	}


	/**
	 * divides the bodytext field of a news single view to pages and returns the part of the bodytext
	 * that is choosen by piVars[$pointerName]
	 *
	 * @param	string		the text with 'pageBreakTokens' in it
	 * @param	array		config array for the single view
	 * @return	string		the current bodytext part wrapped with stdWrap
	 */
	function makeMultiPageSView($bodytext, $lConf) {
		$pointerName = $this->pObj->config['singleViewPointerName'];
		$pagenum = $this->pObj->piVars[$pointerName] ? $this->pObj->piVars[$pointerName] : 0;
		$textArr = t3lib_div::trimExplode($this->pObj->config['pageBreakToken'], $bodytext, 1);
		$pagecount = count($textArr);
		// render a pagebrowser for the single view
		if ($pagecount > 1) {
			// configure pagebrowser vars
			$this->pObj->internal['res_count'] = $pagecount;
			$this->pObj->internal['results_at_a_time'] = 1;
			$this->pObj->internal['maxPages'] = $this->pObj->conf['pageBrowser.']['maxPages'];
			if (! $this->pObj->conf['pageBrowser.']['showPBrowserText']) {
				$this->pObj->LOCAL_LANG[$this->pObj->LLkey]['pi_list_browseresults_page'] = '';
			}
			$pbConf = $this->pObj->conf['singleViewPageBrowser.'];
			$markerArray = array();
			$markerArray = $this->pObj->getPagebrowserContent($markerArray, $pbConf, $pointerName);
			$pagebrowser = $markerArray['###BROWSE_LINKS###'];
		}
		return array($this->pObj->formatStr($this->pObj->local_cObj->stdWrap($textArr[$pagenum], $lConf['content_stdWrap.'])), $pagebrowser);
	}


	/**
	 * Converts the piVars 'pS' and 'pL' to a human readable format which will be filled to
	 * the piVars 'year' and 'month'.
	 *
	 * @return	void
	 */
	function convertDates() {
		//readable archivedates
		if ($this->pObj->piVars['year'] || $this->pObj->piVars['month']) {
			$this->pObj->arcExclusive = 1;
		}
		if (! $this->pObj->piVars['year'] && $this->pObj->piVars['pS']) {
			$this->pObj->piVars['year'] = date('Y', (int) $this->pObj->piVars['pS']);
		}
		if (! $this->pObj->piVars['month'] && $this->pObj->piVars['pS']) {
			$this->pObj->piVars['month'] = date('m', (int) $this->pObj->piVars['pS']);
		}
		if (! $this->pObj->piVars['day'] && $this->pObj->piVars['pS']) {
			$this->pObj->piVars['day'] = date('j', (int) $this->pObj->piVars['pS']);
		}
		if ($this->pObj->piVars['year'] || $this->pObj->piVars['month'] || $this->pObj->piVars['day']) {
			$mon = intval($this->pObj->piVars['month'] ? $this->pObj->piVars['month'] : 1);
			$day = intval($this->pObj->piVars['day'] ? $this->pObj->piVars['day'] : 1);

			$this->pObj->piVars['pS'] = mktime(0, 0, 0, $mon, $day, (int) $this->pObj->piVars['year']);

			switch ($this->pObj->config['archiveMode']) {
				case 'month' :
					$this->pObj->piVars['pL'] = mktime(0, 0, 0, $mon + 1, 1, (int) $this->pObj->piVars['year']) - (int) $this->pObj->piVars['pS'] - 1;
					break;
				case 'quarter' :
					$this->pObj->piVars['pL'] = mktime(0, 0, 0, $mon + 3, 1, (int) $this->pObj->piVars['year']) - (int) $this->pObj->piVars['pS'] - 1;
					break;
				case 'year' :
					$this->pObj->piVars['pL'] = mktime(0, 0, 0, 1, 1, (int) $this->pObj->piVars['year'] + 1) - (int) $this->pObj->piVars['pS'] - 1;
					unset($this->pObj->piVars['month']);
					break;
			}
		}
	}


	/**
	 * inserts pagebreaks after a certain amount of words
	 *
	 * @param	string		text which can contain manully inserted 'pageBreakTokens'
	 * @param	integer		amount of words in the subheader (short). The length of the first page will be reduced by that amount of words added to the value of $this->conf['cropWordsFromFirstPage'].
	 * @return	string		the processed text
	 */
	function insertPagebreaks($text, $firstPageWordCrop) {

		$text = str_replace(array('</p>'), array('</p>' . chr(10)), $text);
		$paragraphToken = chr(10);

		$paragraphs = explode($paragraphToken, $text); // get paragraphs
		$wtmp = array();
		$firstPageCrop = $firstPageWordCrop + intval($this->pObj->conf['cropWordsFromFirstPage']);
		$cc = 0; // wordcount
		$isfirst = true; // first paragraph
		foreach ($paragraphs as $k => $p) {

			if (trim($paragraphs[$k + 1]) == '&nbsp;') {
				unset($paragraphs[$k + 1]);
			}
			if (! isset($paragraphs[$k + 2])) {
				if ($paragraphs[$k + 1] && strlen($paragraphs[$k + 1]) < 20) {
					$p .= $paragraphs[$k + 1];
				}
			}
			if (! isset($paragraphs[$k + 1]) && strlen($p) < 20) { // last paragraph shorter than 20 chars was already added to previous paragraph
				continue;
			}

			$words = explode(' ', $p); // get words
			$pArr = array();
			$break = false;

			foreach ($words as $w) {
				$fpc = ($isfirst && ! $this->pObj->conf['subheaderOnAllSViewPages'] ? $firstPageCrop : 0);
				$wc = $this->pObj->config['maxWordsInSingleView'] - $fpc;
				if (strpos($w, $this->pObj->config['pageBreakToken'])) { // manually inserted pagebreaks, unset counter
					$cc = 0;
					$pArr[] = $w;
					$isfirst = false;
				} elseif ($cc >= $this->compatibility()->intInRange($wc, 0, $this->pObj->config['maxWordsInSingleView'])) { // more words than maxWordsInSingleView
					if (t3lib_div::inList('.,!,?', substr($w, - 1))) {
						if ($this->pObj->conf['useParagraphAsPagebreak']) { // break at paragraph
							$break = true;
							$pArr[] = $w;
							//							$pArr[] = '<b> '.$cc.' </b>';
						} else { // break at dot and ? and !
							$pArr[] = $w . $this->pObj->config['pageBreakToken'];
							//							$pArr[] = '<b> '.$cc.' </b>';
						}
						$cc = 0;
					} else {
						$pArr[] = $w;
					}
					$isfirst = false;
				} else {
					$pArr[] = $w;
				}
				$cc++;
			}
			if ($break) { // add break at end of current paragraph
				array_push($pArr, $this->pObj->config['pageBreakToken']);
			}
			$wtmp[] = implode($pArr, ' ');
		}
		$processedText = implode($wtmp, chr(10));
		return $processedText;
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$caller: ...
	 * @param	[type]		$getGlobalTime: ...
	 * @return	[type]		...
	 */
	function getParsetime($caller = '', $getGlobalTime = false) {
		$currentTime = time() + microtime();
		$tmpCI = debug_backtrace();
		$call_info = $tmpCI[1];

		//		debug($call_info, ' ('.__CLASS__.'::'.__FUNCTION__.')', __LINE__, __FILE__, 1);


		$code_line = $call_info['line'];
		$file = array_pop(explode('/', $tmpCI[0]['file']));
		$lbl = $caller;
		$msg = array();
		if ($this->pObj->start_time === NULL) {
			$lbl = 'START: ' . basename($file);
			$msg['INITIALIZE'] = $caller;
			$this->pObj->start_time = $currentTime;
			$this->pObj->global_start_time = $currentTime;
			$this->pObj->start_time = $currentTime;

			$msg['file:'] = $file;
			$msg['start_code_line:'] = $this->pObj->start_code_line;
			$msg['mem:'] = ceil(memory_get_usage() / 1024) . '  KB';
			$this->writelog($msg, $lbl, $code_line, 0);

			return;
		}

		if ($getGlobalTime) {
			$time = round(($currentTime - $this->pObj->global_start_time), 3);
			$lbl = 'RESULTS: ' . $this->pObj->theCode;
			$msg['pid_list'] = $this->pObj->pid_list;
		} else {
			$time = round(($currentTime - $this->pObj->start_time), 3);
		}

		$msg['time:'] = $time;
		$msg['caller:'] = $caller;
		$msg['code-lines:'] = $this->pObj->start_code_line . '-' . $code_line;
		$msg['mem:'] = ceil(memory_get_usage() / 1024) . '  KB';

		$this->pObj->start_time = $currentTime;
		$this->pObj->start_code_line = $code_line;

		if ($time > $this->pObj->parsetimeThreshold || $getGlobalTime) {
			$this->writelog($msg, $lbl, $code_line, $getGlobalTime);
		}
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$msg: ...
	 * @param	[type]		$lbl: ...
	 * @param	[type]		$code_line: ...
	 * @param	[type]		$sev: ...
	 * @return	[type]		...
	 */
	function writelog($msg, $lbl, $code_line, $sev) {

		if ($this->pObj->useDevlog) {
			$time = $msg['time:'];
			if ($time > 0) {
				$sev = 0;
				if ($time > 0.2) {
					$sev = 1;
					if ($time > 0.5) {
						$sev = 2;
						if ($time > 1) {
							$sev = 3;
						}
					}
				}
			}
			t3lib_div::devLog($lbl . ($time ? ' time: ' . $time . ' s' : ''), $this->pObj->extKey, (int) $sev, $msg);
		} else {
			debug($msg, $lbl, $code_line, $msg['file:'], 3);
		}
	}


	/**
	 * returns an error message if some important settings are missing (template file, singlePid, pidList, code)
	 *
	 * @return	string		the error message
	 */
	function displayErrors() {

		/**
		 * TODO: 07.05.2009
		 * localize
		 */

		if (count($this->pObj->errors) >= 2) {
			$msg = '--> Did you include the static TypoScript template (\'News settings\') for tt_news?';
		}
		return '<div style="border:2px solid red; padding:10px; margin:10px;"><img src="typo3/gfx/icon_warning2.gif" />
				<strong>plugin.tt_news ERROR:</strong><br />' . implode('<br /> ', $this->pObj->errors) . '<br />' . $msg . '</div>';
	}


	/**
	 * cleans the content for rss feeds. removes '&nbsp;' and '?;' (dont't know if the scond one matters in real-life).
	 * The rest of the cleaning/character-conversion is done by the stdWrap functions htmlspecialchars,stripHtml and csconv.
	 * For details see http://typo3.org/documentation/document-library/doc_core_tsref/stdWrap/
	 *
	 * @param	string		$str: input string to clean
	 * @return	string		the cleaned string
	 */
	function cleanXML($str) {
		$cleanedStr = preg_replace(array('/&nbsp;/', '/&;/', '/</', '/>/'), array(' ', '&amp;;', '&lt;', '&gt;'), $str);
		return $cleanedStr;
	}


	/**
	 * Obtains current extension version (for use with compatVersion)
	 *
	 * @return	string		Extension version (for example, '2.5.1')
	 */
	function getCurrentVersion() {
		$_EXTKEY = $this->pObj->extKey;
		// require_once fails if the plugin is executed multiple times
		require (t3lib_extMgm::extPath($_EXTKEY, 'ext_emconf.php'));
		return $EM_CONF[$_EXTKEY]['version'];
	}


	/**
	 * Generates the date format needed for Atom feeds
	 * see: http://www.w3.org/TR/NOTE-datetime (same as ISO 8601)
	 * in php5 it would be so easy: date('c', $row['datetime']);
	 *
	 * @param	integer		the datetime value to be converted to w3c format
	 * @return	string		datetime in w3c format
	 */
	function getW3cDate($datetime) {
		$offset = date('Z', $datetime) / 3600;
		if ($offset < 0) {
			$offset *= - 1;
			if ($offset < 10) {
				$offset = '0' . $offset;
			}
			$offset = '-' . $offset;
		} elseif ($offset == 0) {
			$offset = '+00';
		} elseif ($offset < 10) {
			$offset = '+0' . $offset;
		} else {
			$offset = '+' . $offset;
		}
		return strftime('%Y-%m-%dT%H:%M:%S', $datetime) . $offset . ':00';
	}

	/**
	 * @return tx_ttnews_compatibility
	 */
	protected function compatibility() {
		return tx_ttnews_compatibility::getInstance();
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_helpers.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_helpers.php']);
}
?>