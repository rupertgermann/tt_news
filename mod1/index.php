<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Rupert Germann <rg@rgdata.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  106: class tx_ttnews_module1 extends t3lib_SCbase
 *  129:     function init()
 *  204:     function main()
 *  266:     function printContent()
 *
 *              SECTION: Module content
 *  298:     function moduleContent()
 *  335:     function displayOverview()
 *  389:     function displayCategoryTree()
 *  409:     function getTreeObj()
 *  461:     function displayNewsList()
 *
 *              SECTION: AJAX functions
 *  538:     function ajaxExpandCollapse($params, &$ajaxObj)
 *  556:     function ajaxLoadList($params, &$ajaxObj)
 *  568:     function processAjaxRequestConstruct()
 *
 *              SECTION: GUI Elements
 *  602:     function getListHeaderMsg()
 *  646:     function renderTreeCheckBoxes()
 *  679:     function renderListCheckBoxes()
 *  701:     function renderNewCatButton()
 *  720:     function getHeaderButtons()
 *
 *              SECTION: Internal helper functions
 *  830:     function setPidList()
 *  849:     function setEditablePages($pidlist)
 *  869:     function getSubPages($pages, $cc = 0)
 *  896:     function initGPvars()
 *  915:     function initSubCategories()
 *  932:     function menuConfig()
 *  958:     function checkPageAccess($pid)
 *  982:     function getCbLinkParams()
 *
 *
 * 1008: class tx_ttnewscatmanager_treeView extends tx_ttnews_categorytree
 * 1020:     function wrapIcon($icon,&$row)
 * 1038:     function wrapTitle($title,$v)
 * 1062:     function makeControl($table,$row)
 * 1116:     function issueCommand($params,$rUrl='')
 *
 * TOTAL FUNCTIONS: 28
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require('conf.php');
require_once($BACK_PATH.'init.php');
if (tx_ttnews_compatibility::getInstance()->int_from_ver(TYPO3_version) < 6002000) {
	require_once($BACK_PATH.'template.php');
}


$GLOBALS['LANG']->includeLLFile('EXT:tt_news/mod1/locallang.xml');
//require_once(PATH_t3lib.'class.t3lib_scbase.php');
$GLOBALS['BE_USER']->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]




require_once(t3lib_extMgm::extPath('tt_news').'lib/class.tx_ttnews_div.php');
require_once(t3lib_extMgm::extPath('tt_news').'lib/class.tx_ttnews_categorytree.php');
require_once(t3lib_extMgm::extPath('tt_news').'lib/class.tx_ttnews_recordlist.php');


/**
 * Module 'News Admin' for the 'tt_news' extension.
 *
 *
 * $Id$
 *
 * @author	Rupert Germann <rg@rgdata.de>
 * @package	TYPO3
 * @subpackage	tt_news
 */
class tx_ttnews_module1 extends t3lib_SCbase {
	var $pageinfo;
	var $treeObj;
	var $markers = array();
	var $docHeaderButtons = array();
	var $selectedCategories;	// list of selected category from GETvars extended by subcategories
	var $useSubCategories = TRUE;

	var $limit = 20;
	var $TSprop = array();
	var $fieldList = 'uid,title,datetime,archivedate,tstamp,category;author';
	var $permsCache = array();
	var $pidList = 0;
	var $storagePid = 0;

	var $localCalcPerms = 0; // CALC_PERMS for the current page. Used for the module header.
	var $grspCalcPerms = 0; // CALC_PERMS for the "general record storage page". Used for categories.
	var $newArticleCalcPerms = 0; // CALC_PERMS for the page with news articles (newArticlePid).


	var $excludeCats = array();
	var $includeCats = array();

	/**
	 * Initializes the Module
	 *
	 * @return	void
	 */
	function init()	{
//		$s = microtime(TRUE);

		if (!$this->MCONF['name']) {
			$this->MCONF = $GLOBALS['MCONF'];
		}
		$this->isAdmin = $GLOBALS['BE_USER']->isAdmin();

		$this->id = intval(t3lib_div::_GP('id'));
//		$this->CMD = t3lib_div::_GP('CMD');
		$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);

		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id,'mod.'.$this->MCONF['name']);
		$this->TSprop = $this->modTSconfig['properties'];
		$this->confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);

		$tceTSC = array();
		if ($this->confArr['useStoragePid']) {
			$tceTSC = t3lib_BEfunc::getTCEFORM_TSconfig('tt_news_cat',array('pid'=>$this->id));
		}
		$this->storagePid = $tceTSC['_STORAGE_PID']?$tceTSC['_STORAGE_PID']:$this->id;

		$newArticlePid = intval($this->TSprop['list.']['pidForNewArticles']);
		$this->newArticlePid = ($newArticlePid?$newArticlePid:$this->id);

		$this->script = 'index.php';

		if (($fieldList = $this->TSprop['list.']['fList'])) {
			$this->fieldList = $fieldList;
		}

		// get pageinfo array for the current page
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$this->localCalcPerms = $GLOBALS['BE_USER']->calcPerms($this->pageinfo);

		// get pageinfo array for the GRSP
		$grspPI = t3lib_BEfunc::readPageAccess($this->storagePid,$this->perms_clause);
		$this->grspCalcPerms = $GLOBALS['BE_USER']->calcPerms($grspPI);
		$this->mayUserEditCategories = $this->grspCalcPerms&16;

		// get pageinfo array for newArticlePid
		$newArticlePidPI = t3lib_BEfunc::readPageAccess($this->newArticlePid,$this->perms_clause);
		$this->newArticleCalcPerms = $GLOBALS['BE_USER']->calcPerms($newArticlePidPI);
		$this->mayUserEditArticles = $this->newArticleCalcPerms&16;

		$pagesTSC = t3lib_BEfunc::getPagesTSconfig($this->id);
		if ($pagesTSC['tx_ttnews.']['singlePid']) {
			$this->singlePid = intval($pagesTSC['tx_ttnews.']['singlePid']);
		}



		$this->initCategories();

		$this->setPidList();
		$this->initPermsCache();
		if ($this->pidList) {
			$this->setEditablePages($this->pidList);
		}



		$this->menuConfig();
		$this->mData = $GLOBALS['BE_USER']->uc['moduleData']['web_txttnewsM1'];


		$this->current_sys_language = intval($this->MOD_SETTINGS['language']);
		$this->searchLevels = intval($this->MOD_SETTINGS['searchLevels']);
		$this->thumbs = intval($this->MOD_SETTINGS['showThumbs']);

		$limit = intval($this->MOD_SETTINGS['showLimit']);
		if ($limit) {
			$this->showLimit = $limit;
		} else {
			$this->showLimit = intval($this->TSprop['list.']['limit']);
		}



		$this->initGPvars();
