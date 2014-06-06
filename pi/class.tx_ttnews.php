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
* class.tx_ttnews.php
*
* Creates a news system.
* $Id: class.tx_ttnews.php,v 1.33 2004/08/29 12:08:57 honk Exp $
*
* TypoScript config:
* - See ext_typoscript_setup.txt
* - See tt_news Reference: http://typo3.org/documentation/document-library/tt_news/Reference-59/
* - See TSref: http://typo3.org/documentation/document-library/doc_core_tsref/
*
* @author Rupert Germann <rupi@gmx.li>
*/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   81: class tx_ttnews extends tslib_pibase
 *  112:     function main_xmlnewsfeed($content, $conf)
 *  127:     function getStoriesResult()
 *  139:     function init($conf)
 *  312:     function main_news($content, $conf)
 *  379:     function newsArchiveMenu()
 *  505:     function displaySingle()
 *  553:     function displayList()
 *  725:     function getListContent($itemparts, $selectConf, $prefix_display)
 *  762:     function getSelectConf($where, $noPeriod = 0)
 *  830:     function initCategories()
 *  862:     function generatePageArray()
 *  878:     function getItemMarkerArray ($row, $textRenderObj = 'displaySingle')
 *  987:     function getCatMarkerArray($markerArray, $row, $lConf)
 * 1064:     function getRelated($uid)
 * 1093:     function userProcess($mConfKey, $passVar)
 * 1108:     function spMarker($subpartMarker)
 * 1126:     function searchWhere($sw)
 * 1138:     function formatStr($str)
 * 1154:     function getLayouts($templateCode, $alternatingLayouts, $marker)
 *
 * TOTAL FUNCTIONS: 19
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_t3lib.'class.t3lib_xml.php');
require_once(PATH_tslib.'class.tslib_pibase.php');

/**
 * Plugin 'news' for the 'tt_news' extension.
 *
 * @author	Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage tt_news
 */
class tx_ttnews extends tslib_pibase {
	var $cObj;	// The backReference to the mother cObj object set at call time

	// Default plugin variables:
	var $prefixId = 'tx_ttnews'; // Same as class name
	var $scriptRelPath = 'pi/class.tx_ttnews.php'; // Path to this script relative to the extension dir.
	var $extKey = 'tt_news'; // The extension key.

	var $tt_news_uid;
	var $conf;
	var $conf2;
	var $config;
	var $alternatingLayouts;
	var $allowCaching;
	var $catExclusive;
	var $arcExclusive;
	var $searchFieldList = 'short,bodytext,author,keywords,links,imagecaption,title';
	var $theCode = '';

	var $categories = array();	// Is initialized with the categories of the news system
	var $pageArray = array();	// Is initialized with an array of the pages in the pid-list

