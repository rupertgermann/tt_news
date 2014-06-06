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
* $Id: class.tx_ttnews.php,v 1.30 2004/07/31 19:39:46 rupertgermann Exp $
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
	var $typolink_conf;
	var $catExclusive;
	var $arcExclusive;
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
	* returns the db-result for the news-item displayed by the xmlnewsfeed function 
	*
	* @return 	pointer 	MySQL select result pointer / DBAL object
	*/
	function getStoriesResult() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_news', 'pid='.intval($GLOBALS['TSFE']->id).$this->cObj->enableFields('tt_news'), '', 'datetime DESC');
		return $res;
	}
	 
	 
	/**
	* Init Function: here all the needed configuration Values are stored in class variables..
	* init is normaly called one time in main function!
	*
	* @param array $conf: configuration array from TS 
	* @return void
	*/
	function init_news($conf) {
		
		$this->conf = $conf; //store configuration
		$this->tt_news_uid = intval(t3lib_div::_GP('tt_news')); //Get the submitted uid of a news (if any)
		//Get number of alternative Layouts (loop layout in Archivelist and List view) default is 2:
		$this->alternatingLayouts = intval($this->conf['alternatingLayouts']) > 0 ? intval($this->conf['alternatingLayouts']) : 2;
		 
		// Loading language-labels
		$this->pi_loadLL();
		 
		// Init FlexForm configuration for plugin:
		$this->pi_initPIflexForm();
		$this->enableFields = $this->cObj->enableFields('tt_news');
		
		
				 
		/**
		*  "CODE" decides what is rendered:
		*  codes can be added by TS or FF with priority on FF
		*/
		$this->config['code'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'what_to_display', 'sDEF');
		$this->config['code'] = $this->config['code'] ? $this->config['code'] : $this->cObj->stdWrap($this->conf['code'], $this->conf['code.']);

		/**
		* 	categories		
		*/ 
		// categoryModes are: 0=display all categories, 1=display selected categories, -1=display deselected categories 
		$categoryMode = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'categoryMode', 'sDEF');
		$this->config['categoryMode'] = $categoryMode ? $categoryMode : $this->conf['categoryMode'];
		
		if (is_numeric(t3lib_div::_GP('cat'))) {
		    $catSelection = t3lib_div::_GP('cat');
			$this->config['categoryMode'] = 1; // force 'select categories' mode if cat is given in GPvars
		}
		$catSelection = $catSelection?$catSelection:$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'categorySelection', 'sDEF');
		$catSelection = $catSelection?$catSelection:$this->conf['categorySelection'];
		// ignore cat selection if categoryMode isn't set
		$this->catExclusive = $this->config['categoryMode']?$catSelection:0;


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
		
		
		/**
		* 	Archive
		*/
		// arcExclusive : -1=only non-archived; 0=don't care; 1=only archived
		$archive = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'archive', 'sDEF');
		$this->arcExclusive = $archive?$archive:$this->conf['archive'];
		// if next value is set, the archive list is empty at start. default is, to show all 
		// archived items when no time period is given. 
		$this->config['emptyArchListAtStart'] = $this->conf['emptyArchListAtStart'];
		// month, quarter or year listing in AMENU
		$this->config['archiveMode'] = $this->conf['archiveMode'];
		// reverse AMENU order
		$this->config['reverseAMenu'] = $this->conf['reverseAMenu'];
		$this->config['archiveMenuNoEmpty'] = $this->conf['archiveMenuNoEmpty'];
		$this->config['displayCurrentRecord'] = $this->conf['displayCurrentRecord'];
		$this->config['datetimeDaysToArchive'] = $this->conf['datetimeDaysToArchive'];
		
		/**
		* 	PID List		
		*/ 
		
		// pid_list is the pid/list of pids from where to fetch the news items.
		$pid_list = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'pages', 'sDEF');
		$pid_list = $pid_list?$pid_list:trim($this->cObj->stdWrap($this->conf['pid_list'], $this->conf['pid_list.']));
		$this->config['pid_list'] = $pid_list ? implode(t3lib_div::intExplode(',', $pid_list), ',') : $GLOBALS['TSFE']->id;
			 
		$recursive = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'recursive', 'sDEF');
		$this->config['recursive'] = is_numeric($recursive) ? $recursive : $this->cObj->stdWrap($conf['recursive'], $conf['recursive.']);
		list($pid) = explode(',', $this->config['pid_list']);
		$this->pid = $pid;
		
		/**
		*	Parameters for Links 		
		*/ 
		if ($this->conf['itemLinkType']) {
			$this->config['itemLinkType'] = $this->conf['itemLinkType'];
		} else {
			if ($GLOBALS['TSFE']->type) {
				$this->config['itemLinkType'] = $GLOBALS['TSFE']->type;
			} else {
				$this->config['itemLinkType'] = '';
			}
		}
		$this->config['itemLinkTarget'] = $this->conf['itemLinkTarget']; 
		
		//For keeping the configured linkVars
		$this->config['linkVars']=t3lib_div::trimexplode(",",$GLOBALS['TSFE']->config['config']['linkVars']);
		
				 
		// pid of the page with the single view
		$PIDitemDisplay = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'PIDitemDisplay', 's_misc');
		$this->config['PIDitemDisplay'] = $PIDitemDisplay ? $PIDitemDisplay : $this->conf['PIDitemDisplay'];
		// pid to return to when leaving single view
		$backPid = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'backPid', 'sDEF'));
		$backPid = $backPid?$backPid:intval($this->conf['backPid']);
				
		$backPid = $backPid?$backPid:intval(t3lib_div::_GP('backPid')); 
		$this->config['backPid'] = $backPid;
		
		// max items per page
		$this->config['limit'] = t3lib_div::intInRange($this->conf['limit'], 0, 1000);
		$this->config['limit'] = $this->config['limit'] ? $this->config['limit'] : 50;
		$this->config['latestLimit'] = intval($this->conf['latestLimit'])?intval($this->conf['latestLimit']):$this->config['limit'];
		// display text like 'page' in pagebrowser ?
		$this->config['showPBrowserText'] = $this->conf['showPBrowserText'];
		$this->config['pageBrowser.'] = $this->conf['pageBrowser.'];
		
		$this->config['emptySearchAtStart'] = $this->conf['emptySearchAtStart'];
				
		// message diplayed when single view is called without a tt_news uid		 
		$this->config['noNewsIdMsg'] = $this->conf['noNewsIdMsg'];
		 
		$this->initCategories(); 
		
		
		/**
		*	read template file and fill it with Global Markers
		*/
		$templateflex_file = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template_file', 's_template');
		$this->templateCode = $this->cObj->fileResource($templateflex_file?"uploads/tx_ttnews/".$templateflex_file:$this->conf['templateFile']);
		$splitMark = md5(microtime());
		$globalMarkerArray = array();
		list($globalMarkerArray['###GW1B###'], $globalMarkerArray['###GW1E###']) = explode($splitMark, $this->cObj->stdWrap($splitMark, $this->conf['wrap1.']));
		list($globalMarkerArray['###GW2B###'], $globalMarkerArray['###GW2E###']) = explode($splitMark, $this->cObj->stdWrap($splitMark, $this->conf['wrap2.']));
		list($globalMarkerArray['###GW3B###'], $globalMarkerArray['###GW3E###']) = explode($splitMark, $this->cObj->stdWrap($splitMark, $this->conf['wrap3.']));
		$globalMarkerArray['###GC1###'] = $this->cObj->stdWrap($this->conf['color1'], $this->conf['color1.']);
		$globalMarkerArray['###GC2###'] = $this->cObj->stdWrap($this->conf['color2'], $this->conf['color2.']);
		$globalMarkerArray['###GC3###'] = $this->cObj->stdWrap($this->conf['color3'], $this->conf['color3.']);
		$globalMarkerArray['###GC4###'] = $this->cObj->stdWrap($this->conf['color4'], $this->conf['color4.']);
				 
		// Substitute Global Marker Array
		$this->templateCode = $this->cObj->substituteMarkerArray($this->templateCode, $globalMarkerArray);
		
		##### caching/indexing
			$this->local_cObj->setCurrentVal($GLOBALS['TSFE']->id);
			$this->typolink_conf = $this->conf['typolink.'];
			$this->typolink_conf['parameter.']['current'] = 1;
			$this->typolink_conf['additionalParams'] = $this->cObj->stdWrap($this->typolink_conf['additionalParams'], $this->typolink_conf['additionalParams.']);
			unset($this->typolink_conf['additionalParams.']);
	
			// Configure caching
			$this->allowCaching = $this->conf['allowCaching']?1:0;
			if (!$this->allowCaching) {
				$GLOBALS['TSFE']->set_no_cache();
			}
		##### caching/indexing end 
	}
	 
	/**
	* Main news function.
	*
	* @param [string]  $content: function output is added to this
	* @param [array]  $conf: configuration array
	* @return [string]  $content: complete content generated by the tt_news plugin
	*/
	function main_news($content, $conf) {
		 
		 
		#$GLOBALS['TSFE']->set_no_cache();
		
		// Local cObj. 
		$this->local_cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->init_news($conf);

		if ($this->config['displayCurrentRecord']) {
			# added the possibility to change the template, used for 'display current record'.
			# if the value is empty, the code is 'single'
			$this->config['code'] = $this->conf['defaultCode']?trim($this->conf['defaultCode']):
			"SINGLE";
			$this->tt_news_uid = $this->cObj->data['uid'];
		}
		 
		// get codes and decide which function is used to process the content 
		$codes = t3lib_div::trimExplode(',', $this->config['code']?$this->config['code']:$this->conf['defaultCode'], 1);
		if (!count($codes)) $codes = array('');
			while (list(, $theCode) = each($codes)) {
			$theCode = (string)strtoupper(trim($theCode));
			$this->theCode = $theCode;
		 
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
	* generates the News archive menu
	*
	* @return [string]  html code of the archive menu
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
			$arcMode = $this->config['archiveMode'] ? $this->config['archiveMode'] : 'month';
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
				 
				// execute a query to count the archive periods 
				$selectConf['selectFields'] = 'count(distinct(uid))';
				$selectConf['where'] = $selectConf2['where'].' AND datetime>='.$periodInfo['start'].' AND datetime<'.$periodInfo['stop'];
				$res = $this->cObj->exec_getQuery('tt_news', $selectConf);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$periodInfo['count'] = $row[0];
				 
				if (!$this->config['archiveMenuNoEmpty'] || $periodInfo['count']) {
					$periodAccum[] = $periodInfo;
				}
			}
			 
			// get template subpart	 
			$t['total'] = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_ARCHIVE###'));
			$t['item'] = $this->getLayouts($t['total'], $this->alternatingLayouts, 'MENUITEM');
			$cc = 0;
			 
			$veryLocal_cObj = t3lib_div::makeInstance('tslib_cObj');
			
			// reverse amenu oder if 'reverseAMenu' is given
			if ($this->config['reverseAMenu']) {
				arsort($periodAccum);
			}
			 
			$archiveLink = $this->conf['archiveTypoLink.']['parameter'];
			$this->local_cObj->setCurrentVal($archiveLink?$archiveLink:$GLOBALS["TSFE"]->id);
			
			reset($periodAccum);
			$itemsOut = '';
			while (list(, $pArr) = each($periodAccum)) {
				// Print Item Title
				$wrappedSubpartArray = array();
				$temp_conf = $this->typolink_conf; 
				$temp_conf['additionalParams'] .= '&'.$this->getLinkUrl(0,'id,pS,pL,begin_at,type').'&pS='.$pArr['start'].'&pL='.($pArr['stop']-$pArr['start']).'&arc=1';
 				$temp_conf['useCacheHash'] = $this->allowCaching;
				$temp_conf['no_cache'] = !$this->allowCaching;
				$wrappedSubpartArray['###LINK_ITEM###'] = explode('|', $this->local_cObj->typolink('|', $temp_conf));
					 
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
		// if nothing is found in the archive display the TEMPLATE_ARCHIVE_NOITEMS message
			$markerArray['###ARCHIVE_HEADER###'] = $this->local_cObj->stdWrap($this->pi_getLL('archiveHeader'), $lConf['archiveHeader_stdWrap.']);
			$markerArray['###ARCHIVE_EMPTY_MSG###'] = $this->local_cObj->stdWrap($this->pi_getLL('archiveEmptyMsg'), $lConf['archiveEmptyMsg_stdWrap.']);
			$noItemsMsg = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_ARCHIVE_NOITEMS###'));
$content = $this->cObj->substituteMarkerArrayCached($noItemsMsg, $markerArray);
		}
		return $content;
	}
	 
	/**
	* generates the single view of a news article. Is also used when displaying single records
	* with the 'insert records' content element 	
	*
	* @return [string]  ...
	*/
	function single_view() {
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_news', 'uid='.intval($this->tt_news_uid).' AND type=0'.$this->enableFields); // type=0->only real news.
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		
		 
		if (is_array($row)) {
			$this->setPidlist(intval($row['pid']));
			$this->generatePageArray();
			 
			// Get the subpart code
			$item = '';
			if ($this->config['displayCurrentRecord']) {
				$item = trim($this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_SINGLE_RECORDINSERT###')));
			}
			if (!$item) {
				$item = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_SINGLE###'));
			}
			 
			// Fill marker arrays
			$wrappedSubpartArray = array();

	##### caching/indexing			
			$temp_conf = $this->typolink_conf;
 			$this->local_cObj->setCurrentVal($this->config['backPid']);
			$temp_conf['additionalParams'] .= '&'.$this->getLinkUrl(0,'id');
			$temp_conf['useCacheHash'] = $this->allowCaching;
			$temp_conf['no_cache'] = !$this->allowCaching;
			$wrappedSubpartArray['###LINK_ITEM###'] = explode('|', $this->local_cObj->typolink('|', $temp_conf));
	##### caching/indexing  end

			// set the titel of the single view page to the title of the news record
			$GLOBALS['TSFE']->page['title'] = $row['title']; 

			$markerArray = $this->getItemMarkerArray($row, 'displaySingle');
			// Substitute
			$content = $this->cObj->substituteMarkerArrayCached($item, $markerArray, array(), $wrappedSubpartArray);
		} else {
			// if singleview is shown with no tt_news uid given in the url, an error message is displayed.
			$noNewsIdMsg =  $this->local_cObj->stdWrap($this->pi_getLL('noNewsIdMsg'), $lConf['noNewsIdMsg_stdWrap.']);
			$content .= $noNewsIdMsg?$noNewsIdMsg:'Wrong parameters, GET/POST var \'tt_news\' was missing.';
			
		}
		return $content;
	}
	 
	 
	/**
	* Display LIST,LATEST or SEARCH
	* Things happen: determine the template-part to use, get the query parameters (add where if search was performed),
	* exec count query to get the number of results Check if a browsebox should be displayed
* 	
* than render of the List:
	* Get the general Markers for each item and fill the content array (Templatebased)
	* Check if a browsebox should be displayed
	*
	* @return 	string  	$content: html code for the plugin content
	*/
	function news_list() {
		$theCode = $this->theCode;
		
		 
		$where = '';
		$content = '';
		 
		// in the switch blocks the things which are diffrent for the codefields
		switch($theCode) {
			case 'LATEST':
			$prefix_display = 'displayLatest';
			$templateName = 'TEMPLATE_LATEST';
			$this->arcExclusive = -1; // Only latest, non archive news
			$this->config['limit'] = $this->config['latestLimit'];
				
			break;
			case 'LIST':
			
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
			$out = $this->cObj->substituteMarker($out, '###FORM_URL###', '?'.$this->getLinkUrl($this->conf['PIDsearch']));
			$out = $this->cObj->substituteMarker($out, '###SWORDS###', htmlspecialchars(t3lib_div::_GP('swords')));
			$out = $this->cObj->substituteMarker($out, '###SEARCH_BUTTON###', $this->pi_getLL('searchButtonLabel'));
			// Add to content
			$content .= $out;
			
			 
			//if searchword is given make t
			if (t3lib_div::_GP('swords')) {
				$where = $this->searchWhere(trim(t3lib_div::_GP('swords')));
				$theCode = 'SEARCH';
			} else {
				$where = ($this->config['emptySearchAtStart']?'AND 1=0':'');
			}
			
			
			break;
			 
		}
		 
		
		$noPeriod = 0;
		
	    // if this is true, we're listing from the archive for the first time (no pS set), 
		// to prevent an empty list page we set the pS value to the archive start  
		if (($this->arcExclusive > 0 && !t3lib_div::_GP('pS') && $theCode != 'SEARCH')) {
	    	// set pS to time minus archive startdate
			t3lib_div::_GETset(array('pS' => (time()-($this->config['datetimeDaysToArchive']*86400)))) ;
			
			
		}
	
		if (t3lib_div::_GP('pS')&&!t3lib_div::_GP('pL')) {
		    $noPeriod = 1;
		}
		
		// Allowed to show the listing?
		// periodStart must be set, when listing from the archive.
		 if (!($this->arcExclusive > -1 && !t3lib_div::_GP('pS') && $theCode != 'SEARCH')) {
		 
			/**
			*   this change makes it possible to display news-items, which are included by the content
			*   element 'insert records', either as single item or as a list item. Configuration is done 
			* 	in TS, the default template is SINGLE.
			*/
			if ($this->config['displayCurrentRecord'] && $this->tt_news_uid) {
				$this->config['pid_list'] = $this->cObj->data['pid'];
				$where = 'AND uid='.$this->cObj->data['uid'];
			}
			
			//Get the Listqueryparameters
	  		$selectConf = $this->getSelectConf($where,$noPeriod);
						 
			// performing query to count all news (we need to know it for browsing):
			$selectConf['selectFields'] = 'count(distinct(uid))'; //count(*)
			$res = $this->cObj->exec_getQuery('tt_news', $selectConf);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			$newsCount = $row[0];
			 
			//Only do something if the queryresult is not empty
			if ($newsCount > 0) {
				// Init the Templateparts
				// $t['total'] is the part which is defined by the Code (TEMPLATE_LATEST and so on)
				// $t['item'] is an array with the alternated Subparts (NEWS, NEWS_1 and so on)
				$t = array();
				$t['total'] = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###'.$templateName.'###'));
				$t['item'] = $this->getLayouts($t['total'], $this->alternatingLayouts, 'NEWS');
				 
				// range check to current newsCount
				//get begin_at for browsing
				$begin_at = t3lib_div::intInRange(t3lib_div::_GP('begin_at'), 0, 100000);
				$begin_at = t3lib_div::intInRange(($begin_at >= $newsCount) ? ($newsCount-$this->config['limit']) : $begin_at, 0);
				 
				// build query for display:
				$selectConf['groupBy'] = 'uid';
				$selectConf['orderBy'] = 'datetime DESC';
				$selectConf['selectFields'] = '*';
				$selectConf['max'] = intval($this->config['limit']+1);
				
				//	exclude the LATEST template from changing its content with the pagebrowser
				//	this can be overridden by setting the conf var latestWithPagebrowser
				if ($theCode != 'LATEST' && !$this->conf['latestWithPagebrowser']) {
					$selectConf['begin'] = $begin_at;
				}
				 
				// Reset:
				$subpartArray = array();
				$wrappedSubpartArray = array();
				$markerArray = array();
				 
				//	get the list of news items and fill them in the CONTENT subpart 
				$subpartArray['###CONTENT###'] = $this->get_content_news_list($t['item'], $selectConf, $prefix_display);
				 
				#$markerArray['###CATEGORY_TITLE###'] = ''; // Something here later...
				$markerArray['###GOTOARCHIVE###'] = $this->pi_getLL('goToArchive');
				$markerArray['###LATEST_HEADER###'] = $this->pi_getLL('latestHeader');
				$wrappedSubpartArray['###LINK_ARCHIVE###'] = $this->local_cObj->typolinkWrap($this->conf['archiveTypoLink.']);
				 
								
				// render a pagebrowser if needed
				$markerTemp=$this->get_page_browser($newsCount,$begin_at);
				$markerArray['###LINK_PREV###']=$markerTemp['###LINK_PREV###'];
				$markerArray['###BROWSE_LINKS###']=$markerTemp['###BROWSE_LINKS###'];
				$markerArray['###LINK_NEXT###']=$markerTemp['###LINK_NEXT###'];
				 
				$content .= $this->cObj->substituteMarkerArrayCached($t['total'], $markerArray, $subpartArray, $wrappedSubpartArray);
			} elseif (ereg('1=0', $where)) {
				// first view of the search page with the parameter 'emptySearchAtStart' set
				$markerArray['###SEARCH_EMPTY_MSG###'] = $this->local_cObj->stdWrap($this->pi_getLL('searchEmptyMsg'), $this->conf['searchEmptyMsg_stdWrap.']);	
				$searchEmptyMsg = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_SEARCH_EMPTY###'));
				
				$content .= $this->cObj->substituteMarkerArrayCached($searchEmptyMsg, $markerArray);
			}  elseif (t3lib_div::_GP('swords')) {
				// no results
				$markerArray['###SEARCH_EMPTY_MSG###'] = $this->local_cObj->stdWrap($this->pi_getLL('noResultsMsg'), $this->conf['searchEmptyMsg_stdWrap.']);	
				$searchEmptyMsg = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_SEARCH_EMPTY###'));					
				$content .= $this->cObj->substituteMarkerArrayCached($searchEmptyMsg, $markerArray);
			}
		}
		return $content;
	}
	 
	 
	/** 
	* generates a pagebrowser and fills it in the 3 markers ###LINK_NEXT###, ###LINK_PREV### 
	* and ###BROWSE_LINKS###
	* 
	* @param [int]  $newsCount: number of news items
	* @param [int]  $begin_at: item to start with
	* @return [array]  $markerArray: array with filled in pagebrowser marks
	*/
	function get_page_browser($newsCount, $begin_at) {		
		$temp_conf = $this->typolink_conf;
		$this->local_cObj->setCurrentVal($GLOBALS['TSFE']->id);
		$temp_conf['useCacheHash'] = $this->allowCaching;
		$temp_conf['no_cache'] = !$this->allowCaching;
		$temp_conf_additionalParams=$temp_conf['additionalParams'];
		
		// Make Next link
		if ($newsCount > $begin_at+$this->config['limit']) {
			$next = ($begin_at+$this->config['limit'] > $newsCount) ? $newsCount-$this->config['limit'] : $begin_at+$this->config['limit'];						
			$temp_conf['additionalParams'] = $temp_conf_additionalParams.'&'.$this->getLinkUrl('','begin_at,id').'&begin_at='.$next;					
			$markerArray['###LINK_NEXT###'] = $this->local_cObj->typolink( $this->local_cObj->stdWrap($this->pi_getLL('pbrLinkNext'),$this->config['pageBrowser.']['item_stdWrap.'])  , $temp_conf);						
		} else {
			$markerArray['###LINK_NEXT###'] = '';
		}
		
		// Make Previous link
		if ($begin_at) {
			$prev = ($begin_at-$this->config['limit'] < 0) ? 0 : $begin_at-$this->config['limit'];
			$temp_conf['additionalParams'] = $temp_conf_additionalParams.'&'.$this->getLinkUrl('','begin_at,id').'&begin_at='.$prev;			
			$markerArray['###LINK_PREV###'] = $this->local_cObj->typolink(  $this->local_cObj->stdWrap($this->pi_getLL('pbrLinkPrev'),$this->config['pageBrowser.']['item_stdWrap.'])  , $temp_conf);			
		} else {
			$markerArray['###LINK_PREV###'] = '';
		}
		
		$markerArray['###BROWSE_LINKS###'] = '';	//Clear
		
		if ($newsCount > $this->config['limit'] ) {
			// there is more than one page, so let's browse
			$firstpage=0;
			 $lastpage=$pages=ceil($newsCount/$this->config['limit']);
			 $actualpage=floor($begin_at/$this->config['limit']);
			
			if ($lastpage>$this->config['pageBrowser.']['maxPages']) {
				//There had to be more pages than allowed in $this->config['pageBrowser.']['maxPages']
				//So calculate the first and the lastpage to show (actualpage shoul be the middle)
				 $bevorepagecount=floor($this->config['pageBrowser.']['maxPages']/2);
				 $afterpagecount=$this->config['pageBrowser.']['maxPages']-$bevorepagecount;
				//Now set firstpage and lastpage
				$firstpage=$actualpage-$bevorepagecount;
				if ($firstpage <0) {					
					$firstpage=0;
					 $lastpage=$this->config['pageBrowser.']['maxPages'];
				}
				else {
					$lastpage=$actualpage+$afterpagecount;
					if ($lastpage > $pages) {
						$lastpage=$pages;
						$firstpage=$pages-$this->config['pageBrowser.']['maxPages'];
					}
				}
			}
			
			//Now we know lastpage and firstpage							
			
			for ($i = $firstpage ; $i < $lastpage; $i++) {
				if (($begin_at >= $i * $this->config['limit']) && ($begin_at < $i * $this->config['limit']+$this->config['limit'])) {
				
					$item=($this->config['showPBrowserText']?$this->pi_getLL('pbrPage'):'').(string)($i+1);
					$markerArray['###BROWSE_LINKS###'] .= ' '.$this->local_cObj->stdWrap($item,$this->config['pageBrowser.']['activpage_stdWrap.']).' ';
				} else {
					$temp_conf['additionalParams'] = $temp_conf_additionalParams.'&'.$this->getLinkUrl('','begin_at,id').'&begin_at='.(string)($i * $this->config['limit']);
					$item=($this->config['showPBrowserText']?$this->pi_getLL('pbrPage'):'').(string)($i+1);											
					$markerArray['###BROWSE_LINKS###'] .= ' '.$this->local_cObj->typolink($this->local_cObj->stdWrap($item,$this->config['pageBrowser.']['item_stdWrap.']), $temp_conf).' ';										
				}
			}
		}
		
		return $markerArray;
		
    	}
	 
	 
	 
	 
	 
	/**
	* get the content for a news item NOT displayed as single item (List & Latest)
	*
	* @param [array]  $itemparts: parts of the html template 
	* @param [array]  $selectConf: quety parameters in an array
	* @param [string]  $prefix_display: the part of the TS-setup 
	* @return [string]  $itemsOut: itemlist as htmlcode 
	*/
		function get_content_news_list($itemparts, $selectConf, $prefix_display) {
		//Make the Listingquery
		$res = $this->cObj->exec_getQuery('tt_news', $selectConf);
		// Getting elements
		$itemsOut = '';
		 
		$cc = 0;
		#debug($selectConf);
		$itemLinkTarget = $this->config['itemLinkTarget'] ? 'target="'.$this->config['itemLinkTarget'].'"':'';
		 
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			// Print Item Title
			$wrappedSubpartArray = array();
			if ($row['type']) {
				//News type article or external url
				$this->local_cObj->setCurrentVal($row['type'] == 1 ? $row['page'] : $row['ext_url']);
				$wrappedSubpartArray['###LINK_ITEM###'] = $this->local_cObj->typolinkWrap($this->conf['pageTypoLink.']);
			} else {
		
		
		##### caching/indexing		
				$temp_conf = $this->typolink_conf;
				$this->local_cObj->setCurrentVal($this->config['PIDitemDisplay']);
				$temp_conf['additionalParams'] .= '&'.$this->getLinkUrl(0,'id').'&tt_news='.$row['uid'].$itemLinkTarget;
				$temp_conf['useCacheHash'] = $this->allowCaching;
				$temp_conf['no_cache'] = !$this->allowCaching;
				$wrappedSubpartArray['###LINK_ITEM###'] = explode('|', $this->local_cObj->typolink('|', $temp_conf));
	 	##### caching/indexing  end

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
	* @param 	integer	$id: id to link to
	* @param 	string	$excludeList: parameters listed here, are removed from the link (used in the pagebrowser)
	* @return 	string  the link url
	*/
	function getLinkUrl($id = '', $excludeList = '') {
		$queryString = array();
		$queryString['id'] = 'id='.($id ? $id : $GLOBALS['TSFE']->id);

		$queryString['type'] = $this->config['itemLinkType'] ? 'type='.$this->config['itemLinkType'] : '';
		$queryString['backPid'] = 'backPid='.($this->config['backPid']?$this->config['backPid']:$GLOBALS['TSFE']->id);
		$queryString['begin_at'] = t3lib_div::_GP('begin_at') ? 'begin_at='.t3lib_div::_GP('begin_at') : '';
		$queryString['swords'] = t3lib_div::_GP('swords') ? 'swords='.rawurlencode(t3lib_div::_GP('swords')) : '';
		$queryString['pS'] = t3lib_div::_GP('pS') ? 'pS='.intval(t3lib_div::_GP('pS')) : ''; // period start
		$queryString['pL'] = t3lib_div::_GP('pL') ? 'pL='.intval(t3lib_div::_GP('pL')) : ''; // Period length
		$queryString['arc'] = t3lib_div::_GP('arc') ? 'arc='.intval(t3lib_div::_GP('arc')) : ''; // Archive flag: 0 = don't care, -1 = latest, 1 = archive
		$queryString['cat'] = t3lib_div::_GP('cat') ? 'cat='.intval(t3lib_div::_GP('cat')) : ''; // Category uid, 0 = any
		
		//danp Keep the linkVars:
		if (is_array($this->config['linkVars'])) {
			foreach ($this->config['linkVars'] as $v) {
				$queryString[$v]=t3lib_div::GPvar($v) ? $v.'='.(t3lib_div::_GP($v)) : '';
			}
		}
		
		reset($queryString);
		while (list($key, $val) = each($queryString)) {
			if (!$val || ($excludeList && t3lib_div::inList($excludeList, $key))) {
				unset($queryString[$key]);
			}
		}
		return implode($queryString, '&'); 
		
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
		$selectConf['where'] = '1=1 '.($this->theCode=='LATEST'?'':$where);
		 
		
		// Archive
		if ($this->arcExclusive > 0) {
			if (intval(t3lib_div::_GP('arc'))) {
				$this->arcExclusive = intval(t3lib_div::_GP('arc'));
			}
			 
			// Period 
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
			if ($this->config['datetimeDaysToArchive']) {
				$theTime = $GLOBALS['SIM_EXEC_TIME']-intval($this->config['datetimeDaysToArchive']) * 3600 * 24;
				if ($this->arcExclusive < 0) {
					// latest
					$selectConf['where'] .= ' AND (tt_news.datetime=0 OR tt_news.datetime>'.$theTime.')';
				} elseif ($this->arcExclusive > 0) {
					$selectConf['where'] .= ' AND tt_news.datetime<'.$theTime;
				}
			}
		}
		

		if ($this->catExclusive || $this->config['categoryMode']) {
			$selectConf['leftjoin'] = 'tt_news_cat_mm ON tt_news.uid = tt_news_cat_mm.uid_local';
			if ($this->config['categoryMode']) {
				$selectConf['where'] .= ' AND (IFNULL(tt_news_cat_mm.uid_foreign,0) '.($this->config['categoryMode'] < 0?'NOT ':'').'IN ('.($this->catExclusive?$this->catExclusive:0).'))';
			}
		}
		# t3lib_div::debug($selectConf);
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
	* @param integer  $recursive: levels
	* @return void
	*/
	function initRecursive($recursive) {
		if ($recursive) {
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
	* @return void
	*/
	function initCategories() {
		$storagePid = $GLOBALS['TSFE']->page['storage_pid'] ? $GLOBALS['TSFE']->page['storage_pid'] : $GLOBALS['TSFE']->rootLine[0]['storage_pid'];
		$storagePid = $storagePid?$storagePid:$this->config['pid_list'];
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_news_cat LEFT JOIN tt_news_cat_mm ON tt_news_cat_mm.uid_foreign = tt_news_cat.uid', '1=1 AND tt_news_cat.pid IN ('.$storagePid.') '.$this->cObj->enableFields('tt_news_cat'));
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
	* @return void
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
	* Fills in the markerArray with data for a news item
	*
	* @param array  $row: result row for a news item
	* @param array  $textRenderObj: conf vars for the current template
	* @return array $markerArray: filled marker array
	*/
	function getItemMarkerArray ($row, $textRenderObj = 'displaySingle') {
		
		// config to use:
		$lConf = $this->conf[$textRenderObj.'.'];
		$this->local_cObj->start($row, 'tt_news');
		$imageNum = isset($lConf['imageCount']) ? $lConf['imageCount'] : 1;
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
		$markerArray['###TEXT_NEWS_AGE###'] = $this->local_cObj->stdWrap($this->pi_getLL('textNewsAge'), $lConf['ntextNewsAge_stdWrap.']);
		
		$markerArray['###NEWS_SUBHEADER###'] = $this->formatStr($this->local_cObj->stdWrap($row['short'], $lConf['subheader_stdWrap.']));
		
		$markerArray['###NEWS_CONTENT###'] = $this->formatStr($this->local_cObj->stdWrap($row['bodytext'], $lConf['content_stdWrap.']));
		
		// Links
		$newsLinks = $row['links']?$this->formatStr($this->local_cObj->stdWrap($row['links'],$lConf['links_stdWrap.'])):'';
		$markerArray['###NEWS_LINKS###'] = $newsLinks;
		$markerArray['###TEXT_LINKS###'] = $newsLinks?$this->local_cObj->stdWrap($this->pi_getLL('textLinks'), $lConf['newsLinksHeader_stdWrap.']):'';
		
		$markerArray['###MORE###'] = $this->pi_getLL('more');
		$markerArray['###BACK_TO_LIST###'] = $this->pi_getLL('backToList');
		
		// related
		$relatedNews = $this->local_cObj->stdWrap($this->getRelated($row['uid']), $lConf['related_stdWrap.']);
		$markerArray['###NEWS_RELATED###'] = $relatedNews;
		$markerArray['###TEXT_RELATED###'] = $relatedNews ? $this->local_cObj->stdWrap($this->pi_getLL('textRelated'), $this->conf['relatedHeader_stdWrap.']):''; 

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
	$markerArray = $this->getCatMarkerArray($markerArray,$row,$lConf);
		return $markerArray;
	}
	/**
	* Fills in the Category markerArray with data 
	*
	* @param array  $row: result row for a news item
	* @param array  $textRenderObj: conf vars for the current template
	* @return array $markerArray: filled marker array
	*/
	 function getCatMarkerArray($markerArray,$row,$lConf){
	 		$markerArray['###TEXT_CAT###'] = $this->pi_getLL('textCat');
		$markerArray['###TEXT_CAT_LATEST###'] = $this->pi_getLL('textCatLatest');
		
		$news_category = array();
		$theCatImgCode = '';
		$theCatImgCodeArray = array();
		
		if (isset($this->categories[$row['uid']]) && ($this->config['catImageMode'] || $this->config['catTextMode'])) {
			while (list ($key, $val) = each ($this->categories[$row['uid']])) {
				if ($this->config['catTextMode'] == 0) {
					$markerArray['###NEWS_CATEGORY###'] = '';
				} elseif($this->config['catTextMode'] == 1) {			
					$news_category[] = $this->local_cObj->stdWrap($this->categories[$row['uid']][$key]['title'], $lConf['category_stdWrap.']);
				} elseif($this->config['catTextMode'] == 2) {
		##### caching/indexing		
				$temp_conf = $this->typolink_conf;
				$this->local_cObj->setCurrentVal($this->categories[$row['uid']][$key]['shortcut']);
				$temp_conf['additionalParams'] .= '&'.$this->getLinkUrl(0,'id').'&id='.$this->categories[$row['uid']][$key]['shortcut'];
				$temp_conf['useCacheHash'] = $this->allowCaching;
				$temp_conf['no_cache'] = !$this->allowCaching;
$news_category[] = $this->local_cObj->typolink($this->categories[$row['uid']][$key]['title'], $temp_conf);
	 	##### caching/indexing  end


				} elseif($this->config['catTextMode'] == 3) {
				
		##### caching/indexing		
				$temp_conf = $this->typolink_conf;
				$this->local_cObj->setCurrentVal($GLOBALS['TSFE']->id);
				$temp_conf['additionalParams'] .= '&'.$this->getLinkUrl(0,'id,cat').'&cat='.$this->categories[$row['uid']][$key]['catid'];
				$temp_conf['useCacheHash'] = 0;
				$temp_conf['no_cache'] = 1;
				

				$news_category[] = $this->local_cObj->typolink($this->categories[$row['uid']][$key]['title'], $temp_conf);

				}
				if ($this->config['catImageMode'] == 0 or empty($this->categories[$row['uid']][$key]['image'])) {
					$markerArray['###NEWS_CATEGORY_IMAGE###'] = '';
				} else {
					$catPicConf = array();
					$catPicConf['image.']['file'] = 'uploads/pics/'.$this->categories[$row['uid']][$key]['image'];
					$catPicConf['image.']['file.']['maxW'] = intval($this->config['catImageMaxWidth']);
					$catPicConf['image.']['file.']['maxH'] = intval($this->config['catImageMaxHeight']);
					$catPicConf['image.']['stdWrap.']['spaceAfter'] = 0;
					// clear the imagewrap to prevent category image from beeing wrapped in a table 
					$lConf['imageWrapIfAny'] = '';
					if ($this->config['catImageMode'] != 1) {
						$temp_conf = $this->typolink_conf;
						$this->local_cObj->setCurrentVal($this->categories[$row['uid']][$key]['shortcut']);
						if ($this->config['catImageMode'] == 2) {
							$this->local_cObj->setCurrentVal($this->categories[$row['uid']][$key]['shortcut']);
							$catPicConf['image.']['stdWrap.']['typolink.']['useCacheHash'] = $this->allowCaching;
							$catPicConf['image.']['stdWrap.']['typolink.']['no_cache'] = !$this->allowCaching;
							$catPicConf['image.']['stdWrap.']['typolink.']['parameter'] = $this->categories[$row['uid']][$key]['shortcut'];
							$catPicConf['image.']['stdWrap.']['typolink.']['additionalParams'] = $temp_conf['additionalParams'].'&'.$this->getLinkUrl(0,'id');
							$catPicConf['image.']['altText'] = $this->categories[$row['uid']][$key]['shortcut']?$this->pi_getLL('altTextCatShortcut').$this->categories[$row['uid']][$key]['shortcut']:'';
#t3lib_div::debug($this->categories);
						} if ($this->config['catImageMode'] == 3) {
							$this->local_cObj->setCurrentVal($GLOBALS['TSFE']->id);
						    $catPicConf['image.']['stdWrap.']['typolink.']['parameter'] = $GLOBALS['TSFE']->id;
							$catPicConf['image.']['stdWrap.']['typolink.']['useCacheHash'] = $this->allowCaching;
							$catPicConf['image.']['stdWrap.']['typolink.']['no_cache'] = !$this->allowCaching;
							$catPicConf['image.']['stdWrap.']['typolink.']['additionalParams'] = $temp_conf['additionalParams'].'&'.$this->getLinkUrl(0,'id,cat').'&cat='.$this->categories[$row['uid']][$key]['catid'];
							$catPicConf['image.']['altText'] = $this->pi_getLL('altTextCatSelector').$this->categories[$row['uid']][$key]['title'];
						}
					
					} else {
						$catPicConf['image.']['altText'] = $this->categories[$row['uid']][$key]['title'];
					}
					//stdWrap.htmlSpecialChars = 1")??? for xml &amp;
					
					$theCatImgCodeArray[] = $this->local_cObj->IMAGE($catPicConf['image.']);
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
		} else {
		// clear the category text and image markers if the news item has no categories
			$markerArray['###NEWS_CATEGORY_IMAGE###'] = '';
			$markerArray['###NEWS_CATEGORY###'] = '';
			$markerArray['###TEXT_CAT###'] = '';
			$markerArray['###TEXT_CAT_LATEST###'] = '';
		}
		return $markerArray;
	 }
	 
	/**
	* Gets related news.
	*
	* @param integer  $uid: it of the current news item
	* @return string  html code for the related news list
	*/
		function getRelated($uid) {
		$veryLocal_cObj = t3lib_div::makeInstance('tslib_cObj');	// Local cObj.
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,title,short,datetime,archivedate,type,page,ext_url', 'tt_news,tt_news_related_mm AS M', 'tt_news.uid=M.uid_foreign AND M.uid_local='.intval($uid));
		 
		$lines = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$veryLocal_cObj->start($row, 'tt_news');
			$tmpConf = $this->conf['getRelatedCObject.'];
			if ($row['type']) { 	// News type article or external url
				$veryLocal_cObj->setCurrentVal($row['type'] == 1 ? $row['page'] : $row['ext_url']);
			} else {
			
				$tmpConf['10.']['default.']['typolink.']['additionalParams.']['wrap'] .= '&'.$this->getLinkUrl(0, 'id');
			}
			$lines[] = $veryLocal_cObj->cObjGetSingle($this->conf['getRelatedCObject'], $tmpConf, 'getRelated');
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
	* @param [type]  $alternatingLayouts: ...
	* @param [type]  $marker: ...
	* @return [type]  ...
	*/
	function getLayouts($templateCode, $alternatingLayouts, $marker) {
		$out = array();
		for($a = 0; $a < $alternatingLayouts; $a++) {
			$m = '###'.$marker.($a?'_'.$a:'').'###';
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
