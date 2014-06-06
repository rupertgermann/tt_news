<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
* newsLib.inc
*
* Creates a news system.
* $Id: class.tx_ttnews.php,v 1.15 2004/07/11 10:31:26 rupertgermann Exp $
* 
* TypoScript config:
* - See static_template "plugin.tt_news"
* - See TS_ref.pdf
*
* @author Kasper Skårhøj <kasper@typo3.com>
*/
/**
* [CLASS/FUNCTION INDEX of SCRIPT]
*/
 
require_once(PATH_t3lib.'class.t3lib_xml.php');
require_once(PATH_tslib.'class.tslib_pibase.php');
 
class tx_ttnews extends tslib_pibase {
	var $cObj;
	// The backReference to the mother cObj object set at call time
	 
	// Default plugin variables:
	var $prefixId = 'tx_ttnews';
	// Same as class name
	var $scriptRelPath = 'pi/class.tx_ttnews.php'; // Path to this script relative to the extension dir.
	var $extKey = 'tt_news'; // The extension key.
	 
	var $tt_news_uid;
	var $conf;
	var $conf2;
	var $config;
	var $alternativeLayouts;
	var $pid;
	var $allowCaching;
	var $catExclusive;
	var $searchFieldList = 'short,bodytext,author,keywords,links,imagecaption,title';
	var $theCode = '';
	 
	var $categories = array();
	// Is initialized with the categories of the news system
	var $pageArray = array();
	// Is initialized with an array of the pages in the pid-list
	 
	/**
	* Main news function for XML news feed
	*
	* @param [type]  $content: ...
	* @param [type]  $conf: ...
	* @return [type]  ...
	*/
	function main_xmlnewsfeed($content, $conf) {
		$className = t3lib_div::makeInstanceClassName('t3lib_xml');
		$xmlObj = new $className('typo3_xmlnewsfeed');
		$xmlObj->setRecFields('tt_news', 'title,datetime'); // More fields here...
		$xmlObj->renderHeader();
		$xmlObj->renderRecords('tt_news', $this->getStoriesResult());
		$xmlObj->renderFooter();
		return $xmlObj->getResult();
	}
	 