	/**
	 * Main news function for XML news feed
	 *
	 * @param	string		$content: ...
	 * @param	array		$conf: configuration array from TS
	 * @return	string		news content as xml string
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
	 * @return	pointer		MySQL select result pointer / DBAL object
	 */
	function getStoriesResult() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_news', 'pid='.intval($GLOBALS['TSFE']->id).$this->cObj->enableFields('tt_news'), '', 'datetime DESC');
		return $res;
	}


	/**
	 * Init Function: here all the needed configuration Values are stored in class variables..
	 *
	 * @param	array		$conf: configuration array from TS
	 * @return	void
	 */
	function init($conf) {

		$this->conf = $conf; //store configuration
		$this->tt_news_uid = intval($this->piVars['tt_news']); // Get the submitted uid of a news (if any)

		//Get number of alternative Layouts (loop layout in Archivelist and List view) default is 2:
		$this->alternatingLayouts = intval($this->conf['alternatingLayouts'])>0?intval($this->conf['alternatingLayouts']):2;

		$this->pi_loadLL(); // Loading language-labels
		$this->pi_setPiVarDefaults(); // Set default piVars from TS
		$this->pi_initPIflexForm(); // Init FlexForm configuration for plugin
		$this->enableFields = $this->cObj->enableFields('tt_news');

		// "CODE" decides what is rendered: codes can be added by TS or FF with priority on FF
		$this->config['code'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'what_to_display', 'sDEF');
		$this->config['code'] = $this->config['code'] ? $this->config['code'] : $this->cObj->stdWrap($this->conf['code'], $this->conf['code.']);

		// News Categories:
		$this->config['latestWithCatSelector'] = $this->conf['latestWithCatSelector']; // if set, news LATEST changes its contents with category selection
		$this->config['amenuWithCatSelector'] = $this->conf['amenuWithCatSelector']; // the same for AMENU
		$this->config['catSelectorTargetPid'] = $this->conf['catSelectorTargetPid'];
		
		// categoryModes are: 0=display all categories, 1=display selected categories, -1=display deselected categories
		$categoryMode = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'categoryMode', 'sDEF');
		$this->config['categoryMode'] = $categoryMode ? $categoryMode : $this->conf['categoryMode'];

		if (is_numeric($this->piVars['cat'])) {
			$this->config['catSelection'] = $this->piVars['cat'];
			#$this->config['categoryMode'] = 1; // force 'select categories' mode if cat is given in GPvars
		}
		$catSelection = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'categorySelection', 'sDEF');
		$catSelection = $catSelection?$catSelection:$this->conf['categorySelection'];
		$this->catExclusive = $this->config['categoryMode']?$catSelection:0; // ignore cat selection if categoryMode isn't set

		$catImageMode = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'catImageMode', 's_category');
		$this->config['catImageMode'] = (is_numeric($catImageMode)?$catImageMode:$this->conf['catImageMode']);
		$catTextMode = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'catTextMode', 's_category');
		$this->config['catTextMode'] = (is_numeric($catTextMode)?$catTextMode:$this->conf['catTextMode']);
		$catImageMaxWidth = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'catImageMaxWidth', 's_category');
		$this->config['catImageMaxWidth'] = ($catImageMaxWidth?$catImageMaxWidth:$this->conf['catImageMaxWidth']);
		$catImageMaxHeight = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'catImageMaxHeight', 's_category');
		$this->config['catImageMaxHeight'] = ($catImageMaxHeight?$catImageMaxHeight:$this->conf['catImageMaxHeight']);
		$maxCatImages = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxCatImages', 's_category');
		$this->config['maxCatImages'] = (is_numeric($maxCatImages)?$maxCatImages:$this->conf['maxCatImages']);
		$catTextLength = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'catTextLength', 's_category');
		$this->config['catTextLength'] = (is_numeric($catTextLength)?$catTextLength:$this->conf['catTextLength']);
		$maxCatTexts = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxCatTexts', 's_category');
		$this->config['maxCatTexts'] = (is_numeric($maxCatTexts)?$maxCatTexts:$this->conf['maxCatTexts']);
		
		$this->initCategories(); // initialize category-array


		// Archive:		
		$this->config['archiveMode'] = $this->conf['archiveMode']; // month, quarter or year listing in AMENU
		$this->config['reverseAMenu'] = $this->conf['reverseAMenu']; // reverse AMENU order
		$this->config['archiveMenuNoEmpty'] = $this->conf['archiveMenuNoEmpty'];
		// arcExclusive : -1=only non-archived; 0=don't care; 1=only archived
		$archive = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'archive', 'sDEF');
		$this->arcExclusive = $archive?$archive:$this->conf['archive'];
		
		// if next value is set, the archive list is empty at start. default is, to show all archived items when no time period is given.
		$this->config['emptyArchListAtStart'] = $this->conf['emptyArchListAtStart'];
		$this->config['datetimeDaysToArchive'] = $this->conf['datetimeDaysToArchive'];

		// pid_list is the pid/list of pids from where to fetch the news items.
		$pid_list = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'pages', 'sDEF');
		$pid_list = $pid_list?$pid_list:trim($this->cObj->stdWrap($this->conf['pid_list'], $this->conf['pid_list.']));
		$pid_list = $pid_list ? implode(t3lib_div::intExplode(',', $pid_list), ',') : $GLOBALS['TSFE']->id;

		$recursive = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'recursive', 'sDEF');
		$recursive = is_numeric($recursive) ? $recursive : $this->cObj->stdWrap($conf['recursive'], $conf['recursive.']);
		// extend the pid_list by reecursive levels
		$this->pid_list = $this->pi_getPidList($pid_list,$recursive);
		// generate array of page titles 
		$this->generatePageArray();

		$this->config['itemLinkTarget'] = $this->conf['itemLinkTarget'];

		// id of the page where the search results should be displayed
		$this->config['searchPid'] = $this->conf['searchPid'];
		$this->config['emptySearchAtStart'] = $this->conf['emptySearchAtStart']; // display only the search form, when entering the news search-page

		// pid of the page with the single view
		$singlePid = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'PIDitemDisplay', 's_misc');
		$singlePid = $singlePid ? $singlePid : intval($this->conf['singlePid']);
		$this->config['singlePid'] = $singlePid ? $singlePid : intval($this->conf['PIDitemDisplay']);
		// pid to return to when leaving single view
		$backPid = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'backPid', 'sDEF'));
		$backPid = $backPid?$backPid:intval($this->conf['backPid']);
		$backPid = $backPid?$backPid:intval($this->piVars['backPid']);
		$backPid = $backPid?$backPid:$GLOBALS['TSFE']->id ;
		$this->config['backPid'] = $backPid;

		// max items per page
		$limit = t3lib_div::intInRange($this->conf['limit'], 0, 1000);
		$this->config['limit'] = $limit?$limit:50;
		$this->config['latestLimit'] = intval($this->conf['latestLimit'])?intval($this->conf['latestLimit']): $this->config['limit'];

		$this->config['showPBrowserText'] = $this->conf['showPBrowserText']; // display text like 'page' in pagebrowser
		$this->config['pageBrowser.'] = $this->conf['pageBrowser.']; // get pageBrowser configuration
		
		$this->config['noNewsIdMsg'] = $this->conf['noNewsIdMsg']; // message diplayed when single view is called without a tt_news uid

		$this->config['substitutePagetitle'] = $this->conf['substitutePagetitle'];

		// if this is set the first image is handled as preview image, which is only shown in list view
		$fImgPreview = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'firstImageIsPreview', 's_misc');
		$this->config['firstImageIsPreview'] = $fImgPreview?$fImgPreview:$this->conf['firstImageIsPreview'];
		
		// read template-file and fill and substitute the Global Markers
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
		$this->templateCode = $this->cObj->substituteMarkerArray($this->templateCode, $globalMarkerArray);

		// names of the alternative subparts, used instead of the default subpart-names
		$this->config['altMainMarkers.'] = $this->conf['altMainMarkers.'];

		// this is set to 1 if a newsrecord is displayed with the "insert records" content element
		$this->config['displayCurrentRecord'] = $this->conf['displayCurrentRecord'];

		// Configure caching
		$this->allowCaching = $this->conf['allowCaching']?1:0;
		#$this->allowCaching = 0;

		if (!$this->allowCaching) {
			$GLOBALS['TSFE']->set_no_cache();
		}
	}

	/**
	 * Main news function: calls the init_news() function and decides by the given CODEs which of the
	 * functions to display news should by called.
	 *
	 * @param	string		$content: function output is added to this
	 * @param	array		$conf: configuration array
	 * @return	string		$content: complete content generated by the tt_news plugin
	 */
	function main_news($content, $conf) {

		$this->local_cObj = t3lib_div::makeInstance('tslib_cObj'); // Local cObj.
		$this->init($conf);

		if ($this->config['displayCurrentRecord']) {
			// added the possibility to change the template, used for 'display current record'. if the value is empty, the code is 'single'
			$this->config['code'] = $this->conf['defaultCode']?trim($this->conf['defaultCode']):'SINGLE';
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
				$content .= $this->displaySingle();
				break;
				case 'LATEST':
				case 'LIST':
				case 'SEARCH':
				$content .= $this->displayList();
				break;
				case 'AMENU':
				$content .= $this->newsArchiveMenu();
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

					// Get language version of the help-template
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
	 * @return	string		html code of the archive menu
	 */
	function newsArchiveMenu() {
		$this->arcExclusive = 1;
		$selectConf = $this->getSelectConf('', 1);

		// Finding maximum and minimum values:
		$selectConf['selectFields'] = 'max(datetime) as maxval, min(datetime) as minval';
		$res = $this->cObj->exec_getQuery('tt_news', $selectConf);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if ($row['minval']) {
			$dateArr = array();
			$arcMode = $this->config['archiveMode']?$this->config['archiveMode']:'month';
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

			// reverse amenu order if 'reverseAMenu' is given
			if ($this->config['reverseAMenu']) {
				arsort($periodAccum);
			}

			$archiveLink = $this->conf['archiveTypoLink.']['parameter'];
			
			reset($periodAccum);
			$itemsOutArr = array();
			while (list(, $pArr) = each($periodAccum)) {
				// Print Item Title
				$wrappedSubpartArray = array();
				

				if ($this->config['catSelection'] && $this->config['amenuWithCatSelector']) { // use the catSelection from GPvars only if 'amenuWithCatSelector' is given.
					$amenuLinkCat = $this->config['catSelection'];
				} else {
					$amenuLinkCat = $this->catExclusive;
				}


				$wrappedSubpartArray['###LINK_ITEM###'] = explode('|', $this->pi_linkTP_keepPIvars('|', array('cat'=>($amenuLinkCat?$amenuLinkCat:NULL),'pS'=>$pArr['start'],'pL'=>($pArr['stop']-$pArr['start']),'arc'=>1,'pointer'=>NULL),$this->allowCaching,'',($archiveLink?$archiveLink:$GLOBALS['TSFE']->id)));

				$markerArray = array();
				$veryLocal_cObj->start($pArr, '');
				$markerArray['###ARCHIVE_TITLE###'] = $veryLocal_cObj->cObjGetSingle($this->conf['archiveTitleCObject'], $this->conf['archiveTitleCObject.'], 'archiveTitle');
				$markerArray['###ARCHIVE_COUNT###'] = $pArr['count'];
				$markerArray['###ARCHIVE_ITEMS###'] = $this->pi_getLL('archiveItems');

				$itemsOutArr[] = array('html'=>$this->cObj->substituteMarkerArrayCached($t['item'][($cc%count($t['item']))], $markerArray, array(), $wrappedSubpartArray),'data'=>$pArr);
				$cc++;
			}

			// Pass to user defined function
			if ($this->conf['newsAmenuUserFunc']) {

				$itemsOutArr = $this->userProcess('newsAmenuUserFunc', $itemsOutArr);
			}
			foreach ($itemsOutArr as $itemHtml) {
				$tmpItemsArr[] = $itemHtml['html'];

			}

			$itemsOut = implode('',$tmpItemsArr);
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
	 * @return	string		html-code for a single news item
	 */
	function displaySingle() {

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_news', 'uid='.intval($this->tt_news_uid).' AND type=0'.$this->enableFields); // type=0 -> only real news.
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

		if (is_array($row)) {
			// Get the subpart code
			$item = '';
			if ($this->config['displayCurrentRecord']) {
				$item = trim($this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_SINGLE_RECORDINSERT###')));
			}
			if (!$item) {
				$item = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_SINGLE###'));
			}

			// reset marker array
			$wrappedSubpartArray = array();
			$wrappedSubpartArray['###LINK_ITEM###'] = explode('|', $this->pi_linkTP_keepPIvars('|', array(),$this->allowCaching,'',$this->piVars['backPid']));

			// set the title of the single view page to the title of the news record
			if ($this->config['substitutePagetitle']) {
			    $GLOBALS['TSFE']->page['title'] = $row['title'];
			}
			
			// set pagetitle for indexed search to news title
			$GLOBALS['TSFE']->indexedDocTitle = $row['title'];
			$markerArray = $this->getItemMarkerArray($row, 'displaySingle');
			// Substitute
			$content = $this->cObj->substituteMarkerArrayCached($item, $markerArray, array(), $wrappedSubpartArray);
		}  else {
			// if singleview is shown with no tt_news_uid given from GPvars, an error message is displayed.
			$noNewsIdMsg = $this->local_cObj->stdWrap($this->pi_getLL('noNewsIdMsg'), $lConf['noNewsIdMsg_stdWrap.']);
			$content .= $noNewsIdMsg?$noNewsIdMsg:'Wrong parameters, GET/POST var "tx_ttnews[tt_news]" was missing.';
		}
		return $content;
	}


	/**
	 * Display LIST,LATEST or SEARCH
	 * Things happen: determine the template-part to use, get the query parameters (add where if search was performed),
	 * exec count query to get the number of results, check if a browsebox should be displayed,
	 * get the general Markers for each item and fill the content array, check if a browsebox should be displayed
	 *
	 * @return	string		html-code for the plugin content
	 */
	function displayList() {
		$theCode = $this->theCode;

		$where = '';
		$content = '';
#debug ($this->pi_getLL('pi_list_browseresults_prev',$alt='',$hsc=FALSE));
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
			#$GLOBALS['TSFE']->set_no_cache();
			#$this->allowCaching = 0;
			
			$formURL = $this->pi_linkTP_keepPIvars_url(array('pointer'=>NULL,'cat'=>NULL),0,'',$this->config['searchPid']) ;

			// Get search subpart
			$t['search'] = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_SEARCH###'));
			// Substitute the markers for teh searchform
			$out = $t['search'];

			$out = $this->cObj->substituteMarker($out, '###FORM_URL###', $formURL);
			$out = $this->cObj->substituteMarker($out, '###SWORDS###', htmlspecialchars($this->piVars['swords']));
			$out = $this->cObj->substituteMarker($out, '###SEARCH_BUTTON###', $this->pi_getLL('searchButtonLabel'));
			// Add to content
			$content .= $out;

			// do the search and add the result to the $where string
			if ($this->piVars['swords']) {
				$where = $this->searchWhere(trim($this->piVars['swords']));
				$theCode = 'SEARCH';
			} else {
				$where = ($this->config['emptySearchAtStart']?'AND 1=0':''); // display an empty list, if 'emptySearchAtStart' is set.
			}
			break;
		}

		$noPeriod = 0;

		if (!$this->config['emptyArchListAtStart']) {
			// if this is true, we're listing from the archive for the first time (no pS set), to prevent an empty list page we set the pS value to the archive start
            if (($this->arcExclusive > 0 && !$this->piVars['pS'] && $theCode != 'SEARCH')) {
				// set pS to time minus archive startdate
				$this->piVars['pS'] = ($GLOBALS['SIM_EXEC_TIME']-($this->config['datetimeDaysToArchive']*86400));
			}
		}

		if ($this->piVars['pS'] && !$this->piVars['pL']) {
			$noPeriod = 1; // override the period lenght checking in getSelectConf
		}

		// Allowed to show the listing? periodStart must be set, when listing from the archive.
		if (!($this->arcExclusive > -1 && !$this->piVars['pS'] && $theCode != 'SEARCH')) {

			if ($this->config['displayCurrentRecord'] && $this->tt_news_uid) {
				$this->pid_list = $this->cObj->data['pid'];
				$where = 'AND tt_news.uid='.$this->tt_news_uid;
			}

			// build parameter Array for List query
			$selectConf = $this->getSelectConf($where, $noPeriod);

			// performing query to count all news (we need to know it for browsing):
			$selectConf['selectFields'] = 'count(distinct(uid))'; //count(*)
			$res = $this->cObj->exec_getQuery('tt_news', $selectConf);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			$newsCount = $row[0];

			//Only do something if the queryresult is not empty
			if ($newsCount > 0) {
				// Init Templateparts: $t['total'] is complete template subpart (TEMPLATE_LATEST f.e.)
				// $t['item'] is an array with the alternative subparts (NEWS, NEWS_1, NEWS_2 ...)
				$t = array();
				$t['total'] = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###'.$templateName.'###'));
				$t['item'] = $this->getLayouts($t['total'], $this->alternatingLayouts, 'NEWS');

				// build query for display:
				$selectConf['groupBy'] = 'uid';
				$selectConf['orderBy'] = 'datetime DESC';
				$selectConf['selectFields'] = '*';
				#$selectConf['max'] = intval($this->config['limit']+1);

				// exclude the LATEST template from changing its content with the pagebrowser. This can be overridden by setting the conf var latestWithPagebrowser
				if ($theCode != 'LATEST' && !$this->conf['latestWithPagebrowser']) {
					$selectConf['begin'] = $this->piVars['pointer']*$this->config['limit'];
				}
				// exclude news-records shown in LATEST from the LIST template
				if ($theCode == 'LIST' && $this->conf['excludeLatestFromList'] && !$this->piVars['pointer'] && !$this->piVars['cat']) {
					if  ($this->conf['latestLimit']) {
							$selectConf['begin'] += $this->conf['latestLimit'];
							$newsCount -= $this->conf['latestLimit'];
					} else {
						$selectConf['begin'] += $newsCount;
						// this will clean the display of LIST view when 'latestLimit' is unset because all the news have been shown in LATEST already
					}
				}

				// Reset:
				$subpartArray = array();
				$wrappedSubpartArray = array();
				$markerArray = array();

				// get the list of news items and fill them in the CONTENT subpart
				$subpartArray['###CONTENT###'] = $this->getListContent($t['item'], $selectConf, $prefix_display);

				$markerArray['###GOTOARCHIVE###'] = $this->pi_getLL('goToArchive');
				$markerArray['###LATEST_HEADER###'] = $this->pi_getLL('latestHeader');
				$wrappedSubpartArray['###LINK_ARCHIVE###'] = $this->local_cObj->typolinkWrap($this->conf['archiveTypoLink.']);

				// unset previous and next link
				$markerArray['###LINK_PREV###'] = '';
				$markerArray['###LINK_NEXT###'] = '';
				// render a pagebrowser if needed
				
				if ($newsCount>$this->config['limit']) {
					// configure pagebrowser
    				$this->internal['res_count'] = $newsCount;
					$this->internal['results_at_a_time'] = $this->config['limit'];
					$this->internal['maxPages'] = $this->config['pageBrowser.']['maxPages'];
					if (!$this->config['pageBrowser.']['showPBrowserText']) {
					    $this->LOCAL_LANG[$this->LLkey]['pi_list_browseresults_page'] = ''; 
					}
					
					$markerArray['###BROWSE_LINKS###'] = $this->pi_list_browseresults($this->config['pageBrowser.']['showResultCount'],$this->config['pageBrowser.']['tableParams']);
				} else {
					$markerArray['###BROWSE_LINKS###'] = '';
				}

				$content .= $this->cObj->substituteMarkerArrayCached($t['total'], $markerArray, $subpartArray, $wrappedSubpartArray);
			} elseif (ereg('1=0', $where)) {
				// first view of the search page with the parameter 'emptySearchAtStart' set
				$markerArray['###SEARCH_EMPTY_MSG###'] = $this->local_cObj->stdWrap($this->pi_getLL('searchEmptyMsg'), $this->conf['searchEmptyMsg_stdWrap.']);
				$searchEmptyMsg = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_SEARCH_EMPTY###'));

				$content .= $this->cObj->substituteMarkerArrayCached($searchEmptyMsg, $markerArray);
			} elseif ($this->piVars['swords']) {
				// no results
				$markerArray['###SEARCH_EMPTY_MSG###'] = $this->local_cObj->stdWrap($this->pi_getLL('noResultsMsg'), $this->conf['searchEmptyMsg_stdWrap.']);
				$searchEmptyMsg = $this->cObj->getSubpart($this->templateCode, $this->spMarker('###TEMPLATE_SEARCH_EMPTY###'));
				$content .= $this->cObj->substituteMarkerArrayCached($searchEmptyMsg, $markerArray);
			} else {
				$content .= $this->local_cObj->stdWrap($this->pi_getLL('noNewsToListMsg'), $this->conf['noNewsToListMsg_stdWrap.']);
			}
		}
		return $content;
	}




	/**
	 * get the content for a news item NOT displayed as single item (List & Latest)
	 *
	 * @param	array		$itemparts: parts of the html template
	 * @param	array		$selectConf: quety parameters in an array
	 * @param	string		$prefix_display: the part of the TS-setup
	 * @return	string		$itemsOut: itemlist as htmlcode
	 */
	function getListContent($itemparts, $selectConf, $prefix_display) {

		$res = $this->cObj->exec_getQuery('tt_news', $selectConf); //get query for list contents
		$itemsOut = '';

		$cc = 0;
		// Getting elements
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$wrappedSubpartArray = array();
			if ($row['type']) { // News type article or external url
				$this->local_cObj->setCurrentVal($row['type'] == 1 ? $row['page'] : $row['ext_url']);
				$wrappedSubpartArray['###LINK_ITEM###'] = $this->local_cObj->typolinkWrap($this->conf['pageTypoLink.']);
			} else {

#debug($GLOBALS['TSFE']->ATagParams);

				$wrappedSubpartArray['###LINK_ITEM###'] = explode('|', $this->pi_linkTP_keepPIvars('|', array('tt_news'=>$row['uid'],'backPid'=>$this->config['backPid']),$this->allowCaching,'',$this->config['singlePid']));

			}
			$markerArray = $this->getItemMarkerArray($row, $prefix_display);

			// Store the result of template parsing in the Var $itemsOut, use the alternating layouts
			$itemsOut .= $this->cObj->substituteMarkerArrayCached($itemparts[($cc%count($itemparts))], $markerArray, array(), $wrappedSubpartArray);
			$cc++;
			if ($cc == $this->config['limit']) {
				break;
			}
		}
		
		
		return $itemsOut;
	}


	/**
	 * build the selectconf (array of query-parameters) to get the news items from the db
	 *
	 * @param	string		$where: where-part of the query
	 * @param	integer		$noPeriod: if this value exists the listing starts with the given 'period start' (pS). If not the value period start needs also a value for 'period lenght' (pL) to display something.
	 * @return	array		the selectconf for the display of a news item
	 */
	function getSelectConf($where, $noPeriod = 0) {

		// Get news
		$selectConf = Array();
		$selectConf['pidInList'] = $this->pid_list;

		// exclude latest from search
		$selectConf['where'] = '1=1 '.($this->theCode == 'LATEST'?'':$where);

		// Archive
		if ($this->arcExclusive > 0) {
if ($this->piVars['arc']) {
				$this->arcExclusive = $this->piVars['arc'];
			}

			// Period
			if (!$noPeriod && $this->piVars['pS']) {
				$selectConf['where'] .= ' AND tt_news.datetime>='.$this->piVars['pS'];
				if ($this->piVars['pL']) {
					$selectConf['where'] .= ' AND tt_news.datetime<'.($this->piVars['pS']+$this->piVars['pL']);
				}
			}
		}

		if ($this->arcExclusive) {
			if ($this->conf['enableArchiveDate']) {
				if ($this->arcExclusive < 0) {
					$selectConf['where'] .= ' AND (tt_news.archivedate=0 OR tt_news.archivedate>'.$GLOBALS['SIM_EXEC_TIME'].')';
				} elseif ($this->arcExclusive > 0) {
					$selectConf['where'] .= ' AND tt_news.archivedate<'.$GLOBALS['SIM_EXEC_TIME'];
				}
			}
			if ($this->config['datetimeDaysToArchive']) {
				$theTime = $GLOBALS['SIM_EXEC_TIME']-intval($this->config['datetimeDaysToArchive']) * 3600 * 24;
				if ($this->arcExclusive < 0) {
					$selectConf['where'] .= ' AND (tt_news.datetime=0 OR tt_news.datetime>'.$theTime.')';
				} elseif ($this->arcExclusive > 0) {
					$selectConf['where'] .= ' AND tt_news.datetime<'.$theTime;
				}
			}
		}

		// exclude LATEST and AMENU from changing their contents with the cat selector. This can be overridden by setting the TSvars 'latestWithCatSelector' or 'amenuWithCatSelector'
		if ($this->config['catSelection'] && (($this->theCode == 'LATEST' && $this->config['latestWithCatSelector']) || ($this->theCode == 'AMENU' && $this->config['amenuWithCatSelector']) || ($this->theCode == 'LIST' || $this->theCode == 'SEARCH'))) {
			$this->config['categoryMode'] = 1; // force 'select categories' mode if cat is given in GPvars
			$this->catExclusive = $this->config['catSelection']; // override category selection from other news content-elements with the selection from the catselector
		}

		// find newsitems by their categories if categoryMode is '1' or '-1'
		if ($this->config['categoryMode']) {
			$selectConf['leftjoin'] = 'tt_news_cat_mm ON tt_news.uid = tt_news_cat_mm.uid_local';
			$selectConf['where'] .= ' AND (IFNULL(tt_news_cat_mm.uid_foreign,0) '.($this->config['categoryMode'] < 0?'NOT ':'').'IN ('.($this->catExclusive?$this->catExclusive:0).'))';
		}

		//debug(array('select_conf',$this->piVars,$selectConf,time()));
		return $selectConf;
	}

	/**
	 * Getting all tt_news_cat categories into internal array
	 *
	 * @return	void
	 */
	function initCategories() {
		// decide whether to look for categories only in the 'General record Storage page', or in the complete pagetree
		$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);
		if ($confArr['useStoragePid']) {
		    $storagePid=$GLOBALS['TSFE']->getStorageSiterootPids();
			$addquery = ' AND tt_news_cat.pid IN ('.$storagePid['_STORAGE_PID'].')';
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_news_cat LEFT JOIN tt_news_cat_mm ON tt_news_cat_mm.uid_foreign = tt_news_cat.uid', '1=1'.$addquery.$this->cObj->enableFields('tt_news_cat'));
		echo mysql_error();
		$this->categories = array();
		$this->categorieImages = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if (isset($row['uid_local'])) {
				$this->categories[$row['uid_local']][] = array(
				'title' => $row['title'],
					'image' => $row['image'],
					'shortcut' => $row['shortcut'],
					'shortcut_target' => $row['shortcut_target'],
					'catid' => $row['uid_foreign'] );
			} else {
				$this->categories['0'][$row['uid']] = $row['title'];
			}
		}
	}

	/**
	 * Generates an array,->pageArray of the pagerecords from->pid_list
	 *
	 * @return	void
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
	 * @param	array		$row: result row for a news item
	 * @param	array		$textRenderObj: conf vars for the current template
	 * @return	array		$markerArray: filled marker array
	 */
	function getItemMarkerArray ($row, $textRenderObj = 'displaySingle') {

		// config to use:
		$lConf = $this->conf[$textRenderObj.'.'];
		$this->local_cObj->start($row, 'tt_news');
		
		$markerArray = array();

		// Get and set image:
		$imageNum = isset($lConf['imageCount']) ? $lConf['imageCount']:1;
		$imageNum = t3lib_div::intInRange($imageNum, 0, 100);
		$theImgCode = '';
		$imgs = t3lib_div::trimExplode(',', $row['image'], 1);
		$imgsCaptions = explode(chr(10), $row['imagecaption']);
		reset($imgs);
		$cc = 0;

		// unset the img in the image array in single view if the var firstImageIsPreview is set
		if (count($imgs) > 1 && $this->config['firstImageIsPreview'] && $textRenderObj == 'displaySingle') {
			unset($imgs[0]);
			unset($imgsCaptions[0]);
			$cc = 1;
		}

		while (list(, $val) = each($imgs)) {
			if ($cc == $imageNum) break;
			if ($val) {
			 	$lConf['image.']['altText'] = ''; // reset altText
				$lConf['image.']['altText'] = $lConf['image.']['altText']; // set altText to value from TS
				$lConf['image.']['file'] = 'uploads/pics/'.$val;
				switch($lConf['imgAltTextField']) {
					case 'image': 
						$lConf['image.']['altText'] .= $val;
					break;
					case 'imagecaption': 
						$lConf['image.']['altText'] .= $imgsCaptions[$cc];
					break;
					default:
						$lConf['image.']['altText'] .= $row[$lConf['imgAltTextField']];
				} 
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
		$markerArray['###NEWS_AUTHOR###'] = $this->formatStr($row['author']?$this->pi_getLL('preAuthor').' '.$newsAuthor:'');
		$markerArray['###NEWS_EMAIL###'] = $this->local_cObj->stdWrap($row['author_email'], $lConf['email_stdWrap.']);
		$markerArray['###NEWS_DATE###'] = $this->local_cObj->stdWrap($row['datetime'], $lConf['date_stdWrap.']);
		$markerArray['###NEWS_TIME###'] = $this->local_cObj->stdWrap($row['datetime'], $lConf['time_stdWrap.']);
		$markerArray['###NEWS_AGE###'] = $this->local_cObj->stdWrap($row['datetime'], $lConf['age_stdWrap.']);
		$markerArray['###TEXT_NEWS_AGE###'] = $this->local_cObj->stdWrap($this->pi_getLL('textNewsAge'), $lConf['textNewsAge_stdWrap.']);

		$markerArray['###NEWS_SUBHEADER###'] = $this->formatStr($this->local_cObj->stdWrap($row['short'], $lConf['subheader_stdWrap.']));

		$markerArray['###NEWS_CONTENT###'] = $this->formatStr($this->local_cObj->stdWrap($row['bodytext'], $lConf['content_stdWrap.']));

		// Links
		$newsLinks = $row['links']?$this->formatStr($this->local_cObj->stdWrap($row['links'], $lConf['links_stdWrap.'])):'';
		$markerArray['###NEWS_LINKS###'] = $newsLinks;
		$markerArray['###TEXT_LINKS###'] = $newsLinks?$this->local_cObj->stdWrap($this->pi_getLL('textLinks'), $lConf['newsLinksHeader_stdWrap.']):'';

		$markerArray['###MORE###'] = $this->pi_getLL('more');
		
		if ($this->piVars['backPid'] && $textRenderObj == 'displaySingle') {
		    $backP = $this->pi_getRecord('pages',$this->piVars['backPid']);
		}
		$markerArray['###BACK_TO_LIST###'] =  $this->pi_getLL('backToList','',TRUE).$backP['title'];
		
		// related
		if ($textRenderObj == 'displaySingle') {
		    $tmpRelated = $this->getRelated($row['uid']);
		}
		

 		$relatedNews = $tmpRelated?$this->local_cObj->stdWrap($tmpRelated, $lConf['related_stdWrap.']):'';

		$markerArray['###NEWS_RELATED###'] = $relatedNews;
		$markerArray['###TEXT_RELATED###'] = $relatedNews ? $this->local_cObj->stdWrap($this->pi_getLL('textRelated'), $this->conf['relatedHeader_stdWrap.']):'';

		// filelinks
		
	if ($row['news_files']) {
		$markerArray['###TEXT_FILES###'] = $this->local_cObj->stdWrap($this->pi_getLL('textFiles'), $this->conf['newsFilesHeader_stdWrap.']);
		$fileArr = explode(',',$row['news_files']);
		$files = '';
	 	while(list(,$val)=each($fileArr)) {
		// fills the marker ###FILE_LINK### with the links to the atached files
			$filelinks .= $this->local_cObj->filelink($val,$this->conf['newsFiles.']) ;
		} 
		$markerArray['###FILE_LINK###'] = $this->local_cObj->stdWrap($filelinks, $this->conf['newsFiles_stdWrap.']);
	} else { // no files atached
		$markerArray['###TEXT_FILES###'] = '';
		$markerArray['###FILE_LINK###'] = '';
		}

		// Page fields:
		$markerArray['###PAGE_UID###'] = $row['pid'];
		$markerArray['###PAGE_TITLE###'] = $this->pageArray[$row['pid']]['title'];
		$markerArray['###PAGE_AUTHOR###'] = $this->local_cObj->stdWrap($this->pageArray[$row['pid']]['author'], $lConf['author_stdWrap.']);
		$markerArray['###PAGE_AUTHOR_EMAIL###'] = $this->local_cObj->stdWrap($this->pageArray[$row['pid']]['author_email'], $lConf['email_stdWrap.']);
				
		// get markers and links for categories
		$markerArray = $this->getCatMarkerArray($markerArray, $row, $lConf);
		
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

		return $markerArray;
	}
	/**
	 * Fills in the Category markerArray with data
	 *
	 * @param	array		$markerArray: partly filled marker array
	 * @param	array		$row: result row for a news item
	 * @param	array		$lConf: configuration for the current templatepart
	 * @return	array		$markerArray: filled markerarray
	 */
	function getCatMarkerArray($markerArray, $row, $lConf) {

		if (isset($this->categories[$row['uid']]) && ($this->config['catImageMode'] || $this->config['catTextMode'])) {
			$markerArray['###TEXT_CAT###'] = $this->pi_getLL('textCat');
			$markerArray['###TEXT_CAT_LATEST###'] = $this->pi_getLL('textCatLatest');

			$news_category = array();
			$theCatImgCode = '';
			$theCatImgCodeArray = array();
			while (list ($key, $val) = each ($this->categories[$row['uid']])) { // find categories, wrap them with links and collect them in the array $news_category.
				$catLinkTarget = $this->categories[$row['uid']][$key]['shortcut_target'];
				if ($this->config['catTextMode'] == 0) {
					$markerArray['###NEWS_CATEGORY###'] = '';
				} elseif($this->config['catTextMode'] == 1) { // display but don't link
					$news_category[] = $this->local_cObj->stdWrap($this->categories[$row['uid']][$key]['title'], $lConf['category_stdWrap.']);
				} elseif($this->config['catTextMode'] == 2) { // link to category shortcut
					$news_category[] = $this->pi_linkToPage($this->categories[$row['uid']][$key]['title'],$this->categories[$row['uid']][$key]['shortcut'],($catLinkTarget?$catLinkTarget:$this->config['itemLinkTarget']));
				} elseif($this->config['catTextMode'] == 3) { // act as category selector
					$news_category[] = $this->pi_linkTP_keepPIvars($this->categories[$row['uid']][$key]['title'],array('cat'=>$this->categories[$row['uid']][$key]['catid'],'backPid'=>NULL,'pointer'=>NULL),'','',($this->config['catSelectorTargetPid']?$this->config['catSelectorTargetPid']:$GLOBALS['TSFE']->id));
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
						if ($this->config['catImageMode'] == 2) { // link to category shortcut
						$sCpageId = $this->categories[$row['uid']][$key]['shortcut'];
						$sCpage = $this->pi_getRecord('pages',$sCpageId); // get the title of the shortcut page
							$catPicConf['image.']['altText'] = $sCpage['title']?$this->pi_getLL('altTextCatShortcut').$sCpage['title']:'';
							$catPicConf['image.']['stdWrap.']['innerWrap'] = $this->pi_linkToPage('|',$this->categories[$row['uid']][$key]['shortcut'],($catLinkTarget?$catLinkTarget:$this->config['itemLinkTarget']));
						}
						if ($this->config['catImageMode'] == 3) { // act as category selector
							$catPicConf['image.']['altText'] = $this->pi_getLL('altTextCatSelector').$this->categories[$row['uid']][$key]['title'];
							$catPicConf['image.']['stdWrap.']['innerWrap'] = $this->pi_linkTP_keepPIvars('|',array('cat'=>$this->categories[$row['uid']][$key]['catid'],'backPid'=>NULL,'pointer'=>NULL),'','',($this->config['catSelectorTargetPid']?$this->config['catSelectorTargetPid']:$GLOBALS['TSFE']->id));
						}
					} else {
						$catPicConf['image.']['altText'] = $this->categories[$row['uid']][$key]['title'];
					}
					// add linked category image to output array
					$theCatImgCodeArray[] = $this->local_cObj->IMAGE($catPicConf['image.']);
				}
			}
			if ($this->config['catTextMode'] != 0) {
				$news_category = implode(', ', array_slice($news_category, 0, intval($this->config['maxCatTexts'])));
				if ($this->config['catTextLength']) { // crop the complete category titles if 'catTextLength' value is given
				    $markerArray['###NEWS_CATEGORY###'] = (strlen($news_category) < intval($this->config['catTextLength'])?$news_category:substr($news_category, 0, intval($this->config['catTextLength'])).'...');
				} else {
					$markerArray['###NEWS_CATEGORY###'] = $news_category;
				}
			}
			if ($this->config['catImageMode'] != 0) {
				$theCatImgCode = implode('', array_slice($theCatImgCodeArray, 0, intval($this->config['maxCatImages']))); // downsize the image array to the 'maxCatImages' value
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
	 * Find related news records, add links to them and wrap them with stdWraps from TS.
	 *
	 * @param	integer		$uid: it of the current news item
	 * @return	string		html code for the related news list
	 */
 	function getRelated($uid) {
		$select_fields = 'uid,title,short,datetime,archivedate,type,page,ext_url';
		$lConf = $this->conf['getRelatedCObject.'];
		if ($lConf['groupBy']) {
		    $groupBy = trim($lConf['groupBy']);
		}
		if ($lConf['orderBy']) {
		    $orderBy = trim($lConf['orderBy']);
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select_fields, 'tt_news,tt_news_related_mm AS M', 'tt_news.uid=M.uid_foreign AND M.uid_local='.intval($uid),$groupBy,$orderBy);
		if ($res) {
		    $veryLocal_cObj = t3lib_div::makeInstance('tslib_cObj'); // Local cObj.
			$lines = array();
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$veryLocal_cObj->start($row, 'tt_news');
				if (!$row['type']) { // normal news
					if ($GLOBALS['TSFE']->config['config']['simulateStaticDocuments']) { 
						// to get a non-encoded parameter string, simulateStaticDocuments will be temporarily disabled if it is used. 
					    $GLOBALS['TSFE']->config['config']['simulateStaticDocuments']=0;
						// build the AddParams for the related-news link; stristr removes the part with index.php?id=xx from the beginning of the URL string. 
						$newsAddParams =  stristr($this->pi_linkTP_keepPIvars_url(array('tt_news'=>$row['uid'],'backPid'=>$this->config['backPid']),$this->allowCaching), '&');
						$GLOBALS['TSFE']->config['config']['simulateStaticDocuments']=1; 
					} else {
					    $newsAddParams =  stristr($this->pi_linkTP_keepPIvars_url(array('tt_news'=>$row['uid'],'backPid'=>$this->config['backPid']),$this->allowCaching), '&');
					}

					// load the parameter string into the register 'newsAddParams' to access it from TS
					$veryLocal_cObj->LOAD_REGISTER(array('newsAddParams' => $newsAddParams),'');
				}	
			$lines[] = $veryLocal_cObj->cObjGetSingle($this->conf['getRelatedCObject'], $this->conf['getRelatedCObject.'], 'getRelated');
			}
		return implode('', $lines);
		}
		
	}
	
	

	/**
	 * Calls user function defined in TypoScript
	 *
	 * @param	integer		$mConfKey: if this value is empty the var $mConfKey is not processed
	 * @param	mixed		$passVar: this var is processed in the user function
	 * @return	mixed		the processed $passVar
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
	 * returns the subpart name. if 'altMainMarkers.' are given this name is used instead of the default marker-name.
	 *
	 * @param	string		$subpartMarker: name of the subpart to be substituted
	 * @return	string		new name of the template subpart
	 */
	function spMarker($subpartMarker) {
		$sPBody = substr($subpartMarker, 3, -3);
		$altSPM = '';
		if (isset($this->config['altMainMarkers.'])) {
			$altSPM = trim($this->cObj->stdWrap($this->config['altMainMarkers.'][$sPBody], $this->conf['altMainMarkers.'][$sPBody.'.']));
			$GLOBALS['TT']->setTSlogMessage('Using alternative subpart marker for \''.$subpartMarker.'\': '.$altSPM, 1);
		}

		return $altSPM?$altSPM:$subpartMarker;
	}


	/**
	 * Generates a search where clause.
	 *
	 * @param	string		searchword(s)
	 * @return	string		querypart
	 */
	function searchWhere($sw) {
		$where = $this->cObj->searchWhere($sw, $this->searchFieldList, 'tt_news');
		return $where;
	}

	/**
	 * Format string with general_stdWrap from configuration
	 * this is not used in the current version, cause it collides with the proc functions of the rte
	 *
	 * @param	string		string to wrap
	 * @return	string		wrapped string
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
	 * @param	string		html code of the template subpart
	 * @param	integer		number of alternatingLayouts
	 * @param	string		name of the content-markers in this template-subpart
	 * @return	array		html code for alternating content markers
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