//		debug((microtime(TRUE)-$s), 'time ('.__CLASS__.'::'.__FUNCTION__.')', __LINE__, __FILE__, 3);

	}





	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	[type]		...
	 */
	function main()	{
		global $LANG;

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('mod_ttnews_admin.html');
		$this->doc->docType = 'xhtml_trans';



		if (!$this->doc->moduleTemplate) {
			t3lib_div::devLog('cannot set moduleTemplate', 'tt_news', 2, array(
					'backpath' => $this->doc->backPath,
					'filename from TBE_STYLES' => $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_ttnews_admin.html'],
					'full path' => $this->doc->backPath.$GLOBALS['TBE_STYLES']['htmlTemplates']['mod_ttnews_admin.html']
			));
			$tfile = t3lib_extMgm::siteRelPath('tt_news').'mod1/mod_ttnews_admin.html';
			$this->doc->moduleTemplate = @file_get_contents(PATH_site.$tfile);
		}



		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user

		$access = (is_array($this->pageinfo) ? 1 : 0);
		$this->markers['MOD_INFO'] = '';


		if ($this->id && $access)	{
				// JavaScript
			$this->doc->JScode = $this->doc->wrapScriptTags('
				script_ended = 0;
				function jumpToUrl(URL)	{	//
					window.location.href = URL;
				}
			'.($this->singlePid ?
			'
			function openFePreview(URL) {
				previewWin=window.open(URL,\'newTYPO3frontendWindow\');
				previewWin.focus();
			} '
			: '').$this->doc->redirectUrls());

			$this->doc->postCode=$this->doc->wrapScriptTags('
					script_ended = 1;
				');
			$this->doc->inDocStylesArray['tt_news_mod1'] = '
				#ttnewsadmin-tree {
					float:left;
					overflow-x: auto;
					overflow-y: auto;
					width:230px;
					border-right: 1px solid #ccc;
				}
				#ttnews-cat-tree { margin-bottom: 15px; }
				#ttnewsadmin-list {  padding: 0 10px 0 240px; }
				#togglesubcats { background:#ddd; padding: 2px; cursor: pointer; font-style:italic; }
				#newssubcats { background:#f8f9fa; padding: 2px; border:1px solid #ddd; }
				#resetcatselection { float:right; font-style:italic; }
				#ttnewsadmin-search {  padding: 0; margin:0; }
				#ttnewsadmin-search input {  margin: 0 3px; }

				span.hiddencat { color:#999; }
				span.list-cb { padding-right:15px;}

				table.typo3-dblist tr td.col-icon a {
					width: 18px;
					display: inline;
				}
				div.docheader-row2-right {
					margin-top: -3px;
				}
				div.ttnewsadmin-pagination {
					padding: 5px 0;
					white-space: nowrap;
				}
				div.ttnewsadmin-pagination img, div.ttnewsadmin-pagination span.pageIndicator {
					margin-right: 6px;
				}
				div.ttnewsadmin-pagination img {
					vertical-align: bottom;
					padding-bottom: 2px;
				}


			';

			// Render content:
			$this->moduleContent();
		} else {
				// If no access or if ID == zero
			$this->displayOverview();
		}
		$this->docHeaderButtons = $this->getHeaderButtons();
		$this->markers['FUNC_MENU'] = ''/*t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])*/;
		$this->markers['TREE'] = $this->treeContent;
		$this->markers['LIST'] = $this->listContent;
		$this->markers['CSH'] = $this->docHeaderButtons['csh'];
		$this->markers['LANG_MENU'] = $this->getLangMenu();
		$this->markers['PAGE_SELECT'] = $this->getPageSelector();

		// put it all together
		$this->content = $this->doc->startPage($LANG->getLL('title'));
		$this->content.= $this->doc->moduleBody($this->pageinfo, $this->docHeaderButtons, $this->markers);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);

		if (count($this->permsCache)) {
			$GLOBALS['BE_USER']->setAndSaveSessionData('permsCache', array($this->pidChash => $this->permsCache));
		}
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}

















	/*************************************************************************
	 *
	 * 		Module content
	 *
	 ************************************************************************/


	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent()	{
		$error = false;
//		switch((string)$this->MOD_SETTINGS['function'])	{
//			case 1:
				$this->table = 'tt_news_cat';
				if ($this->confArr['useStoragePid']) {
					$catRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid','tt_news_cat','pid='.$this->storagePid.$this->catlistWhere.' AND deleted=0');
					if (empty($catRows)) {
						$error = $this->displayOverview();
					}
				}
				if (!$error) {
					$this->doc->JScodeLibArray['txttnewsM1'] = '
						<script src="'.$GLOBALS['BACK_PATH'].t3lib_extMgm::extRelPath('tt_news').'js/tt_news_mod1.js" type="text/javascript"></script>
						';
					$this->doc->getDragDropCode('tt_news_cat');
					$this->doc->postCode=$this->doc->wrapScriptTags('
							txttnewsM1js.registerDragDropHandlers();
					');
					$this->doc->getContextMenuCode();

					$this->treeContent = $this->displayCategoryTree();
					$this->listContent .= $this->displayNewsList();


				}
//			break;
//		}
	}



	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function displayOverview() {
		$tRows = array();
		$tRows[] = '<tr>
				<td colspan="2" valign="top"><p><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/icon_note.gif','width="18" height="16"').' title="" alt="" />
				'.$GLOBALS['LANG']->getLL('nothingfound').'
				</p><br></td>
				</tr>';

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'pid,count(uid)',
					'tt_news_cat',
					'pid>=0'.$this->catlistWhere.t3lib_BEfunc::deleteClause('tt_news_cat'),
					'pid'
				);
		while(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)))	{
			$list[$row['pid']]['count'] = $row['count(uid)'];
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		$tRows[] = '
			<tr>
				<td class="c-headLine"><strong>'.$GLOBALS['LANG']->getLL('path').'</strong></td>
				<td class="c-headLine"><strong>'.$GLOBALS['LANG']->getLL('categories').'</strong></td>

			</tr>';

		if (is_array($list))	{
			foreach($list as $pid => $stat)	{
				$pa = $this->getPageInfoForOverview($pid);
				if ($pa['path'])	{
					$tRows[] = '
						<tr class="bgColor4">
							<td><a href="index.php?id='.$pid.'">'.htmlspecialchars($pa['path']).'</a></td>
							<td>'.htmlspecialchars($stat['count']).'</td>

						</tr>';
				}
			}

				// Create overview
			$outputString = '<table border="0" cellpadding="1" cellspacing="2" id="typo3-page-stdlist">'.implode('',$tRows).'</table>';

				// Add output:
			$this->markers['MOD_INFO'] = $outputString;
			return TRUE;
		}
	}


	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function displayCategoryTree() {
		$content = '';
		$content .= $this->renderTreeCheckBoxes();
		$content .= $this->renderNewCatButton();

		$this->getTreeObj();
		$content .= '<div id="ttnews-cat-tree">'.$this->sPageIcon.$this->treeObj->getBrowsableTree().'</div>';

		return $content;
	}





	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getTreeObj() {
		$addWhere = '';




		if ($this->confArr['useStoragePid']) {
			$addWhere .= ' AND tt_news_cat.pid=' . $this->storagePid;
		}
		if (!$this->mData['showHiddenCategories']) {
			$addWhere .= ' AND tt_news_cat.hidden=0';
		}

		$treeOrderBy = $this->confArr['treeOrderBy']?$this->confArr['treeOrderBy']:'uid';

		if (!is_object($this->treeObj)) {
			$this->treeObj = t3lib_div::makeInstance('tx_ttnewscatmanager_treeView');
		}

		$this->treeObj->table = 'tt_news_cat';
		$this->treeObj->init($this->catlistWhere.$addWhere,$treeOrderBy);
		$this->treeObj->parentField = 'parent_category';
		$this->treeObj->thisScript = $this->script.'?id='.$this->id;
		$this->treeObj->returnUrl = t3lib_extMgm::extRelPath('tt_news').'mod1/'.$this->treeObj->thisScript;
		$this->treeObj->fieldArray = array('uid','title','description','hidden','starttime','endtime','fe_group'); // those fields will be filled to the array $this->treeObj->tree
		$this->treeObj->mayUserEditCategories = $this->mayUserEditCategories;
		$this->treeObj->title = $GLOBALS['LANG']->getLL('treeTitle');
		$this->treeObj->pageID = $this->id;
		$this->treeObj->storagePid = $this->storagePid;
		$this->treeObj->useStoragePid = $this->confArr['useStoragePid'];

		$this->treeObj->expandAll = $GLOBALS['SOBE']->MOD_SETTINGS['expandAll'];
		$this->treeObj->expandable = true;
		$this->treeObj->expandFirst = $this->TSprop['catmenu.']['expandFirst'];
		$this->treeObj->titleLen = 60;
		$this->treeObj->useAjax = true;
		$this->treeObj->showEditIcons = $this->mData['showEditIcons'];
		$this->treeObj->showHiddenCategories = $this->mData['showHiddenCategories'];
		$this->treeObj->category = $this->category;
		$this->treeObj->current_sys_language = $this->current_sys_language;



		// get selected categories from be user/group without subcategories
		$tmpsc = tx_ttnews_div::getBeUserCatMounts(FALSE);
		$beUserSelCatArr = t3lib_div::intExplode(',',$tmpsc);
		$includeListArr = tx_ttnews_div::getIncludeCatArray();
		$subcatArr = array_diff($includeListArr,$beUserSelCatArr);


		/**
		 * TODO:
		 * filter out double mounts
		 */


		// get all selected category records from the current storagePid which are not 'root' categories
		// and add them as tree mounts. Subcategories of selected categories will be excluded.
		$cMounts = array();
		$nonRootMounts = FALSE;
		foreach ($beUserSelCatArr as $catID) {
			$tmpR = t3lib_BEfunc::getRecord('tt_news_cat',$catID,'parent_category,hidden',$addWhere);
			if (is_array($tmpR) && !in_array($catID,$subcatArr)) {
				if ($tmpR['parent_category'] > 0) {
					$nonRootMounts = TRUE;
					$this->sPageIcon = $this->getStoragePageIcon();
				}
				if ($this->mData['showHiddenCategories']) {
					$cMounts[] = $catID;
				} else {
					if ($tmpR['hidden'] == 0) {
						$cMounts[] = $catID;
					}
				}
			}
		}
		if ($nonRootMounts) {
			$this->treeObj->MOUNTS = $cMounts;

		}
	}




	/**
	 * returns the root element for a category tree: icon, title and pageID
	 *
	 * @return	[type]		...
	 */
	function getStoragePageIcon() {



		if ($this->confArr['useStoragePid']) {
			$tmpt = $this->treeObj->table;
			$this->treeObj->table = 'pages';
			$rootRec = $this->treeObj->getRecord($this->storagePid);
			$icon = $this->treeObj->getIcon($rootRec);
			$this->treeObj->table = $tmpt;
			$pidLbl = sprintf($GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang_tca.xml:tt_news.treeSelect.pageTitleSuffix'),$this->storagePid);
		} else {
			$rootRec = $this->treeObj->getRootRecord($this->storagePid);
			$icon = $this->treeObj->getRootIcon($rootRec);
			$pidLbl = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang_tca.xml:tt_news.treeSelect.pageTitleSuffixNoGrsp');
		}
		$pidLbl = ' <span class="typo3-dimmed"><em>'.$pidLbl.'</em></span>';
		$hrefTitle = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/mod1/locallang.xml:showAllResetSel');

		$out = '<div style="margin: 2px 0 -5px 0;">'
					.$icon
					.'<a href="'.t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT').'?id='.$this->id.'" title="'.$hrefTitle.'">'.$rootRec['title'].'</a>'
					.$pidLbl
				.'</div>';
		return $out;
	}






	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function displayNewsList()	{
		$content = '';

		$this->initSubCategories();

		$table = 'tt_news';
		/* @var $dblist tx_ttnews_recordlist */
		$dblist = t3lib_div::makeInstance('tx_ttnews_recordlist');


		$dblist->backPath = $GLOBALS['BACK_PATH'];
		$dblist->script = $this->script;
		$dblist->doEdit = $this->mayUserEditArticles;
		$dblist->ext_CALC_PERMS = $this->newArticleCalcPerms;
		$dblist->perms_clause = $this->perms_clause;
		$dblist->agePrefixes = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears');
		$dblist->id = $this->id;
		$dblist->newRecPid = $this->newArticlePid;
		$dblist->singlePid = $this->singlePid;
		$dblist->selectedCategories = $this->selectedCategories;
		$dblist->category = $this->category;
		$dblist->returnUrl = t3lib_extMgm::extRelPath('tt_news').'mod1/'.$dblist->listURL($this->id,FALSE);
		$dblist->excludeCats = $this->excludeCats;
		$dblist->includeCats = $this->includeCats;
		$dblist->isAdmin = $this->isAdmin;
		$dblist->current_sys_language = $this->current_sys_language;
		$dblist->showOnlyEditable = $this->mData['showOnlyEditable'];
		$dblist->pidList = $this->pidList;
		$dblist->editablePagesList = $this->editablePagesList;

		$dblist->start($this->id,$table,$this->pointer,$this->search_field,$this->searchLevels,$this->showLimit);

		$externalTables[$table][0]['fList'] = $this->fieldList;
		$externalTables[$table][0]['icon'] = $this->TSprop['list.']['icon'];

		$dblist->externalTables = $externalTables;
		$dblist->no_noWrap = TRUE;
		$dblist->lTSprop = $this->TSprop['list.'];
		$dblist->thumbs = $this->thumbs;
		$dblist->pObj = &$this;

		$dblist->generateList();

		if (is_array($this->TSprop['list.']['show.']) && $this->TSprop['list.']['show.']['search']) {
			$search = $this->displaySearch($dblist->listURL($this->id,FALSE));
			$content .= '<div style="float:right;">'.$search.'</div>';
		}

		$content .= $this->renderListCheckBoxes();
		$content .= $this->getListHeaderMsg($dblist);
		$content .= $dblist->HTMLcode;

		$content = '<form action="'.htmlspecialchars($dblist->listURL()).'" method="post" name="dblistForm">'.$content.'</form>';


		return '<div id="ttnewslist">'.$content.'</div>';
	}













	/*************************************************************************
	 *
	 * 		AJAX functions
	 *
	 ************************************************************************/


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$params: ...
	 * @param	[type]		$ajaxObj: ...
	 * @return	[type]		...
	 */
	function ajaxExpandCollapse($params, &$ajaxObj) {
		$this->init();
		$this->getTreeObj();
		$tree = $this->treeObj->getBrowsableTree();
		if (!$this->treeObj->ajaxStatus) {
			$ajaxObj->setError($tree);
		} else	{
			$ajaxObj->addContent('tree', $tree);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$params: ...
	 * @param	[type]		$ajaxObj: ...
	 * @return	[type]		...
	 */
	function ajaxLoadList($params, &$ajaxObj) {
		$this->processAjaxRequestConstruct();
		$this->init();
		$list = $this->displayNewsList();
		$ajaxObj->addContent('ttnewslist', $list);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function processAjaxRequestConstruct() {
		if (tx_ttnews_compatibility::getInstance()->int_from_ver(TYPO3_version) < 6002000) {
		require_once(PATH_typo3.'template.php');
		}

		global $SOBE;

			// Create a new anonymous object:
		$SOBE = new stdClass();
			// Create an instance of the document template object
		$SOBE->doc = t3lib_div::makeInstance('template');
		$SOBE->doc->backPath = $GLOBALS['BACK_PATH'];
		$SOBE->doc->docType = 'xhtml_trans';

	}








	/*************************************************************************
	 *
	 * 		GUI Elements
	 *
	 ************************************************************************/



	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getListHeaderMsg(&$dblist) {
		global $LANG;


		$noCatSelMsg = false;
		if (!$this->selectedCategories)  {
			if ($this->TSprop['list.']['noListWithoutCatSelection']) {
				$content = '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/icon_note.gif','width="18" height="16"').' title="" alt="" />'.$LANG->getLL('selectCategory');
				$noCatSelMsg = true;
			} else {
				$content = $LANG->getLL('showingAll');
			}

		} else {
			$table = 'tt_news_cat';
			$row = t3lib_BEfunc::getRecord($table, $this->category);
//			$reset = '<a href="'.$this->script.'?id='.$this->id.'" id="resetcatselection">'.$LANG->getLL('resetCatSelection').'</a>';
			$title = '<strong>'.t3lib_BEfunc::getRecordTitle($table,$row).'</strong>';
			$content = '<div id="newscatsmsg">'.$LANG->getLL('showingOnlyCat').$title.'</div>';

			if ($this->useSubCategories && ($subCats = t3lib_div::rmFromList($this->category,$this->selectedCategories))) {
				if (!$this->mData['showHiddenCategories']) {
					$addWhere = ' AND hidden=0';
				}
				$scRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title,hidden',$table,'uid IN ('.$subCats.')'.$addWhere);
				$scTitles = array();
				foreach ($scRows as $scRow) {
					$recTitle = t3lib_BEfunc::getRecordTitle($table,$scRow);
					if ($scRow['hidden']) {
						$recTitle = '<span class="hiddencat">'.$recTitle.'</span>';
					}
					$scTitles[] = $recTitle;
				}
				if (count($scTitles)) {
					$showLbl = $LANG->getLL('showSubcatgories');
					$hideLbl = $LANG->getLL('hideSubcatgories');
					$btnID = 'togglesubcats';
					$elID = 'newssubcats';
					$onclick = htmlspecialchars('if ($(\''.$elID.'\').visible()) {$(\''.$elID.'\').hide();$(\''.$btnID.'\').update('.$LANG->JScharCode($showLbl).');} else {$(\''.$elID.'\').show();$(\''.$btnID.'\').update('.$LANG->JScharCode($hideLbl).');}');
					$content .= '<div id="'.$btnID.'" onclick="'.$onclick.'">'.$showLbl.'</div>';
					$content .= '<div id="'.$elID.'" style="display:none;">'.implode(', ',$scTitles).'</div>';
				}
			}
		}
		if ($dblist->totalItems == 0) {
			$content .= $this->getNoResultsMsg($dblist,$noCatSelMsg);
		}
		$content = '<div style="padding:5px 0;">'.$content.'</div>';
		return $content;
	}

	function displaySearch($url) {
		$formElements=array('<form action="'.htmlspecialchars($url).'" method="post">','</form>');
		$content = '';
			// Table with the search box:
		$content.= '
			'.$formElements[0].'

				<!--
					Search box:
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="ttnewsadmin-search">
					<tr>
						<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.enterSearchString',1).'<input type="text" name="search_field" value="'.htmlspecialchars($this->search_field).'"'.$GLOBALS['TBE_TEMPLATE']->formWidth(10).' /></td>
						<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showRecords',1).':<input type="text" name="SET[showLimit]" value="'.htmlspecialchars($this->showLimit?$this->showLimit:'').'"'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).' /></td>
						<td><input type="submit" name="search" value="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.search',1).'" /></td>

					</tr>
				</table>
			'.$formElements[1];
		return $content;

	}

	function getNoResultsMsg(&$listObj,$noCatSelMsg) {
		$content = '';
		$tRows = array();
		if (!$noCatSelMsg) {
				$tRows[] = '<tr>
					<td valign="top"><p><img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/icon_note.gif','width="18" height="16"').' title="" alt="" />
					'.$GLOBALS['LANG']->getLL('noNewsFound').'
					</p></td>
					</tr>';
		}


		if ($this->mayUserEditArticles) {
			$tRows[] = '<tr>
					<td valign="top"><div style="padding:10px 0;">'.$listObj->getNewRecordButton('tt_news',true).'</div></td>
					</tr>';
		}

		$content .= '<table border="0" cellpadding="1" cellspacing="2" id="typo3-page-stdlist">'.implode('',$tRows).'</table>';
		return $content;
	}




	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function renderTreeCheckBoxes() {
		$show = array();
		if (is_array($this->TSprop['catmenu.']['show.'])) {
			$show = $this->TSprop['catmenu.']['show.'];
		}
		$allowedCbNames = array('expandAll','showHiddenCategories');

		if ($this->mayUserEditCategories) {
			$allowedCbNames[] = 'showEditIcons';
		}

		$params = $this->getLinkParams();
		$out = array();
		foreach ($allowedCbNames as $n) {
			if ((bool)$show['cb_'.$n]) {
				$out[] = t3lib_BEfunc::getFuncCheck($params,'SET['.$n.']',$this->MOD_SETTINGS[$n],'','','id="cb-'.$n.'"').
					' <label for="cb-'.$n.'">'.$GLOBALS['LANG']->getLL($n,1).'</label>';
			}
		}

		$content = '<div>'.implode('<br />',$out).'</div>';
		return $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function renderListCheckBoxes() {
		$show = array();
		if (is_array($this->TSprop['list.']['show.'])) {
			$show = $this->TSprop['list.']['show.'];
		}
		$allowedCbNames = array();
		if (t3lib_div::inList($this->fieldList,'image')) {
			$allowedCbNames[] = 'showThumbs';
		}
		if (!$this->isAdmin) {
			$allowedCbNames[] = 'showOnlyEditable';
		}
		$params = $this->getLinkParams();
		$out = array();
		foreach ($allowedCbNames as $n) {
			if ((bool)$show['cb_'.$n]) {
				$out[] = '<span class="list-cb">' .
						t3lib_BEfunc::getFuncCheck($params, 'SET['.$n.']', $this->MOD_SETTINGS[$n], 'index.php', '', 'id="cb-' . $n . '"') .
					' <label for="cb-'.$n.'">'.$GLOBALS['LANG']->getLL($n,1).'</label></span>';
			}
		}

		$content = '<div>'.implode('',$out).'</div>';
		return $content;
	}


	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function renderNewCatButton() {
		$show = array();
		if (is_array($this->TSprop['catmenu.']['show.'])) {
			$show = $this->TSprop['catmenu.']['show.'];
		}
		if ($this->mayUserEditCategories && (bool)$show['btn_newCategory'])	{
			$params = '&edit[tt_news_cat]['.$this->storagePid.']=new';
			$onclick = htmlspecialchars(t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'],$this->returnUrl));
			$button = '<a href="#" onclick="'.$onclick.'">'.
				'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/new_el.gif').' title="'.$GLOBALS['LANG']->getLL('createCategory',1).'" alt="" /> '.
			$GLOBALS['LANG']->getLL('createCategory').
				'</a>';
		}
		return '<div style="padding:5px 0;">'.$button.'</div>';
	}



	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array		all available buttons as an assoc. array
	 */
	function getHeaderButtons()	{
		global $LANG;

		$buttons = array(
			'csh' => '',
			'view' => '',
			'edit' => '',
			'record_list' => '',
//			'new_record' => '',
//			'paste' => '',
			'level_up' => '',
			'reload' => '',
			'shortcut' => '',
			'back' => '',
			'csv' => '',
			'export' => ''
		);


		$backPath = $GLOBALS['BACK_PATH'];

			// CSH
// 		if (!strlen($this->id))	{
// 			$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_txttnewsM1', 'list_module_noId', $backPath);
// 		} elseif(!$this->id) {
// 			$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_txttnewsM1', 'list_module_root', $backPath);
// 		} else {
// 			$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_txttnewsM1', 'list_module', $backPath);
// 		}

		if (isset($this->id)) {
			if ($GLOBALS['BE_USER']->check('modules','web_list'))	{


				$href = t3lib_BEfunc::getModuleUrl('web_list', array ('id' => $this->pageinfo['uid'], 'returnUrl' => t3lib_div::getIndpEnv('REQUEST_URI')) );


				$buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '">' .
						'<img' . t3lib_iconWorks::skinImg($backPath, 'gfx/list.gif', 'width="11" height="11"') . ' title="' . $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.showList', 1) . '" alt="" />' .
						'</a>';
			}

				// View
			$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewOnClick($this->id, $backPath, t3lib_BEfunc::BEgetRootLine($this->id))) . '">' .
							'<img' . t3lib_iconWorks::skinImg($backPath, 'gfx/zoom.gif') . ' title="' . $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', 1) . '" alt="" />' .
							'</a>';

				// If edit permissions are set (see class.t3lib_userauthgroup.php)
			if ($this->localCalcPerms&2 && !empty($this->id))	{
					// Edit
				$params = '&edit[pages][' . $this->pageinfo['uid'] . ']=edit';
				$buttons['edit'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick($params, $backPath, -1)) . '">' .
								'<img' . t3lib_iconWorks::skinImg($backPath, 'gfx/edit2.gif') . ' title="' . $LANG->getLL('editPage', 1) . '" alt="" />' .
								'</a>';
			}

//			if ($this->table) {
					// Export
				if (t3lib_extMgm::isLoaded('impexp')) {
					$modUrl = t3lib_extMgm::extRelPath('impexp') . 'app/index.php';
					$params = $modUrl . '?tx_impexp[action]=export&tx_impexp[list][]=';
					$params .= rawurlencode('tt_news:' . $this->id).'&tx_impexp[list][]=';
					$params .= rawurlencode('tt_news_cat:' . $this->id);
					$buttons['export'] = '<a href="' . htmlspecialchars($backPath.$params).'">' .
									'<img' . t3lib_iconWorks::skinImg($backPath, t3lib_extMgm::extRelPath('impexp') . 'export.gif') . ' title="' . $LANG->sL('LLL:EXT:lang/locallang_core.php:rm.export', 1) . '" alt="" />' .
									'</a>';
				}
//			}

				// Reload
			$buttons['reload'] = '<a href="' . htmlspecialchars(t3lib_div::linkThisScript()) . '">' .
							'<img' . t3lib_iconWorks::skinImg($backPath, 'gfx/refresh_n.gif') . ' title="' . $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.reload', 1) . '" alt="" />' .
							'</a>';

				// Shortcut
			if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
				$buttons['shortcut'] = $this->doc->makeShortcutIcon('id, showThumbs, pointer, table, search_field, searchLevels, showLimit, sortField, sortRev', implode(',', array_keys($this->MOD_MENU)), 'web_txttnewsM1');
			}

				// Back
			if ($this->returnUrl) {
				$buttons['back'] = '<a href="' . htmlspecialchars(t3lib_div::linkThisUrl($this->returnUrl, array('id' => $this->id))) . '" class="typo3-goBack">' .
								'<img' . t3lib_iconWorks::skinImg($backPath, 'gfx/goback.gif') . ' title="' . $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.goBack', 1) . '" alt="" />' .
								'</a>';
			}
		}

		return $buttons;
	}


	function getLangMenu() {
		if (count($this->MOD_MENU['language'])>1) {
			$menu = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xml:LGL.language',1) .
				t3lib_BEfunc::getFuncMenu($this->id,'SET[language]',$this->current_sys_language,$this->MOD_MENU['language']);
		}
		return $menu;
	}




	function getPageSelector() {
		if (count($this->MOD_MENU['searchLevels'])>1) {
			$menu = $GLOBALS['LANG']->getLL('enterSearchLevels') .
				t3lib_BEfunc::getFuncMenu($this->id,'SET[searchLevels]',$this->searchLevels,$this->MOD_MENU['searchLevels']);
		}

		return $menu;
	}









	/*************************************************************************
	 *
	 * 		Internal helper functions
	 *
	 ************************************************************************/



	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function setPidList() {
		if (!$this->isAdmin) {
				// get allowed pages
			$webmounts = $GLOBALS['BE_USER']->returnWebmounts();
			if (is_array($webmounts)) {
				$pidList = '';
				foreach ($webmounts as $mount) {
					$pidList .= ','.$mount.','.$this->getSubPages($mount);
				}
				$pidList = t3lib_div::uniqueList($pidList);
				$this->pidList = ($pidList?$pidList:0);
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$pidlist: ...
	 * @return	[type]		...
	 */
	function setEditablePages($pidlist) {
		$pids = explode(',',$pidlist);
		$editPids = array();
		foreach ($pids as $pid) {
			if (($this->checkPageAccess($pid))) {
				$editPids[] = $pid;
			}
		}
		$this->editablePagesList = implode(',',$editPids);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$pages: ...
	 * @param	[type]		$cc: ...
	 * @return	[type]		...
	 */
	function getSubPages($pages, $cc = 0) {
		$pArr = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			'pages',
			'pages.pid IN ('.$pages.') AND pages.deleted=0 AND '.$this->perms_clause);

		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$cc++;
			if ($cc > 10000) {
				return implode(',', $pArr);
			}
			$subpages = $this->getSubPages($row['uid'], $cc);
			$subpages = $subpages?','.$subpages:'';
			$pArr[] = $row['uid'].$subpages;
		}
		$pages = implode(',', $pArr);
		return $pages;
	}



	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function initGPvars() {
		$this->pointer = $this->compatibility()->intInRange(t3lib_div::_GP('pointer'),0,100000);
		$this->category = intval(t3lib_div::_GP('category'));
		$this->search_field = t3lib_div::_GP('search_field');

	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{


		$this->MOD_MENU = array (
			'function' => array (
				'1' => $GLOBALS['LANG']->getLL('function1'),
//				'2' => $GLOBALS['LANG']->getLL('function2'),
			),
			'showEditIcons' => 0,
			'expandAll' => 0,
//			'useSubCategories' => 0,
			'showOnlyEditable' => 0,
			'showHiddenCategories' => 0,
			'searchLevels' => array(
				-1 => $GLOBALS['LANG']->getLL('allPages'),
				0 => $GLOBALS['LANG']->getLL('thisPage'),
				1 => $GLOBALS['LANG']->getLL('oneLevel'),
				2 => $GLOBALS['LANG']->getLL('twoLevels'),
				3 => $GLOBALS['LANG']->getLL('threeLevels'),
				4 => $GLOBALS['LANG']->getLL('fourLevels')

			),
			'showThumbs' => 1,
			'showLimit' => 0,
			'language' => array(
				0 => $GLOBALS['LANG']->getLL('defaultLangLabel')
			)

		);
		$this->initLanguageMenu();

		$this->MOD_MENU['function'] = $this->mergeExternalItems($this->MCONF['name'],'function',$this->MOD_MENU['function']);
		$this->MOD_MENU['function'] = t3lib_BEfunc::unsetMenuItems($this->modTSconfig['properties'],$this->MOD_MENU['function'],'menu.function');

		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name'], $this->modMenu_type, $this->modMenu_dontValidateList, $this->modMenu_setDefaultList);

	}



	function initLanguageMenu() {
		if ($this->isAdmin) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'sys_language.*',
					'sys_language',
					'sys_language.hidden=0',
					'',
					'sys_language.title'
				);
		} else {
			$exQ = t3lib_BEfunc::deleteClause('pages_language_overlay');
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'sys_language.*',
					'pages_language_overlay,sys_language',
					'pages_language_overlay.sys_language_uid=sys_language.uid AND pages_language_overlay.pid IN ('.$this->pidList.')'.$exQ,
					'pages_language_overlay.sys_language_uid,sys_language.uid,sys_language.pid,sys_language.tstamp,sys_language.hidden,sys_language.title,sys_language.static_lang_isocode,sys_language.flag',
					'sys_language.title'
				);
		}

		while(($lrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)))	{
			if ($GLOBALS['BE_USER']->checkLanguageAccess($lrow['uid']))	{
				$this->MOD_MENU['language'][$lrow['uid']]=($lrow['hidden']?'('.$lrow['title'].')':$lrow['title']);
			}
		}

			// Setting alternative default label:
		$dl = trim($this->TSprop['defaultLanguageLabel']);
		if ($dl && isset($this->MOD_MENU['language'][0]))	{
			$this->MOD_MENU['language'][0] = $dl;
		}
	}



	function initCategories() {
		if (!$this->isAdmin) {
				// get include/exclude items
			if (($excludeList = $GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.excludeList'))) {
				$this->excludeCats = $this->posIntExplode($excludeList);
			}

			$this->includeCats = tx_ttnews_div::getIncludeCatArray();
			$this->catlistWhere = tx_ttnews_div::getCatlistWhere();
		}
	}


	function posIntExplode($list) {
		$arr = t3lib_div::intExplode(',',$list);
		$out = array();
		foreach ($arr as $v) {
			if ($v > 0) {
				$out[] = $v;
			}
		}
		return $out;
	}




	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function initSubCategories() {
		if ($this->useSubCategories && $this->category) {
			$subcats = tx_ttnews_div::getSubCategories($this->category);
			$this->selectedCategories = t3lib_div::uniqueList($this->category.($subcats?','.$subcats:''));
		} else {
			$this->selectedCategories = $this->category;
		}
	}






	/**
	 * Checks if a PID value is accessible and if so returns the path for the page.
	 * Processing is cached so many calls to the function are OK.
	 *
	 * @param	integer		Page id for check
	 * @return	string		Page path of PID if accessible. otherwise zero.
	 */
	function getPageInfoForOverview($pid)	{
		$out = array();
		$pageinfo = t3lib_BEfunc::readPageAccess($pid,$this->perms_clause);
		$out['path'] = $pageinfo['_thePath'];

		$calcPerms = $GLOBALS['BE_USER']->calcPerms($pageinfo);
		if (($calcPerms&16)) {
			$out['edit'] = TRUE;
		}
		return $out;
	}

	function checkPageAccess($pid) {
		if (!isset($this->permsCache[$pid])) {
			$pageinfo = t3lib_BEfunc::readPageAccess($pid,$this->perms_clause);
			$calcPerms = $GLOBALS['BE_USER']->calcPerms($pageinfo);
			if (($calcPerms&16)) {
				$this->permsCache[$pid] = TRUE;
			} else {
				$this->permsCache[$pid] = FALSE;
			}
		}
		return $this->permsCache[$pid];
	}


	function initPermsCache() {
		if (!$this->isAdmin) {
			 $this->pidChash = md5($this->pidList);
			 $pc = $GLOBALS['BE_USER']->getSessionData('permsCache');
			 if (is_array($pc) && is_array($pc[$this->pidChash])) {
	 			$this->permsCache = $pc[$this->pidChash];
		 	}
		}
	}


	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getLinkParams() {
		$params = array('id' => $this->id);
		if ($this->category) {
			$params['category'] = $this->category;
		}
		return $params;
	}

	/**
	 * @return tx_ttnews_compatibility
	 */
	protected function compatibility() {
		return tx_ttnews_compatibility::getInstance();
	}
}







	/**
	 * [Describe function...]
	 *
	 */
class tx_ttnewscatmanager_treeView extends tx_ttnews_categorytree {

	var $TCEforms_itemFormElName='';
	var $TCEforms_nonSelectableItemsArray=array();

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$icon: ...
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function wrapIcon($icon,&$row)	{
		$theIcon = $this->addTagAttributes($icon, $this->titleAttrib.'="'.$this->getTitleAttrib($row).'"');

		if($row['uid']>0 && !isset($row['doktype'])) { // no clickmenu for pages
			$theIcon = $GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon($theIcon,'tt_news_cat_CM',$row['uid'],0,'&bank='.$this->bank);
			$theIcon = '<span class="dragIcon" id="dragIconID_'.$row['uid'].'">'.$theIcon.'</span>';
		} else {
			$theIcon = '<span class="dragIcon" id="dragIconID_0">'.$theIcon.'</span>';
		}
		return $theIcon;
	}

	/**
	 * wraps the record titles in the tree with links or not depending on if they are in the TCEforms_nonSelectableItemsArray.
	 *
	 * @param	string		$title: the title
	 * @param	array		$v: an array with uid and title of the current item.
	 * @return	string		the wrapped title
	 */
	function wrapTitle($title,$v)	{

		// TODO: language overlay


		if($v['uid']>0) {
			$hrefTitle = htmlentities('[id='.$v['uid'].'] '.$v['description']);
			$js = htmlspecialchars('txttnewsM1js.loadList(\''.$v['uid'].'\', $(\'ttnewslist\'), \''.intval($this->pageID).'\');');
			$out =  '<a href="#" onclick="'.$js.'" title="'.$hrefTitle.'">'.$title.'</a>';

			// Wrap title in a drag/drop span.
			$out = '<span class="dragTitle" id="dragTitleID_'.$v['uid'].'">'.$out.'</span>';
			if ($this->showEditIcons) {
				$out .= $this->makeControl('tt_news_cat',$v);
			}
		} else {
			if ($this->storagePid != $this->pageID) {
				$grsp = ' GRSP';
			}
			if ($this->useStoragePid) {
				$pidLbl = sprintf($GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang_tca.xml:tt_news.treeSelect.pageTitleSuffix'),$this->storagePid.$grsp);
			} else {
				$pidLbl = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang_tca.xml:tt_news.treeSelect.pageTitleSuffixNoGrsp');

			}
			$pidLbl = ' <span class="typo3-dimmed"><em>'.$pidLbl.'</em></span>';
			$hrefTitle = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/mod1/locallang.xml:showAllResetSel');

			$out = '<span class="dragTitle" id="dragTitleID_0">
						<a href="'.t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT').'?id='.$this->pageID.'" title="'.$hrefTitle.'">'.$title.'</a>
					</span>'.$pidLbl;
		}
		return $out;
	}

	/**
	 * Creates the control panel for a single record in the listing.
	 *
	 * @param	string		The table
	 * @param	array		The record for which to make the control panel.
	 * @return	string		HTML table with the control panel (unless disabled)
	 */
	function makeControl($table,$row)	{
		global $TCA, $LANG;

			// Initialize:
		t3lib_div::loadTCA($table);
		$cells = array();
			// "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
		if ($this->mayUserEditCategories)	{
			$params='&edit['.$table.']['.$row['uid'].']=edit';
			$cells[]='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$this->backPath,$this->returnUrl)).'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/edit2'.(!$TCA[$table]['ctrl']['readOnly']?'':'_d').'.gif',
						'width="11" height="12"').' title="'.$LANG->getLLL('edit',$this->LL).'" alt="" />'.
					'</a>';
		}

// 			// "Info": (All records)
// 		$cells[]='<a href="#" onclick="'.htmlspecialchars('top.launchView(\''.$table.'\', \''.$row['uid'].'\'); return false;').'">'.
// 				'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/zoom2.gif','width="12" height="12"').' title="'.$LANG->getLLL('showInfo',$this->LL).'" alt="" />'.
// 				'</a>';

			// "Hide/Unhide" links:
		$hiddenField = $TCA[$table]['ctrl']['enablecolumns']['disabled'];
		if ($this->mayUserEditCategories && $hiddenField && $TCA[$table]['columns'][$hiddenField] &&
				(!$TCA[$table]['columns'][$hiddenField]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields',$table.':'.$hiddenField)))	{
			if ($row[$hiddenField])	{
				$params='&data['.$table.']['.$row['uid'].']['.$hiddenField.']=0';
				$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$this->issueCommand($params,$this->returnUrl).'\');').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_unhide.gif',
							'width="11" height="10"').' title="'.$LANG->getLLL('unHide',$this->LL).'" alt="" />'.
						'</a>';
			} else {
				$params='&data['.$table.']['.$row['uid'].']['.$hiddenField.']=1';
				$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$this->issueCommand($params,$this->returnUrl).'\');').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_hide.gif',
							'width="11" height="10"').' title="'.$LANG->getLLL('hide',$this->LL).'" alt="" />'.
						'</a>';
			}
		}




		return '
				<!-- CONTROL PANEL: '.$table.':'.$row['uid'].' -->
				<span style="padding:0 0 0 7px;">'.implode('',$cells).'</span>';
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$params: ...
	 * @param	[type]		$rUrl: ...
	 * @return	[type]		...
	 */
	function issueCommand($params,$rUrl='')	{
		$rUrl = $rUrl ? $rUrl : t3lib_div::getIndpEnv('REQUEST_URI');
		return $this->backPath.'tce_db.php?'.
				$params.
				'&redirect='.($rUrl==-1?"'+T3_THIS_LOCATION+'":rawurlencode($rUrl)).
				'&vC='.rawurlencode($GLOBALS['BE_USER']->veriCode()).
				'&prErr=1&uPT=1';
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/mod1/index.php']);
}

if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	$SOBE = t3lib_div::makeInstance('tx_ttnews_module1');
	$SOBE->init();
	foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

	$SOBE->main();
	$SOBE->printContent();
}

?>