	/**
	* [Describe function...]
	*
	* @return [type]  ...
	*/
	function getStoriesResult() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_news', 'pid='.intval($GLOBALS['TSFE']->id).$this->cObj->enableFields('tt_news'), '', 'datetime DESC');
		return $res;
	}
	 
	 
	/**
	* Init Function here all the needed configuration Values where stored in class variables..
	* init is normaly called one time in main function!
	*
	* @param [type]  $conf: ...
	* @return [type]  ...
	*/
	function init_news($conf) {
		// *************************************
		// *** getting configuration values:
		// *************************************
		$this->conf = $conf; //store configuration
		$this->tt_news_uid = intval(t3lib_div::_GP('tt_news')); //Get the submitted uid of a news (if any)
		//Get number of alternative Layouts (loop layout in Archivelist and List view) default is 2:
		$this->alternativeLayouts = intval($this->conf['alternatingLayouts']) > 0 ? intval($this->conf['alternatingLayouts']) :
		 2;
		 
		// Loading language-labels
		$this->pi_loadLL();
		 
		// Init FlexForm configuration for plugin:
		$this->pi_initPIflexForm();
		$this->enableFields = $this->cObj->enableFields('tt_news');
		 
		// pid_list is the pid/list of pids from where to fetch the news items.
		$this->config['pid_list'] = trim($this->cObj->stdWrap($this->conf['pid_list'], $this->conf['pid_list.']));
		$this->config['pid_list'] = $this->config['pid_list'] ? implode(t3lib_div::intExplode(',', $this->config['pid_list']), ',') :
		 $GLOBALS['TSFE']->id;
		 
		 
		$recursive = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'recursive', 'sDEF');
		$this->config['recursive'] = is_numeric($recursive)?$recursive:
		$this->cObj->stdWrap($conf['recursive'], $conf['recursive.']);
		list($pid) = explode(',', $this->config['pid_list']);
		$this->pid = $pid;
		 
		/**
		*  "CODE" decides what is rendered:
		*  codes can be added by TS or FF with priority on FF
		*/
		$this->config['code'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'what_to_display', 'sDEF');
		$this->config['code'] = $this->config['code']?$this->config['code']:
		$this->cObj->stdWrap($this->conf['code'], $this->conf['code.']);
		 
		$this->config['select_deselect_categories'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'select_deselect_categories', 'sDEF');
		$this->config['category_selection'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'category_selection', 'sDEF');
		$this->config['archive'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'archive', 'sDEF');
		 
		//Set the global flags to this values, they are needed for search query building (the flags could be overriden, for example in CODE LATEST)
		$this->catExclusive = $this->config['category_selection'];
		//arc exclusiv means: -1=SHOW NON-ARCHIVED; 0=dont care; 1=SHOW ARCHIVED
		$this->arcExclusive = $this->config['archive'];
		 
		$this->config['limit'] = t3lib_div::intInRange($conf['limit'], 0, 1000);
		$this->config['limit'] = $this->config['limit'] ? $this->config['limit'] :
		 50;
		$this->allowCaching = $this->conf['allowCaching']?1:
		0;
		 
		$catImageMode = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'catImageMode', 's_category');
		$catTextMode = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'catTextMode', 's_category');
		$catImageMaxWidth = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'catImageMaxWidth', 's_category');
		$catImageMaxHeight = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'catImageMaxHeight', 's_category');
		$maxCatImages = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxCatImages', 's_category');
		$catTextLength = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'catTextLength', 's_category');
		$maxCatTexts = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxCatTexts', 's_category');
		$this->config['catImageMode'] = (is_numeric($catImageMode)?$catImageMode:$this->conf['catImageMode']);
		$this->config['catTextMode'] = (is_numeric($catTextMode)?$catTextMode:$this->conf['catTextMode']);
		$this->config['catImageMaxWidth'] = ($catImageMaxWidth?$catImageMaxWidth:$this->conf['catImageMaxWidth']);
		$this->config['catImageMaxHeight'] = ($catImageMaxHeight?$catImageMaxHeight:$this->conf['catImageMaxHeight']);
		$this->config['maxCatImages'] = (is_numeric($maxCatImages)?$maxCatImages:$this->conf['maxCatImages']);
		$this->config['catTextLength'] = (is_numeric($catTextLength)?$catTextLength:$this->conf['catTextLength']);
		$this->config['maxCatTexts'] = (is_numeric($maxCatTexts)?$maxCatTexts:$this->conf['maxCatTexts']);
		 
		//Others which could be set in Flexform or TS:
		$PIDitemDisplay = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'PIDitemDisplay', 'sDEF');
		//If FlexForm Value is given then overwrite
		$this->config['PIDitemDisplay'] = $PIDitemDisplay ? $PIDitemDisplay :
		 $this->conf['PIDitemDisplay'];
		 
		$this->config['noNewsIdMsg'] = $this->conf['noNewsIdMsg'];
		 
		// reverse AMENU order
		$this->config['reverseAMenu'] = $this->conf['reverseAMenu'];
		 
		// template is read.
		$templateflex_file = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template_file', 's_template');
		$this->templateCode = $this->cObj->fileResource($templateflex_file?"uploads/tx_ttnews/".$templateflex_file:$this->conf['templateFile']);
		// globally substituted markers, fonts and colors.
		$splitMark = md5(microtime());
		$globalMarkerArray = array();
		list($globalMarkerArray['###GW1B###'], $globalMarkerArray['###GW1E###']) = explode($splitMark, $this->cObj->stdWrap($splitMark, $conf['wrap1.']));
		list($globalMarkerArray['###GW2B###'], $globalMarkerArray['###GW2E###']) = explode($splitMark, $this->cObj->stdWrap($splitMark, $conf['wrap2.']));
		$globalMarkerArray['###GC1###'] = $this->cObj->stdWrap($conf['color1'], $conf['color1.']);
		$globalMarkerArray['###GC2###'] = $this->cObj->stdWrap($conf['color2'], $conf['color2.']);
		$globalMarkerArray['###GC3###'] = $this->cObj->stdWrap($conf['color3'], $conf['color3.']);
		 
		// Substitute Global Marker Array
		$this->templateCode = $this->cObj->substituteMarkerArray($this->templateCode, $globalMarkerArray);
	}
	 
	/**
	* Main news function.
	*
	* @param [type]  $content: ...
	* @param [type]  $conf: ...
	* @return [type]  ...
	*/
	function main_news($content, $conf) {
		 
		 
		$GLOBALS['TSFE']->set_no_cache();
		 
		$this->init_news($conf);
		 
		// If the current record should be displayed.
		//It is in content element "insert record"
		$this->config['displayCurrentRecord'] = $this->conf['displayCurrentRecord'];
		if ($this->config['displayCurrentRecord']) {
			# rg: added the possibility to change the template, used for 'display current record'.
			# if the value is empty, the code is 'single'
			$this->config['code'] = $this->conf['defaultCode']?trim($this->conf['defaultCode']):
			"SINGLE";
			$this->tt_news_uid = $this->cObj->data['uid'];
		}
		 
		// *************************************
		// *** doing the things...:
		// *************************************
		 
		//danp seems not to be need: $this->dontParseContent = $this->conf['dontParseContent'];
		 
		//make lokal cObj Instance needed for all the Templateparsing stuff in diffrent functions:
		 
		$this->local_cObj = t3lib_div::makeInstance('tslib_cObj');
		// Local cObj.
		 
		$codes = t3lib_div::trimExplode(',', $this->config['code']?$this->config['code']:$this->conf['defaultCode'], 1);
		if (!count($codes)) $codes = array('');
			while (list(, $theCode) = each($codes)) {
			$theCode = (string)strtoupper(trim($theCode));
			$this->theCode = $theCode;
			 
			 
			#t3lib_div::debug(strtoupper($GLOBALS['TSFE']->config['config']['language']));
			 
			switch($theCode) {
				 
				case 'SINGLE':
				$content .= $this->single_view();
				break;
				case 'LATEST':
				case 'LIST':
				case 'SEARCH':
				$content .= $this->news_list();
				break;
				case 'AMENU':
				$content .= $this->news_archiveMenu();
				break;
				default:
				//Adds hook for processing of extra codes
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraCodesHook'])) {
					foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraCodesHook'] as $_classRef) {
						$_procObj = &t3lib_div::getUserObj($_classRef);
						$content .= $_procObj->extraCodesProcessor($this);
					}
				} else {
					$langKey = strtoupper($GLOBALS['TSFE']->config['config']['language']);
					$helpTemplate = $this->cObj->fileResource('EXT:tt_news/pi/news_help.tmpl');
					 
					// Get language version
					$helpTemplate_lang = '';
					if ($langKey) {
						$helpTemplate_lang = $this->cObj->getSubpart($helpTemplate, "###TEMPLATE_".$langKey.'###');
					}
					$helpTemplate = $helpTemplate_lang ? $helpTemplate_lang :
					 $this->cObj->getSubpart($helpTemplate, '###TEMPLATE_DEFAULT###');
					 
					// Markers and substitution:
					$markerArray['###CODE###'] = $this->theCode;
					$markerArray['###EXTPATH###'] = $GLOBALS['TYPO3_LOADED_EXT']['tt_news']['siteRelPath'];
					$content .= $this->cObj->substituteMarkerArray($helpTemplate, $markerArray);
				}
				break;
			}
		}
		return $content;
	}
	 
	/**
	* News archive menu
	*
	* @return [type]  ...
	*/
	function news_archiveMenu() {
		$this->arcExclusive = 1;
		$selectConf = $this->getSelectConf('', 1);
		 
		// Finding maximum and minimum values:
		$selectConf['selectFields'] = 'max(datetime) as maxval, min(datetime) as minval';
		$res = $this->cObj->exec_getQuery('tt_news', $selectConf);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if ($row['minval']) {
			$dateArr = array();
			$arcMode = $this->conf['archiveMode'] ? $this->conf['archiveMode'] :
			 "month";
			$c = 0;
			do {
				switch($arcMode) {
					case 'month':
					$theDate = mktime (0, 0, 0, date('m', $row['minval'])+$c, 1, date('Y', $row['minval']));
					break;
					case 'quarter':
					$theDate = mktime (0, 0, 0, floor(date('m', $row['minval'])/3)+1+(3 * $c), 1, date('Y', $row['minval']));
					break;
					case 'year':
					$theDate = mktime (0, 0, 0, 1, 1, date('Y', $row['minval'])+$c);
					break;
				}
				$dateArr[] = $theDate;
				$c++;
				if ($c > 1000) break;
			}
			 while ($theDate < $GLOBALS['SIM_EXEC_TIME']);
			 
			reset($dateArr);
			$periodAccum = array();
			 
			$selectConf2['where'] = $selectConf['where'];
			while (list($k, $v) = each($dateArr)) {
				if (!isset($dateArr[$k+1])) {
					break;
				}
				$periodInfo = array();
				$periodInfo['start'] = $dateArr[$k];
				$periodInfo['stop'] = $dateArr[$k+1]-1;
				$periodInfo['HRstart'] = date('d-m-Y', $periodInfo['start']);
				$periodInfo['HRstop'] = date('d-m-Y', $periodInfo['stop']);
				$periodInfo['quarter'] = floor(date('m', $dateArr[$k])/3)+1;
				 
				// FInding maximum and minimum values:
				$selectConf['selectFields'] = 'count(distinct(uid))';
				$selectConf['where'] = $selectConf2['where'].' AND datetime>='.$periodInfo['start'].' AND datetime<'.$periodInfo['stop'];
				$res = $this->cObj->exec_getQuery('tt_news', $selectConf);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$periodInfo['count'] = $row[0];
				 
				if (!$this->conf['archiveMenuNoEmpty'] || $periodInfo['count']) {
					$periodAccum[] = $periodInfo;
				}
			}
			 
			 
			 
			 
			$t['total'] = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_ARCHIVE###'));
			$t['item'] = $this->getLayouts($t['total'], $this->alternativeLayouts, 'MENUITEM');
			$cc = 0;
			 
			$veryLocal_cObj = t3lib_div::makeInstance('tslib_cObj');
			// Local cObj.
			// reverse amenu oder if 'reverseAMenu' is given
			if ($this->config['reverseAMenu']) {
				arsort($periodAccum);
			}
			 
			reset($periodAccum);
			$itemsOut = '';
			while (list(, $pArr) = each($periodAccum)) {
				// Print Item Title
				$wrappedSubpartArray = array();
				$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="'.$this->getLinkUrl(0).'&amp;pS='.$pArr['start'].'&amp;pL='.($pArr['stop']-$pArr['start']).'&amp;arc=1">', '</a>');
				 
				$markerArray = array();
				$veryLocal_cObj->start($pArr, '');
				$markerArray['###ARCHIVE_TITLE###'] = $veryLocal_cObj->cObjGetSingle($this->conf['archiveTitleCObject'], $this->conf['archiveTitleCObject.'], 'archiveTitle');
				$markerArray['###ARCHIVE_COUNT###'] = $pArr['count'];
				$markerArray['###ARCHIVE_ITEMS###'] = $this->pi_getLL('archiveItems');
		
				$itemsOut .= $this->cObj->substituteMarkerArrayCached($t['item'][($cc%count($t['item']))], $markerArray, array(), $wrappedSubpartArray);
				$cc++;
			}
			 
			// Reset:
			$subpartArray = array();
			$wrappedSubpartArray = array();
			$markerArray = array();
			$markerArray['###ARCHIVE_HEADER###'] = $this->local_cObj->stdWrap($this->pi_getLL('archiveHeader'), $lConf['archiveHeader_stdWrap.']);
			 
			// Set content
			$subpartArray['###CONTENT###'] = $itemsOut;
			$content = $this->cObj->substituteMarkerArrayCached($t['total'], $markerArray, $subpartArray, $wrappedSubpartArray);
		} else {
			$markerArray['###ARCHIVE_HEADER###'] = $this->local_cObj->stdWrap($this->pi_getLL('archiveHeader'), $lConf['archiveHeader_stdWrap.']);
			$markerArray['###ARCHIVE_EMPTY_MSG###'] = $this->local_cObj->stdWrap($this->pi_getLL('archiveEmptyMsg'), $lConf['archiveEmptyMsg_stdWrap.']);
			$noItemsMsg = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_ARCHIVE_NOITEMS###'));
$content = $this->cObj->substituteMarkerArrayCached($noItemsMsg, $markerArray);
		}
		return $content;
	}
	 
	/**
	* [Describe function...]
	*
	* @return [type]  ...
	*/
	function single_view() {
		#$this->enableFields = $this->cObj->enableFields('tt_news');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_news', 'uid='.intval($this->tt_news_uid).' AND type=0'.$this->enableFields); // type=0->only real news.
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		 
		 
		if (is_array($row)) {
			$this->setPidlist(intval($row['pid']));
			$this->generatePageArray();
			 
			// Get the subpart code
			$item = '';
			if ($this->config['displayCurrentRecord']) {
				 
				#$row=$this->cObj->data;
				$item = trim($this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_SINGLE_RECORDINSERT###')));
			}
			if (!$item) {
				$item = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_SINGLE###'));
			}
			 
			// Fill marker arrays
			$wrappedSubpartArray = array();
			if ($backPid = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'backPid', 'sDEF')) {
			} elseif($backPid = $this->conf['backPid']) {
			} elseif($backPid = t3lib_div::_GP('backPID')) {
			}
			$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="'.$this->getLinkUrl($backPid).'">', '</a>');
			$markerArray = $this->getItemMarkerArray($row, 'displaySingle');
			// Substitute
			$content = $this->cObj->substituteMarkerArrayCached($item, $markerArray, array(), $wrappedSubpartArray);
		} else {
			// if singleview is shown with no tt_news uid given in the url, an error message is displayed.
			// you can configure your own message by setting the TS var 'noNewsIdMsg'.
			$noNewsIdMsg =  $this->local_cObj->stdWrap($this->pi_getLL('noNewsIdMsg'), $lConf['noNewsIdMsg_stdWrap.']);
			$content .= $noNewsIdMsg?$noNewsIdMsg:'Wrong parameters, GET/POST var \'tt_news\' was missing.';
			
		}
		return $content;
	}
	 
	 
	/**
	* Displaying single news/ the news list / searching
	*
	* Things happen: get the query parameters (add where if search was performed)
	* First exec count query to get the numbers of results
	* If more than one Go into the rendering of the List:
	*   Get the general Markers for each Iitem and fill the contentarray (Templatebased)
	* Check if a browsebox should be displayed
	*
	* @return [type]  ...
	*/
	function news_list() {
		$theCode = $this->theCode;
		/*  $this->setPidlist($this->config['pid_list']);    // The list of pid's we're operation on. All tt_products records must be in the pidlist in order to be selected.
		$this->initRecursive($this->config['recursive']);
		$this->initCategories();
		$this->generatePageArray();
		 
		debug($this->pageArray);
		debug($this->categories);
		 
		*/
		$this->initCategories();
		 
		$where = '';
		$content = '';
		 
		// in the switch blocks the things which are diffrent for the codefields
		switch($theCode) {
			case 'LATEST':
			$prefix_display = 'displayLatest';
			$templateName = 'TEMPLATE_LATEST';
			$this->arcExclusive = -1; // Only latest, non archive news
			if (intval($this->conf['latestLimit'])) $this->config['limit'] = intval($this->conf['latestLimit']);
				$showList = true;
			break;
			case 'LIST':
			$showList = true;
			$prefix_display = 'displayList';
			$templateName = 'TEMPLATE_LIST';
			break;
			case 'SEARCH':
			$prefix_display = 'displayList';
			$templateName = 'TEMPLATE_LIST';
			 
			// Get search subpart
			$t['search'] = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_SEARCH###'));
			// Substitute a few markers
			$out = $t['search'];
			$out = $this->cObj->substituteMarker($out, '###FORM_URL###', $this->getLinkUrl($this->conf['PIDsearch']));
			$out = $this->cObj->substituteMarker($out, '###SWORDS###', htmlspecialchars(t3lib_div::_GP('swords')));
			// Add to content
			$content .= $out;
			 
			//if searchword is given then make the query where and set the flag to show the list
			if (t3lib_div::_GP('swords')) {
				$where = $this->searchWhere(trim(t3lib_div::_GP('swords')));
				$showList = true;
			}
			break;
			 
		}
		 
		// Allowed to show the listing?
		// AND periodStart must be set when listing from the archive.
		 
		if ($showList && !($this->arcExclusive > 0 && !t3lib_div::_GP('pS') && $theCode != 'SEARCH')) {
			 
			/*
			* rg: this change makes it possible to display news-items, which are included by the content
			*   element 'insert records', either as single item (see changes above
			*   in the single part of this function), or as a list item.
			*   configuration is done in TS, the default template is SINGLE.
			*   element in your template) as the news-search and the search results.
			*/
			if ($this->config['displayCurrentRecord'] && $this->tt_news_uid) {
				$this->config['pid_list'] = $this->cObj->data['pid'];
				$where = 'AND uid='.$this->cObj->data['uid'];
			}
			 
			//Get the Listqueryparameters
			$selectConf = $this->getSelectConf($where);
			 
			// performing query to count all news (we need to know it for browsing):
			$selectConf['selectFields'] = 'count(distinct(uid))'; //count(*)
			$res = $this->cObj->exec_getQuery('tt_news', $selectConf);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			$newsCount = $row[0];
			 
			//Only do something if the queryresult is not empty
			if ($newsCount > 0) {
				//Init the Templateparts
				// $t['total'] is the part which is defined by the Code (TEMPLATE_LATEST and so on)
				// $t['item'] is an array with the alternated Subparts (NEWS, NEWS_1 and so on)
				$t = array();
				$t['total'] = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###'.$templateName.'###'));
				$t['item'] = $this->getLayouts($t['total'], $this->alternativeLayouts, 'NEWS');
				 
				// range check to current newsCount
				//get begin at for browsing
				$begin_at = t3lib_div::intInRange(t3lib_div::_GP('begin_at'), 0, 100000);
				$begin_at = t3lib_div::intInRange(($begin_at >= $newsCount) ? ($newsCount-$this->config['limit']) : $begin_at, 0);
				 
				// performing query for display:
				$selectConf['groupBy'] = 'uid';
				$selectConf['orderBy'] = 'datetime DESC';
				$selectConf['selectFields'] = '*';
				$selectConf['max'] = intval($this->config['limit']+1);
				/**
				*  rg: added this to exclude the LATEST template from changing its content with the pagebrowser
				*   this can be overridden by setting the conf var latestWithPagebrowser
				*/
				if ($theCode != 'LATEST' && !$this->conf['latestWithPagebrowser']) {
					$selectConf['begin'] = $begin_at;
				}
				 
				 
				//debug($this->cObj->getQuery('tt_news',$selectConf,true));
				 
				//*******The total template must be filled:****
				 
				// Reset:
				$subpartArray = array();
				$wrappedSubpartArray = array();
				$markerArray = array();
				 
				//The important think include the Newslist in the CONTENT subpart (perhaps use & for not wasting memory)
				$subpartArray['###CONTENT###'] = $this->get_content_news_list($t['item'], $selectConf, $prefix_display);
				 
				#$markerArray['###CATEGORY_TITLE###'] = ''; // Something here later...
				$markerArray['###GOTOARCHIVE###'] = $this->pi_getLL('goToArchive');
				$markerArray['###LATEST_HEADER###'] = $this->pi_getLL('latestHeader');
				$wrappedSubpartArray['###LINK_ARCHIVE###'] = $this->local_cObj->typolinkWrap($this->conf['archiveTypoLink.']);
				 
				$url = $this->getLinkUrl('', 'begin_at');
				 
				//BROWSEBOXRENDERING -
				/**
				*  rg: added getLL for the pagebrowser. if the getLL values don't exist,
				*  all works like before.
				*/
				if ($newsCount > $begin_at+$this->config['limit']) {
					$next = ($begin_at+$this->config['limit'] > $newsCount) ? $newsCount-$this->config['limit'] :
					 $begin_at+$this->config['limit'];
					 
					 
					 
					$wrappedSubpartArray['###LINK_NEXT###'] = array('<a href="'.$url.'&amp;begin_at='.$next.'">'.$this->pi_getLL('pbrLinkNext'), '</a>');
				} else {
					$subpartArray['###LINK_NEXT###'] = '';
				}
				if ($begin_at) {
					$prev = ($begin_at-$this->config['limit'] < 0) ? 0 :
					 $begin_at-$this->config['limit'];
					$wrappedSubpartArray['###LINK_PREV###'] = array('<a href="'.$url.'&amp;begin_at='.$prev.'">'.$this->pi_getLL('pbrLinkPrev'), '</a>');
				} else {
					$subpartArray['###LINK_PREV###'] = '';
				}
				$markerArray['###BROWSE_LINKS###'] = '';
				if ($newsCount > $this->config['limit'] ) {
					// there is more than one page, so let's browse
					for ($i = 0 ; $i < ($newsCount/$this->config['limit']); $i++) {
						if (($begin_at >= $i * $this->config['limit']) && ($begin_at < $i * $this->config['limit']+$this->config['limit'])) {
							$markerArray['###BROWSE_LINKS###'] .= ' <b>'.$this->pi_getLL('pbrPage').(string)($i+1).'</b> ';
						} else {
							$markerArray['###BROWSE_LINKS###'] .= ' <a href="'.$url.'&amp;begin_at='.(string)($i * $this->config['limit']).'">'.$this->pi_getLL('pbrPage').(string)($i+1).'</a> ';
						}
					}
				}
				 
				 
				$content .= $this->cObj->substituteMarkerArrayCached($t['total'], $markerArray, $subpartArray, $wrappedSubpartArray);
			} elseif ($where) {
				//No results but a searchword was given:
				$markerArray['###SEARCH_EMPTY_MSG###'] = $this->local_cObj->stdWrap($this->pi_getLL('searchEmptyMsg'), $this->conf['searchEmptyMsg_stdWrap.']);	
				$searchEmptyMsg = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###ITEM_SEARCH_EMPTY###'));					
				$content .= $this->cObj->substituteMarkerArrayCached($searchEmptyMsg, $markerArray);
			}
		}
		return $content;
	}
	 
	 
	 
	 
	 
	 
	 
	 
	/**
	* For better overview this function is split out from news_list()
	* It gets the selectConf and a array with templateparts
	* the markers in this subparts get filled with the help of function getItemMarkerArray()
	*
	* @param [type]  $itemparts: ...
	* @param [type]  $selectConf: ...
	* @param [type]  $prefix_display: ...
	* @return [type]  ...
	*/
	function get_content_news_list($itemparts, $selectConf, $prefix_display) {
		//Make the Listingquery
		$res = $this->cObj->exec_getQuery('tt_news', $selectConf);
		// Getting elements
		$itemsOut = '';
		 
		$cc = 0;
		#debug($selectConf);
		$itemLinkTarget = $this->conf['itemLinkTarget'] ? 'target="'.$this->conf['itemLinkTarget'].'"' :
		 "";
		 
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			// Print Item Title
			$wrappedSubpartArray = array();
			if ($row['type'] == 1 || $row['type'] == 2) {
				//News type article or external url
				$this->local_cObj->setCurrentVal($row['type'] == 1 ? $row['page'] : $row['ext_url']);
				$wrappedSubpartArray['###LINK_ITEM###'] = $this->local_cObj->typolinkWrap($this->conf['pageTypoLink.']);
			} else {
				$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="'.$this->getLinkUrl($this->config['PIDitemDisplay']).'&amp;tt_news='.$row['uid'].'" '.$itemLinkTarget.'>', '</a>');
			}
			$markerArray = $this->getItemMarkerArray($row, $prefix_display);
			 
			//Store the result of template parsing in the Var $itemsOut, use the alternating layouts
			$itemsOut .= $this->cObj->substituteMarkerArrayCached($itemparts[($cc%count($itemparts))], $markerArray, array(), $wrappedSubpartArray);
			$cc++;
			if ($cc == $this->config['limit']) {
				break;
			}
		}
		return $itemsOut;
	}
	 
	 
	/**
	* Returns a url for use in forms and links
	*
	* @param [type]  $id: ...
	* @param [type]  $excludeList: ...
	* @return [type]  ...
	*/
	function getLinkUrl($id = "", $excludeList = '') {
		$queryString = array();
		$queryString['id'] = 'id='.($id ? $id : $GLOBALS['TSFE']->id);
		// Andreas Schwarzkopf  $queryString['type']= $GLOBALS['TSFE']->type ? 'type='.$GLOBALS['TSFE']->type : "";
		// der TypoScript-Setup-Wert itemLinkType wird ausgelesen, wenn nicht vorhanden, der aktuelle Type-Wert des Fensters
		if ($this->conf['itemLinkType']) {
			$itemLinkType = 'type='.$this->conf['itemLinkType'];
		} else {
			if ($GLOBALS['TSFE']->type) {
				$itemLinkType = 'type='.$GLOBALS['TSFE']->type;
			} else {
				$itemLinkType = '';
			}
		}
		$queryString['type'] = $itemLinkType;
		$queryString['backPID'] = 'backPID='.$GLOBALS['TSFE']->id;
		$queryString['begin_at'] = t3lib_div::GPvar('begin_at') ? 'begin_at='.t3lib_div::GPvar('begin_at') :
		 "";
		$queryString['swords'] = t3lib_div::GPvar('swords') ? "swords=".rawurlencode(t3lib_div::GPvar('swords')) :
		 "";
		$queryString['pS'] = t3lib_div::GPvar('pS') ? "pS=".intval(t3lib_div::GPvar('pS')) :
		""; // period start
		$queryString['pL'] = t3lib_div::GPvar('pL') ? "pL=".intval(t3lib_div::GPvar('pL')) :
		""; // Period length
		$queryString['arc'] = t3lib_div::GPvar('arc') ? "arc=".intval(t3lib_div::GPvar('arc')) :
		""; // Archive flag: 0 = don't care, -1 = latest, 1 = archive
		$queryString['cat'] = t3lib_div::GPvar('cat') ? "cat=".intval(t3lib_div::GPvar('cat')) :
		""; // Category uid, 0 = any
		 
		reset($queryString);
		while (list($key, $val) = each($queryString)) {
			if (!$val || ($excludeList && t3lib_div::inList($excludeList, $key))) {
				unset($queryString[$key]);
			}
		}
		return $GLOBALS['TSFE']->absRefPrefix.'index.php?'.implode($queryString, '&amp;');
	}
	 
	/**
	* [Describe function...]
	*
	* @param [type]  $where: ...
	* @param [type]  $noPeriod: ...
	* @return [type]  ...
	*/
	function getSelectConf($where, $noPeriod = 0) {
		 
		 
		 
		$this->setPidlist($this->config['pid_list']);
		 
		$this->initRecursive($this->config['recursive']);
		$this->generatePageArray();
		 
		// Get news
		$selectConf = Array();
		$selectConf['pidInList'] = $this->pid_list;
		$selectConf['where'] = '1=1 '.$where;
		 
		# rg: changed, to make it possible to display an amenu and a latest content element at the same page
		// Archive
		if ($this->arcExclusive > 0) {
			if (intval(t3lib_div::_GP('arc'))) {
				$this->arcExclusive = intval(t3lib_div::_GP('arc'));
			}
			 
			// Period (this is moved from line ~585 )
			if (!$noPeriod && intval(t3lib_div::_GP('pS'))) {
				$selectConf['where'] .= ' AND tt_news.datetime>'.intval(t3lib_div::_GP('pS'));
				if (intval(t3lib_div::_GP('pL'))) {
					$selectConf['where'] .= ' AND tt_news.datetime<'.(intval(t3lib_div::_GP('pS'))+intval(t3lib_div::_GP('pL')));
				}
			}
		}
		if ($this->arcExclusive) {
			if ($this->conf['enableArchiveDate']) {
				if ($this->arcExclusive < 0) {
					// latest
					$selectConf['where'] .= ' AND (tt_news.archivedate=0 OR tt_news.archivedate>'.$GLOBALS['SIM_EXEC_TIME'].')';
				} elseif ($this->arcExclusive > 0) {
					$selectConf['where'] .= ' AND tt_news.archivedate<'.$GLOBALS['SIM_EXEC_TIME'];
				}
			}
			if ($this->conf['datetimeDaysToArchive']) {
				$theTime = $GLOBALS['SIM_EXEC_TIME']-intval($this->conf['datetimeDaysToArchive']) * 3600 * 24;
				if ($this->arcExclusive < 0) {
					// latest
					$selectConf['where'] .= ' AND (tt_news.datetime=0 OR tt_news.datetime>'.$theTime.')';
				} elseif ($this->arcExclusive > 0) {
					$selectConf['where'] .= ' AND tt_news.datetime<'.$theTime;
				}
			}
		}
		// Category
		/*  $codes=t3lib_div::trimExplode(',', $this->config['code']?$this->config['code']:$this->conf['defaultCode'],1);
		if (count($codes)) {
		while (list(,$theCode)=each($codes)) {
		list($theCode,$cat,$aFlag) = explode('/',$theCode);
		}
		}*/
		if (intval(t3lib_div::_GP('cat'))) {
			$this->catExclusive = intval(t3lib_div::_GP('cat'));
			$this->config['category_selection'] = intval(t3lib_div::_GP('cat'));
		}
		if ($this->config['category_selection'] != '') {
			$selectConf['leftjoin'] = 'tt_news_cat_mm ON tt_news.uid = tt_news_cat_mm.uid_local';
			if ($this->config['select_deselect_categories'] != 1)$selectConf['where'] .= ' AND (IFNULL(tt_news_cat_mm.uid_foreign,0) '.($this->config['select_deselect_categories'] == 3?"NOT ":"").'IN ('.$this->config['category_selection'].'))';
			#   $selectConf['groupBy'] .= 'uid';
		}
		 
		return $selectConf;
	}
	 
	 
	 
	/**
	* Sets the pid_list internal var
	*
	* @param [type]  $pid_list: ...
	* @return [type]  ...
	*/
	function setPidlist($pid_list) {
		$this->pid_list = $pid_list;
	}
	 
	/**
	* Extends the internal pid_list by the levels given by $recursive
	*
	* @param [type]  $recursive: ...
	* @return [type]  ...
	*/
	function initRecursive($recursive) {
		if ($recursive) {
			// get pid-list if recursivity is enabled
			$pid_list_arr = explode(',', $this->pid_list);
			$this->pid_list = '';
			while (list(, $val) = each($pid_list_arr)) {
				$this->pid_list .= $val.','.$this->cObj->getTreeList($val, intval($recursive));
			}
			$this->pid_list = ereg_replace(",$", '', $this->pid_list);
		}
	}
	 
	/**
	* Getting all tt_news_cat categories into internal array
	*
	* @return [type]  ...
	*/
	function initCategories() {
		// Fetching catagories:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_news_cat LEFT JOIN tt_news_cat_mm ON tt_news_cat_mm.uid_foreign = tt_news_cat.uid', '1=1'.$this->cObj->enableFields('tt_news_cat'));
		echo mysql_error();
		$this->categories = array();
		$this->categorieImages = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if (isset($row['uid_local'])) {
				$this->categories[$row['uid_local']][] = array(
				'title' => $row['title'],
					'image' => $row['image'],
					'shortcut' => $row['shortcut'],
					'catid' => $row['uid_foreign'] );
			} else {
				$this->categories['0'][$row['uid']] = $row['title'];
			}
		}
	}
	 
	/**
	* Generates an array,->pageArray of the pagerecords from->pid_list
	*
	* @return [type]  ...
	*/
	function generatePageArray() {
		// Get pages (for category titles)
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title,uid,author,author_email', 'pages', 'uid IN ('.$this->pid_list.')');
		$this->pageArray = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$this->pageArray[$row['uid']] = $row;
		}
	}
	 
	/**
	* Fills in the markerArray with data for a product
	*
	* @param [type]  $row: ...
	* @param [type]  $textRenderObj: ...
	* @return [type]  ...
	*/
	function getItemMarkerArray ($row, $textRenderObj = 'displaySingle') {
		// Returns a markerArray ready for substitution with information for the tt_producst record, $row
		// config to use:
		$lConf = $this->conf[$textRenderObj.'.'];
		$this->local_cObj->start($row, 'tt_news');
		$imageNum = isset($lConf['imageCount']) ? $lConf['imageCount'] :
		 1;
		$imageNum = t3lib_div::intInRange($imageNum, 0, 100);
		$markerArray = array();
		 
		 
		// Get and set image:
		$theImgCode = '';
		$imgs = t3lib_div::trimExplode(',', $row['image'], 1);
		$imgsCaptions = explode(chr(10), $row['imagecaption']);
		reset($imgs);
		$cc = 0;
		while (list(, $val) = each($imgs)) {
			if ($cc == $imageNum) break;
			if ($val) {
				$lConf['image.']['file'] = 'uploads/pics/'.$val;
			}
			$theImgCode .= $this->local_cObj->IMAGE($lConf['image.']).$this->local_cObj->stdWrap($imgsCaptions[$cc], $lConf['caption_stdWrap.']);
			$cc++;
		}
		$markerArray['###NEWS_IMAGE###'] = '';
		if ($cc) {
			$markerArray['###NEWS_IMAGE###'] = $this->local_cObj->wrap(trim($theImgCode), $lConf['imageWrapIfAny']);
		}
		 
		
		$markerArray['###NEWS_UID###'] = $row['uid'];
		$markerArray['###NEWS_TITLE###'] = $this->local_cObj->stdWrap($row['title'], $lConf['title_stdWrap.']);
		$newsAuthor = $this->local_cObj->stdWrap($row['author'], $lConf['author_stdWrap.']);
		$markerArray['###NEWS_AUTHOR###'] = $row['author']?$this->pi_getLL('preAuthor').' '.$newsAuthor:'';
		$markerArray['###NEWS_EMAIL###'] = $this->local_cObj->stdWrap($row['author_email'], $lConf['email_stdWrap.']);
		$markerArray['###NEWS_DATE###'] = $this->local_cObj->stdWrap($row['datetime'], $lConf['date_stdWrap.']);
		$markerArray['###NEWS_TIME###'] = $this->local_cObj->stdWrap($row['datetime'], $lConf['time_stdWrap.']);
		$markerArray['###NEWS_AGE###'] = $this->local_cObj->stdWrap($row['datetime'], $lConf['age_stdWrap.']);
		$markerArray['###NEWS_SUBHEADER###'] = $this->formatStr($this->local_cObj->stdWrap($row['short'], $lConf['subheader_stdWrap.']));
		$markerArray['###NEWS_CONTENT###'] = $this->formatStr($this->local_cObj->stdWrap($row['bodytext'], $lConf['content_stdWrap.']));
		// Links
		$newsLinks = $this->formatStr($this->local_cObj->stdWrap($row['links'], $lConf['links_stdWrap.']));
		$markerArray['###NEWS_LINKS###'] = $newsLinks;
		$markerArray['###TEXT_LINKS###'] = $newsLinks?$this->local_cObj->stdWrap($this->pi_getLL('textLinks'), $lConf['newsLinksHeader_stdWrap.']):'';
		#?  $markerArray['###NEWS_LINKS###'] = $this->local_cObj->stdWrap($this->formatStr($row['links']),$lConf['links_stdWrap.']);
		
		
		$markerArray['###MORE###'] = $this->pi_getLL('more');
		$markerArray['###BACK_TO_LIST###'] = $this->pi_getLL('backToList');
		
		// related
		$relatedNews = $this->local_cObj->stdWrap($this->getRelated($row['uid']), $lConf['related_stdWrap.']);
		$markerArray['###NEWS_RELATED###'] = $relatedNews;
		$markerArray['###TEXT_RELATED###'] = $relatedNews ? $this->local_cObj->stdWrap($this->pi_getLL('textRelated'), $lConf['relatedHeader_stdWrap.']):''; 

		// Page fields:
		$markerArray['###PAGE_UID###'] = $row['pid'];
		$markerArray['###PAGE_TITLE###'] = $this->pageArray[$row['pid']]['title'];
		$markerArray['###PAGE_AUTHOR###'] = $this->local_cObj->stdWrap($this->pageArray[$row['pid']]['author'], $lConf['author_stdWrap.']);
		$markerArray['###PAGE_AUTHOR_EMAIL###'] = $this->local_cObj->stdWrap($this->pageArray[$row['pid']]['author_email'], $lConf['email_stdWrap.']);
		 
		//Adds hook for processing of extra item markers
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraItemMarkerHook'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraItemMarkerHook'] as $_classRef) {
				$_procObj = &t3lib_div::getUserObj($_classRef);
				$markerArray = $_procObj->extraItemMarkerProcessor($markerArray, $row, $lConf, $this);
			}
		}
		 
		// Pass to user defined function
		if ($this->conf['itemMarkerArrayFunc']) {
			$markerArray = $this->userProcess('itemMarkerArrayFunc', $markerArray);
		}
		$markerArray['###TEXT_CAT###'] = $this->pi_getLL('textCat');
		$markerArray['###TEXT_CAT_LATEST###'] = $this->pi_getLL('textCatLatest');
		
		$news_category = array();
		$theCatImgCode = '';
		$theCatImgCodeArray = array();
		if (isset($this->categories[$row['uid']]) && ($this->config['catImageMode'] != 0 or $this->config['catTextMode'] != 0)) {
			while (list ($key, $val) = each ($this->categories[$row['uid']])) {
				if ($this->config['catTextMode'] == 0) {
					$markerArray['###NEWS_CATEGORY###'] = '';
				} elseif($this->config['catTextMode'] == 1) {
					$news_category[] = $this->local_cObj->stdWrap($this->categories[$row['uid']][$key]['title'], $lConf['category_stdWrap.']);
				} elseif($this->config['catTextMode'] == 2) {
					$news_category[] = $this->local_cObj->stdWrap($this->cObj->getTypoLink($this->categories[$row['uid']][$key]['title'], $this->categories[$row['uid']][$key]['shortcut']), $lConf['category_stdWrap.']);
				} elseif($this->config['catTextMode'] == 3) {
					$news_category[] = $this->local_cObj->stdWrap($this->cObj->getTypoLink($this->categories[$row['uid']][$key]['title'], $GLOBALS['TSFE']->id, array('cat' => $this->categories[$row['uid']][$key]['catid'])), $lConf['category_stdWrap.']);
				}
				if ($this->config['catImageMode'] == 0 or empty($this->categories[$row['uid']][$key]['image'])) {
					$markerArray['###NEWS_CATEGORY_IMAGE###'] = '';
				} else {
					$lConf['image.']['file'] = 'uploads/pics/'.$this->categories[$row['uid']][$key]['image'];
					$lConf['image.']['file.']['maxW'] = intval($this->config['catImageMaxWidth']);
					$lConf['image.']['file.']['maxH'] = intval($this->config['catImageMaxHeight']);
					$lConf['image.']['stdWrap.']['spaceAfter'] = 0;
					if ($this->config['catImageMode'] != 1) {
						$lConf['image.']['stdWrap.']['typolink.']['parameter'] = ($this->config['catImageMode'] == 2?$this->categories[$row['uid']][$key]['shortcut']:$GLOBALS['TSFE']->id);
						$lConf['image.']['stdWrap.']['typolink.']['additionalParams'] = ($this->config['catImageMode'] == 2?"":"&cat=".$this->categories[$row['uid']][$key]['catid']);
					}
					//stdWrap.htmlSpecialChars = 1")??? for xml &amp;
					$lConf['image.']['altText'] = $this->categories[$row['uid']][$key]['title'];
					$theCatImgCodeArray[] = $this->local_cObj->IMAGE($lConf['image.']);
				}
			}
			if ($this->config['catTextMode'] != 0) {
				$news_category = implode(', ', array_slice($news_category, 0, intval($this->config['maxCatTexts'])));
				$markerArray['###NEWS_CATEGORY###'] = (strlen($news_category) < intval($this->config['catTextLength'])?$news_category:substr($news_category, 0, intval($this->config['catTextLength'])).'...');
			}
			if ($this->config['catImageMode'] != 0) {
				$theCatImgCode = implode('', array_slice($theCatImgCodeArray, 0, intval($this->config['maxCatImages'])));
				$markerArray['###NEWS_CATEGORY_IMAGE###'] = $this->local_cObj->wrap(trim($theCatImgCode), $lConf['imageWrapIfAny']);
			}
							
//				} elseif($this->conf['catTextMode']!=0) { //to show categories not defined by tt_news_cat_mm
//				$markerArray['###NEWS_CATEGORY###'] = $this->categories['0'][$row['category']];
//				$markerArray['###NEWS_CATEGORY_IMAGE###']='';*/
		} else {
			$markerArray['###NEWS_CATEGORY_IMAGE###'] = '';
			$markerArray['###NEWS_CATEGORY###'] = '';
			// clear the category text markers if the news item has no categories
			$markerArray['###TEXT_CAT###'] = '';
			$markerArray['###TEXT_CAT_LATEST###'] = '';
		}
		return $markerArray;
	}
	 
	/**
	* Gets related news.
	*
	* @param [type]  $uid: ...
	* @return [type]  ...
	*/
	function getRelated($uid) {
		$veryLocal_cObj = t3lib_div::makeInstance('tslib_cObj');
		// Local cObj.
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,title,short,datetime,archivedate', 'tt_news,tt_news_related_mm AS M', 'tt_news.uid=M.uid_foreign AND M.uid_local='.intval($uid));
		 
		$lines = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$veryLocal_cObj->start($row, 'tt_news');
			$lines[] = $veryLocal_cObj->cObjGetSingle($this->conf['getRelatedCObject'], $this->conf['getRelatedCObject.'], 'getRelated');
		}
		return implode('', $lines);
	}
	 
	/**
	* Calls user function
	*
	* @param [type]  $mConfKey: ...
	* @param [type]  $passVar: ...
	* @return [type]  ...
	*/
	function userProcess($mConfKey, $passVar) {
		if ($this->conf[$mConfKey]) {
			$funcConf = $this->conf[$mConfKey.'.'];
			$funcConf['parentObj'] = &$this;
			$passVar = $GLOBALS['TSFE']->cObj->callUserFunction($this->conf[$mConfKey], $funcConf, $passVar);
		}
		return $passVar;
	}
	 
	/**
	* Returning template subpart marker
	*
	* @param [type]  $subpartMarker: ...
	* @return [type]  ...
	*/
	function spMarker($subpartMarker) {
		$sPBody = substr($subpartMarker, 3, -3);
		$altSPM = '';
		if (isset($this->conf['altMainMarkers.'])) {
			$altSPM = trim($this->cObj->stdWrap($this->conf['altMainMarkers.'][$sPBody], $this->conf['altMainMarkers.'][$sPBody.'.']));
			$GLOBALS['TT']->setTSlogMessage("Using alternative subpart marker for '".$subpartMarker."': ".$altSPM, 1);
		}
		return $altSPM ? $altSPM :
		 $subpartMarker;
	}
	 
	 
	/**
	* Generates a search where clause.
	*
	* @param [type]  $sw: ...
	* @return [type]  ...
	*/
	function searchWhere($sw) {
		$where = $this->cObj->searchWhere($sw, $this->searchFieldList, 'tt_news');
		return $where;
	}
	 
	/**
	* Format string with nl2br and htmlspecialchars()
	*
	* @param [type]  $str: ...
	* @return [type]  ...
	*/
	function formatStr($str) {
		if (is_array($this->conf['general_stdWrap.'])) {
			$str = $this->local_cObj->stdWrap($str, $this->conf['general_stdWrap.']);
		}
		return $str;
	}
	 
	/**
	* Returns alternating layouts
	*
	* @param [type]  $templateCode: ...
	* @param [type]  $alternativeLayouts: ...
	* @param [type]  $marker: ...
	* @return [type]  ...
	*/
	function getLayouts($templateCode, $alternativeLayouts, $marker) {
		$out = array();
		for($a = 0; $a < $alternativeLayouts; $a++) {
			$m = '###'.$marker.($a?"_".$a:"").'###';
			if (strstr($templateCode, $m)) {
				$out[] = $GLOBALS['TSFE']->cObj->getSubpart($templateCode, $m);
			} else {
				break;
			}
		}
		return $out;
	}
}
 
 
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/pi/class.tx_ttnews.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/pi/class.tx_ttnews.php']);
}
 
?>
