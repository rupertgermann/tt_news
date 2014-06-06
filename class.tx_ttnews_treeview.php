<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2007 Rupert Germann <rupi@gmx.li>
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
 * This function displays a selector with nested categories.
 * The original code is borrowed from the extension "Digital Asset Management" (tx_dam) author: René Fritz <r.fritz@colorcube.de>
 *
 * $Id: class.tx_ttnews_treeview.php 5071 2007-02-27 23:16:44Z rupertgermann $
 *
 * @author	Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage tt_news
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   66: class tx_ttnews_tceFunc_selectTreeView extends t3lib_treeview
 *   78:     function wrapTitle($title,$v)
 *  101:     function getTitleStyles($v)
 *  123:     function PM_ATagWrap($icon,$cmd,$bMark='')
 *
 *
 *  142: class tx_ttnews_treeview
 *  145:     function displayCategoryTree(&$PA, &$fobj)
 *  207:     function sendResponse($cmd)
 *  255:     function renderCatTree($cmd='')
 *  385:     function getCatRootline ($selectedItems,$SPaddWhere)
 *  422:     function renderCategoryFields()
 *  654:     function getNotAllowedItems(&$PA,$SPaddWhere,$allowedItemsList=false)
 *  697:     function displayTypeFieldCheckCategories(&$PA, &$fobj)
 *
 * TOTAL FUNCTIONS: 10
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_t3lib.'class.t3lib_treeview.php');
require_once(t3lib_extMgm::extPath('tt_news').'class.tx_ttnews_div.php');

	/**
	 * extend class t3lib_treeview to change function wrapTitle().
	 *
	 */
class tx_ttnews_tceFunc_selectTreeView extends t3lib_treeview {

	var $TCEforms_itemFormElName='';
	var $TCEforms_nonSelectableItemsArray=array();

	/**
	 * wraps the record titles in the tree with links or not depending on if they are in the TCEforms_nonSelectableItemsArray.
	 *
	 * @param	string		$title: the title
	 * @param	array		$v: an array with uid and title of the current item.
	 * @return	string		the wrapped title
	 */
	function wrapTitle($title,$v)	{
// 		debug($v);
		if($v['uid']>0) {
			$hrefTitle = htmlentities('[id='.$v['uid'].'] '.$v['description']);
			if (in_array($v['uid'],$this->TCEforms_nonSelectableItemsArray)) {
				$style = $this->getTitleStyles($v);
				return '<a href="#" title="'.$hrefTitle.'"><span style="color:#999;cursor:default;'.$style.'">'.$title.'</span></a>';
			} else {
				$aOnClick = 'setFormValueFromBrowseWin(\''.$this->TCEforms_itemFormElName.'\','.$v['uid'].',\''.t3lib_div::slashJS($title).'\'); return false;';
				$style = $this->getTitleStyles($v);
				return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'" title="'.$hrefTitle.'"><span style="'.$style.'">'.$title.'</span></a>';
			}
		} else {
			return $title;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$v: ...
	 * @return	[type]		...
	 */
	function getTitleStyles($v) {
		$style = '';
		if (in_array($v['uid'], $this->TCEforms_selectedItemsArray)) {
			$style .= 'font-weight:bold;';
		}
		foreach ($this->TCEforms_selectedItemsArray as $k => $selitems) {
			if (is_array($this->selectedItemsArrayParents[$selitems]) && in_array($v['uid'], $this->selectedItemsArrayParents[$selitems])) {
				$style .= 'text-decoration:underline;';
			}
		}
		return $style;
	}

	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param	string		HTML string to wrap, probably an image tag.
	 * @param	string		Command for 'PM' get var
	 * @param	boolean		If set, the link will have a anchor point (=$bMark) and a name attribute (=$bMark)
	 * @return	string		Link-wrapped input string
	 * @access private
	 */
	function PM_ATagWrap($icon,$cmd,$bMark='')	{
		if ($this->useXajax) {
			$cmdParts = explode('_',$cmd);
			$title = 'collapse';
			if ($cmdParts[1] == '1') {
				$title = 'expand';
			}
			return '<span onclick="tx_ttnews_sendResponse(\''.$cmd.'\');" style="cursor:pointer;" title="'.$title.'">'.$icon.'</span>';
		} else {
			return parent::PM_ATagWrap($icon,$cmd,$bMark);
		}
	}

}

	/**
	 * this class displays a tree selector with nested tt_news categories.
	 *
	 */
class tx_ttnews_treeview {
	var $useXajax = false;

	function displayCategoryTree(&$PA, &$fobj) {
		$this->PA = &$PA;
// 		$this->fobj = &$fobj;
		$this->table = $this->PA['table'];
		$this->field = $this->PA['field'];
		$this->row = $this->PA['row'];
		$this->pObj = &$this->PA['pObj'];

		$content = '';
		if (t3lib_extMgm::isLoaded('xajax')) {
			$this->useXajax = TRUE;
		}

/**
FIXME !!!
temporary fix: disable xajax in the category tree for tt_news records in TYPO3 versions below 4.1 when rtehtmlarea is enabled
--> this is needed because xajax collides with rtehtmlarea and prevents rtehtmlarea from loading
*/
// debug($GLOBALS['BE_USER']->uc);
if (t3lib_div::int_from_ver(TYPO3_version) < 4001000 && $this->table == 'tt_news' && !$GLOBALS['BE_USER']->uc['moduleData']['xMOD_alt_doc.php']['disableRTE'] && t3lib_extMgm::isLoaded('rtehtmlarea')) {
	$this->useXajax = false;
}


		if ($this->useXajax) {
			global $TYPO3_CONF_VARS;
			if ($TYPO3_CONF_VARS['BE']['forceCharset']) {
				define ('XAJAX_DEFAULT_CHAR_ENCODING', $TYPO3_CONF_VARS['BE']['forceCharset']);
			} else {
				define ('XAJAX_DEFAULT_CHAR_ENCODING', 'iso-8859-15');
			}

			require_once (t3lib_extMgm::extPath('xajax') . 'class.tx_xajax.php');
			$this->xajax = t3lib_div::makeInstance('tx_xajax');
			$this->xajax->setWrapperPrefix('tx_ttnews_');
			$this->xajax->registerFunction(array('sendResponse',&$this,'sendResponse'));
// 			$fobj->additionalCode_pre['tt_news_xajax'] = $this->xajax->getJavascript('../'.t3lib_extMgm::siteRelPath('xajax'));
			$content .= $this->xajax->getJavascript('../'.t3lib_extMgm::siteRelPath('xajax'));

			$this->xajax->processRequests();
		}

// 		debug($fobj->additionalCode_pre);


		if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']) { // get tt_news extConf array
			$this->confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);
		}
		if (!is_object($this->divObj)) {
			$this->divObj = t3lib_div::makeInstance('tx_ttnews_div');
		}
		$content .= $this->renderCategoryFields();
		return $content;

	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$cmd: ...
	 * @return	[type]		...
	 */
	function sendResponse($cmd) 	{
		if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']) { // get tt_news extConf array
			$this->confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);
		}
		if (!is_object($this->divObj)) {
			$this->divObj = t3lib_div::makeInstance('tx_ttnews_div');
		}
		$objResponse = new tx_xajax_response();

		$this->debug = array();
// 		if ($cmd == 'show') {
// 			$showhideLink = '<span onclick="tx_ttnews_sendResponse(\'hide\');" style="cursor:pointer;">hide all</span>';
// 		} else {
// 			$showhideLink = '<span onclick="tx_ttnews_sendResponse(\'show\');" style="cursor:pointer;">show all</span>';
// 		}
		if ($cmd == 'show' || $cmd == 'hide') {
			$content = $this->renderCatTree($cmd);
		} else {
			t3lib_div::_GETset($cmd,'PM');
			$content = $this->renderCatTree();
		}

// 		$content .= '<div id="debug-tree">debug</div>';
		$objResponse->addAssign('tt_news_cat_tree', 'innerHTML', $content);

// 		$this->debug['treeItemC'] = $this->treeItemC;
// 		$objResponse->addAssign('debug-tree', 'innerHTML', t3lib_div::view_array($this->debug));

		$config = $this->PA['fieldConf']['config'];
		$size = intval($config['size']);
		$config['autoSizeMax'] = t3lib_div::intInRange($config['autoSizeMax'],0);
		$height = $config['autoSizeMax'] ? t3lib_div::intInRange($this->treeItemC+2,t3lib_div::intInRange($size,1),$config['autoSizeMax']) : $size;
		// hardcoded: 16 is the height of the icons
		$height=$height*16;
		$objResponse->addAssign('tree-div', 'style.height', $height.'px;');

// 		$objResponse->addAssign('showHide', 'innerHTML', $showhideLink);

		//return the XML response
		return $objResponse->getXML();
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$cmd: ...
	 * @return	[type]		...
	 */
	function renderCatTree($cmd='')    {

// 		$tStart = microtime(true);
// 		$this->debug['start'] = time();



		$config = $this->PA['fieldConf']['config'];
		if ($this->confArr['useStoragePid'] && ($this->table == 'tt_news' || $this->table == 'tt_news_cat' || $this->table == 'tt_content')) { // ignore the value of "useStoragePid" if table is be_users or be_groups
			$TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig($this->table,$this->row);
			$this->storagePid = $TSconfig['_STORAGE_PID']?$TSconfig['_STORAGE_PID']:0;
			$SPaddWhere = ' AND tt_news_cat.pid IN (' . $this->storagePid . ')';
		}

		if (!is_object($treeViewObj)) {
			$treeViewObj = t3lib_div::makeInstance('tx_ttnews_tceFunc_selectTreeView');
		}

		if ($this->table == 'tt_news' || $this->table == 'tt_news_cat') {
				// get include/exclude items
			$this->excludeList = $GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.excludeList');
			$this->includeList = $GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.includeList');
			$catmounts = $this->divObj->getAllowedCategories();
			if ($catmounts) {
				$this->includeList = $catmounts;
			}
		}




		if ($this->divObj->useAllowedCategories() && !$this->divObj->allowedItemsFromTreeSelector) {
			$notAllowedItems = $this->getNotAllowedItems($this->PA,$SPaddWhere);
		}

		if ($this->excludeList) {
			$catlistWhere = ' AND tt_news_cat.uid NOT IN ('.implode(t3lib_div::intExplode(',',$this->excludeList),',').')';
		}
		$treeOrderBy = $this->confArr['treeOrderBy']?$this->confArr['treeOrderBy']:'uid';

		$treeViewObj->treeName = $this->table.'_tree';
		$treeViewObj->table = $config['foreign_table'];
		$treeViewObj->init($SPaddWhere.$catlistWhere,$treeOrderBy);
		$treeViewObj->backPath = $this->pObj->backPath;
		$treeViewObj->parentField = $GLOBALS['TCA'][$config['foreign_table']]['ctrl']['treeParentField'];
		$treeViewObj->expandAll = ($this->useXajax?($cmd == 'show'?1:0):1);
		$treeViewObj->expandFirst = ($this->useXajax?0:1);
		$treeViewObj->fieldArray = array('uid','title','description','hidden','starttime','endtime','fe_group'); // those fields will be filled to the array $treeViewObj->tree
		$treeViewObj->useXajax = $this->useXajax;


		if ($this->includeList) {
			$treeViewObj->MOUNTS = t3lib_div::intExplode(',',$this->includeList);
		}

		$treeViewObj->ext_IconMode = '1'; // no context menu on icons
		$treeViewObj->title = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$config['foreign_table']]['ctrl']['title']);

		$treeViewObj->TCEforms_itemFormElName = $this->PA['itemFormElName'];
		if ($this->table==$config['foreign_table']) {
			$treeViewObj->TCEforms_nonSelectableItemsArray[] = $this->row['uid'];
		}

		if (is_array($notAllowedItems) && $notAllowedItems[0]) {
			foreach ($notAllowedItems as $k) {
				$treeViewObj->TCEforms_nonSelectableItemsArray[] = $k;
			}
		}
		// mark selected categories
		$selectedItems = array();
		if ($this->row['tt_news_categorymounts']) { //  be_users and be_groups
			$selectedCategories = $this->row['tt_news_categorymounts'];
		} elseif ($this->row['category']) { // tt_news
			$selectedCategories = $this->row['category'];
		} elseif ($this->row['pi_flexform']) { // tt_content
			$cfgArr = t3lib_div::xml2array($this->row['pi_flexform']);
			if (is_array($cfgArr) && is_array($cfgArr['data']['sDEF']['lDEF']) && is_array($cfgArr['data']['sDEF']['lDEF']['categorySelection'])) {
				$selectedCategories = $cfgArr['data']['sDEF']['lDEF']['categorySelection']['vDEF'];
			}
		} else { // tt_news_cat
			$selectedCategories = $this->row['parent_category'];
		}

		if ($selectedCategories) {
			$selvals = explode(',',$selectedCategories);
			if (is_array($selvals)) {
				foreach ($selvals as $kk => $vv) {
					$cuid = explode('|',$vv);
					$selectedItems[] = $cuid[0];
				}
			}
		}
		$treeViewObj->TCEforms_selectedItemsArray = $selectedItems;
		$treeViewObj->selectedItemsArrayParents = $this->getCatRootline($selectedItems,$SPaddWhere);

// debug($treeViewObj->selectedItemsArrayParents);
// debug($selectedItems);

		if (!$this->divObj->allowedItemsFromTreeSelector) {
			$notAllowedItems = $this->getNotAllowedItems($this->PA,$SPaddWhere);
		} else {
			$treeIDs = $this->divObj->getCategoryTreeIDs();
			$notAllowedItems = $this->getNotAllowedItems($this->PA,$SPaddWhere,$treeIDs);
		}
			// render tree html
		$treeContent = $treeViewObj->getBrowsableTree();

		$this->treeItemC = count($treeViewObj->ids);
// 		if ($cmd == 'show' || $cmd == 'hide') {
// 			$this->treeItemC++;
// 		}
		$this->treeIDs = $treeViewObj->ids;

// $this->debug['MOUNTS'] = $treeViewObj->MOUNTS;

// 		$tEnd = microtime(true);
// 		$this->debug['end'] = time();
//
// 		$exectime = $tEnd-$tStart;
// 		$this->debug['exectime'] = $exectime;
		return $treeContent;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$selectedItems: ...
	 * @param	[type]		$SPaddWhere: ...
	 * @return	[type]		...
	 */
	function getCatRootline ($selectedItems,$SPaddWhere) {
		$selectedItemsArrayParents = array();
		foreach($selectedItems as $k => $v) {
			$uid = $v;
			$loopCheck = 100;
			$catRootline = array();
			while ($uid!=0 && $loopCheck>0)	{
				$loopCheck--;
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'parent_category',
					'tt_news_cat',
					'uid='.intval($uid).$SPaddWhere.' AND deleted=0');

				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$uid = $row['parent_category'];
					if ($row['parent_category']) {
						$catRootline[] = $row['parent_category'];
					}
				} else {
					break;
				}
			}
			$selectedItemsArrayParents[$v] = $catRootline;
		}
		return $selectedItemsArrayParents;
	}



	/**
	 * Generation of TCEform elements of the type "select"
	 * This will render a selector box element, or possibly a special construction with two selector boxes. That depends on configuration.
	 *
	 * @param	array		$PA: the parameter array for the current field
	 * @param	object		$fobj: Reference to the parent object
	 * @return	string		the HTML code for the field
	 */
	function renderCategoryFields()    {
		$PA = &$this->PA;
// 		$fobj = &$this->fobj;

		$table = $PA['table'];
		$field = $PA['field'];
		$row = $PA['row'];


			// Field configuration from TCA:
		$config = $PA['fieldConf']['config'];
			// it seems TCE has a bug and do not work correctly with '1'
		$config['maxitems'] = ($config['maxitems']==2) ? 1 : $config['maxitems'];

			// Getting the selector box items from the system
		$selItems = $this->pObj->addSelectOptionsToItemArray($this->pObj->initItemArray($PA['fieldConf']),$PA['fieldConf'],$this->pObj->setTSconfig($table,$row),$field);
		$selItems = $this->pObj->addItems($selItems,$PA['fieldTSConfig']['addItems.']);
		#if ($config['itemsProcFunc']) $selItems = $this->pObj->procItems($selItems,$PA['fieldTSConfig']['itemsProcFunc.'],$config,$table,$row,$field);


			// Possibly remove some items:
		$removeItems=t3lib_div::trimExplode(',',$PA['fieldTSConfig']['removeItems'],1);


		foreach($selItems as $tk => $p)	{
			if (in_array($p[1],$removeItems))	{
				unset($selItems[$tk]);
			} elseif (isset($PA['fieldTSConfig']['altLabels.'][$p[1]])) {
				$selItems[$tk][0]=$this->pObj->sL($PA['fieldTSConfig']['altLabels.'][$p[1]]);
			}

				// Removing doktypes with no access:
			if ($table.'.'.$field == 'pages.doktype')	{
				if (!($GLOBALS['BE_USER']->isAdmin() || t3lib_div::inList($GLOBALS['BE_USER']->groupData['pagetypes_select'],$p[1])))	{
					unset($selItems[$tk]);
				}
			}
		}

			// Creating the label for the "No Matching Value" entry.
		$nMV_label = isset($PA['fieldTSConfig']['noMatchingValue_label']) ? $this->pObj->sL($PA['fieldTSConfig']['noMatchingValue_label']) : '[ '.$this->pObj->getLL('l_noMatchingValue').' ]';
		$nMV_label = @sprintf($nMV_label, $PA['itemFormElValue']);



			// Prepare some values:
		$maxitems = intval($config['maxitems']);
		$minitems = intval($config['minitems']);
		$size = intval($config['size']);
			// If a SINGLE selector box...
		if ($maxitems<=1 AND !$config['treeView'])	{

		} else {
			if ($row['sys_language_uid'] && $row['l18n_parent'] && ($table == 'tt_news' || $table == 'tt_news_cat')) { // the current record is a translation of another record
// 				if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']) { // get tt_news extConf array
// 					$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);
// 				}
				if ($this->confArr['useStoragePid']) {
					$TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig($table,$row);
					$storagePid = $TSconfig['_STORAGE_PID']?$TSconfig['_STORAGE_PID']:0;
					$SPaddWhere = ' AND tt_news_cat.pid IN (' . $storagePid . ')';
				}
				$errorMsg = array();
				$notAllowedItems = array();
				if ($this->divObj->useAllowedCategories()) {
					$notAllowedItems = $this->getNotAllowedItems($PA,$SPaddWhere);
				}
					// get categories of the translation original
				$catres = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query ('tt_news_cat.uid,tt_news_cat.title,tt_news_cat_mm.sorting AS mmsorting', 'tt_news', 'tt_news_cat_mm', 'tt_news_cat', ' AND tt_news_cat_mm.uid_local='.$row['l18n_parent'].$SPaddWhere,'', 'mmsorting');
				$categories = array();
				$NACats = array();
				$na = false;
				while ($catrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($catres)) {
					if(in_array($catrow['uid'],$notAllowedItems)) {
						$categories[$catrow['uid']] = $NACats[] = '<p style="padding:0px;color:red;font-weight:bold;">- '.$catrow['title'].' <span class="typo3-dimmed"><em>['.$catrow['uid'].']</em></span></p>';
						$na = true;
					} else {
						$categories[$catrow['uid']] = '<p style="padding:0px;">- '.$catrow['title'].' <span class="typo3-dimmed"><em>['.$catrow['uid'].']</em></span></p>';
					}
				}
				if($na) {
					$this->NA_Items = '<table class="warningbox" border="0" cellpadding="0" cellspacing="0"><tbody><tr><td><img src="gfx/icon_fatalerror.gif" class="absmiddle" alt="" height="16" width="18">SAVING DISABLED!! <br />'.($row['l18n_parent']&&$row['sys_language_uid']?'The translation original of this':'This').' record has the following categories assigned that are not defined in your BE usergroup: '.implode($NACats,chr(10)).'</td></tr></tbody></table>';
				}
				$item = implode($categories,chr(10));

				if ($item) {
					$item = 'Categories from the translation original of this record:<br />'.$item;
				} else {
					$item = 'The translation original of this record has no categories assigned.<br />';
				}
				$item = '<div class="typo3-TCEforms-originalLanguageValue">'.$item.'</div>';

/** ******************************
       build tree selector
/** *****************************/

			} else { // build tree selector
				$item.= '<input type="hidden" name="'.$PA['itemFormElName'].'_mul" value="'.($config['multiple']?1:0).'" />';

					// Set max and min items:
				$maxitems = t3lib_div::intInRange($config['maxitems'],0);
				if (!$maxitems)	$maxitems=100000;
				$minitems = t3lib_div::intInRange($config['minitems'],0);

					// Register the required number of elements:
				$this->pObj->requiredElements[$PA['itemFormElName']] = array($minitems,$maxitems,'imgName'=>$table.'_'.$row['uid'].'_'.$field);


				if($config['treeView'] AND $config['foreign_table']) {
						// get default items
					$defItems = array();
					if (is_array($config['items']) && $this->table == 'tt_content' && $this->row['CType']=='list' && $this->row['list_type']==9 && $this->field == 'pi_flexform')	{
						reset ($config['items']);
						while (list($itemName,$itemValue) = each($config['items']))	{
							if ($itemValue[0]) {
								$ITitle = $GLOBALS['LANG']->sL($itemValue[0]);
								$defItems[] = '<a href="#" onclick="setFormValueFromBrowseWin(\'data['.$this->table.']['.$this->row['uid'].']['.$this->field.'][data][sDEF][lDEF][categorySelection][vDEF]\','.$itemValue[1].',\''.$ITitle.'\'); return false;" style="text-decoration:none;">'.$ITitle.'</a>';
							}
						}
					}
					$treeContent = '<span id="tt_news_cat_tree">'.$this->renderCatTree().'<span>';

					if ($defItems[0]) { // add default items to the tree table. In this case the value [not categorized]
						$this->treeItemC += count($defItems);
						$treeContent .= '<table border="0" cellpadding="0" cellspacing="0"><tr>
							<td>'.$GLOBALS['LANG']->sL($config['itemsHeader']).'&nbsp;</td><td>'.implode($defItems,'<br />').'</td>
							</tr></table>';
					}

// 					$showHideAll = '<span id="showHide"><span onclick="tx_ttnews_sendResponse(\'show\');" style="cursor:pointer;">show all</span></span>';
// 					$treeContent = $showHideAll.$treeContent;
// 					$this->treeItemC++;


						// find recursive categories or "storagePid" related errors and if there are some, add a message to the $errorMsg array.
// 					$errorMsg = $this->findRecursiveCategories($PA,$row,$table,$this->storagePid,$this->treeIDs) ;
					$errorMsg = array();

					$width = 280; // default width for the field with the category tree
					if (intval($confArr['categoryTreeWidth'])) { // if a value is set in extConf take this one.
						$width = t3lib_div::intInRange($confArr['categoryTreeWidth'],1,600);
					} elseif ($GLOBALS['CLIENT']['BROWSER']=='msie') { // to suppress the unneeded horizontal scrollbar IE needs a width of at least 320px
						$width = 320;
					}

					$config['autoSizeMax'] = t3lib_div::intInRange($config['autoSizeMax'],0);
					$height = $config['autoSizeMax'] ? t3lib_div::intInRange($this->treeItemC+2,t3lib_div::intInRange($size,1),$config['autoSizeMax']) : $size;
						// hardcoded: 16 is the height of the icons
					$height=$height*16;

					$divStyle = 'position:relative; left:0px; top:0px; height:'.$height.'px; width:'.$width.'px;border:solid 1px;overflow:auto;background:#fff;margin-bottom:5px;';
					$thumbnails='<div  name="'.$PA['itemFormElName'].'_selTree" id="tree-div" style="'.htmlspecialchars($divStyle).'">';
					$thumbnails.=$treeContent;
					$thumbnails.='</div>';

				} else {

					$sOnChange = 'setFormValueFromBrowseWin(\''.$PA['itemFormElName'].'\',this.options[this.selectedIndex].value,this.options[this.selectedIndex].text); '.implode('',$PA['fieldChangeFunc']);

						// Put together the select form with selected elements:
					$selector_itemListStyle = isset($config['itemListStyle']) ? ' style="'.htmlspecialchars($config['itemListStyle']).'"' : ' style="'.$this->pObj->defaultMultipleSelectorStyle.'"';
					$itemArray = array();
					$size = $config['autoSizeMax'] ? t3lib_div::intInRange(count($itemArray)+1,t3lib_div::intInRange($size,1),$config['autoSizeMax']) : $size;
					$thumbnails = '<select style="width:150px;" name="'.$PA['itemFormElName'].'_sel"'.$this->pObj->insertDefStyle('select').($size?' size="'.$size.'"':'').' onchange="'.htmlspecialchars($sOnChange).'"'.$PA['onFocus'].$selector_itemListStyle.'>';
					#$thumbnails = '<select                       name="'.$PA['itemFormElName'].'_sel"'.$this->pObj->insertDefStyle('select').($size?' size="'.$size.'"':'').' onchange="'.htmlspecialchars($sOnChange).'"'.$PA['onFocus'].$selector_itemListStyle.'>';
					foreach($selItems as $p)	{
						$thumbnails.= '<option value="'.htmlspecialchars($p[1]).'">'.htmlspecialchars($p[0]).'</option>';
					}
					$thumbnails.= '</select>';

				}

					// Perform modification of the selected items array:
				$itemArray = t3lib_div::trimExplode(',',$PA['itemFormElValue'],1);
				foreach($itemArray as $tk => $tv) {
					$tvP = explode('|',$tv,2);
					$evalValue = rawurldecode($tvP[0]);
					if (in_array($evalValue,$removeItems) && !$PA['fieldTSConfig']['disableNoMatchingValueElement'])	{
						$tvP[1] = rawurlencode($nMV_label);
// 					} elseif (isset($PA['fieldTSConfig']['altLabels.'][$evalValue])) {
// 						$tvP[1] = rawurlencode($this->pObj->sL($PA['fieldTSConfig']['altLabels.'][$evalValue]));
					} else {
						$tvP[1] = rawurldecode($tvP[1]);
					}
					$itemArray[$tk]=implode('|',$tvP);
				}
				$sWidth = 150; // default width for the left field of the category select
				if (intval($confArr['categorySelectedWidth'])) {
					$sWidth = t3lib_div::intInRange($confArr['categorySelectedWidth'],1,600);
				}
				$params=array(
					'size' => $size,
					'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'],0),
					#'style' => isset($config['selectedListStyle']) ? ' style="'.htmlspecialchars($config['selectedListStyle']).'"' : ' style="'.$this->pObj->defaultMultipleSelectorStyle.'"',
					'style' => ' style="width:'.$sWidth.'px;"',
					'dontShowMoveIcons' => ($maxitems<=1),
					'maxitems' => $maxitems,
					'info' => '',
					'headers' => array(
						'selector' => $this->pObj->getLL('l_selected').':<br />',
						'items' => $this->pObj->getLL('l_items').':<br />'
					),
					'noBrowser' => 1,
					'thumbnails' => $thumbnails
				);
				$item.= $this->pObj->dbFileIcons($PA['itemFormElName'],'','',$itemArray,'',$params,$PA['onFocus']);
				// Wizards:
				$altItem = '<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';
				$item = $this->pObj->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'],array());
			}
		}





		return $this->NA_Items.implode($errorMsg,chr(10)).$item;

	}

	/**
	 * This function checks if there are categories selectable that are not allowed for this BE user and if the current record has
	 * already categories assigned that are not allowed.
	 * If such categories were found they will be returned and "$this->NA_Items" is filled with an error message.
	 * The array "$itemArr" which will be returned contains the list of all non-selectable categories. This array will be added to "$treeViewObj->TCEforms_nonSelectableItemsArray". If a category is in this array the "select item" link will not be added to it.
	 *
	 * @param	array		$PA: the paramter array
	 * @param	string		$SPaddWhere: this string is added to the query for categories when "useStoragePid" is set.
	 * @param	[type]		$allowedItemsList: ...
	 * @return	array		array with not allowed categories
	 * @see tx_ttnews_tceFunc_selectTreeView::wrapTitle()
	 */
	function getNotAllowedItems(&$PA,$SPaddWhere,$allowedItemsList=false) {
		$fTable = $PA['fieldConf']['config']['foreign_table'];
			// get list of allowed categories for the current BE user
		if (!$allowedItemsList) {
			$allowedItemsList=$GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.'.$fTable.'.allowedItems');
		}

		$itemArr = array();
		if ($allowedItemsList) {
				// get all categories
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $fTable, '1=1' .$SPaddWhere. ' AND deleted=0');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if(!t3lib_div::inList($allowedItemsList,$row['uid'])) { // remove all allowed categories from the category result
					$itemArr[]=$row['uid'];
				}
			}
			if (!$PA['row']['sys_language_uid'] && !$PA['row']['l18n_parent']) {
				$catvals = explode(',',$PA['row']['category']); // get categories from the current record
// 				debug($catvals,__FUNCTION__);
				$notAllowedCats = array();
				foreach ($catvals as $k) {
					$c = explode('|',$k);
					if($c[0] && !t3lib_div::inList($allowedItemsList,$c[0])) {
						$notAllowedCats[]= '<p style="padding:0px;color:red;font-weight:bold;">- '.$c[1].' <span class="typo3-dimmed"><em>['.$c[0].']</em></span></p>';
					}
				}
				if ($notAllowedCats[0]) {
					$this->NA_Items = '<table class="warningbox" border="0" cellpadding="0" cellspacing="0"><tbody><tr><td><img src="gfx/icon_fatalerror.gif" class="absmiddle" alt="" height="16" width="18">SAVING DISABLED!! <br />This record has the following categories assigned that are not defined in your BE usergroup: '.urldecode(implode($notAllowedCats,chr(10))).'</td></tr></tbody></table>';
				}
			}
		}
		return $itemArr;
	}


	/**
	 * This functions displays the title field of a news record and checks if the record has categories assigned that are not allowed for the current BE user.
	 * If there are non allowed categories an error message will be displayed.
	 *
	 * @param	array		$PA: the parameter array for the current field
	 * @param	object		$fobj: Reference to the parent object
	 * @return	string		the HTML code for the field and the error message
	 */
	function displayTypeFieldCheckCategories(&$PA, &$fobj)    {
		$table = $PA['table'];
		$field = $PA['field'];
		$row = $PA['row'];

		if (!is_object($this->divObj)) {
			$this->divObj = t3lib_div::makeInstance('tx_ttnews_div');
		}

		if ($this->divObj->useAllowedCategories()) {
			$notAllowedItems = array();
			if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']) { // get tt_news extConf array
				$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);
			}
			if ($confArr['useStoragePid']) {
				$TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig($table,$row);
				$storagePid = $TSconfig['_STORAGE_PID']?$TSconfig['_STORAGE_PID']:0;
				$SPaddWhere = ' AND tt_news_cat.pid IN (' . $storagePid . ')';
			}

			if (!$this->divObj->allowedItemsFromTreeSelector) {
				$notAllowedItems = $this->getNotAllowedItems($PA,$SPaddWhere);
			} else {
				$treeIDs = $this->divObj->getCategoryTreeIDs();
				$notAllowedItems = $this->getNotAllowedItems($PA,$SPaddWhere,$treeIDs);
			}

			if ($notAllowedItems[0]) {
				$uidField = $row['l18n_parent']&&$row['sys_language_uid']?$row['l18n_parent']:$row['uid'];

				if ($uidField) {
					// get categories of the record in db
					$catres = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query ('tt_news_cat.uid,tt_news_cat.title,tt_news_cat_mm.sorting AS mmsorting', 'tt_news', 'tt_news_cat_mm', 'tt_news_cat', ' AND tt_news_cat_mm.uid_local='.$uidField.$SPaddWhere,'', 'mmsorting');
					$NACats = array();
					if ($catres) {
						while ($catrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($catres)) {
							if($catrow['uid'] && $notAllowedItems[0] && in_array($catrow['uid'],$notAllowedItems)) {

								$NACats[]= '<p style="padding:0px;color:red;font-weight:bold;">- '.$catrow['title'].' <span class="typo3-dimmed"><em>['.$catrow['uid'].']</em></span></p>';
							}
						}
					}

					if($NACats[0]) {
						$NA_Items =  '<table class="warningbox" border="0" cellpadding="0" cellspacing="0"><tbody><tr><td><img src="gfx/icon_fatalerror.gif" class="absmiddle" alt="" height="16" width="18">SAVING DISABLED!! <br />'.($row['l18n_parent']&&$row['sys_language_uid']?'The translation original of this':'This').' record has the following categories assigned that are not defined in your BE usergroup: '.implode($NACats,chr(10)).'</td></tr></tbody></table>';
					}
				}
			}
		}
			// unset foreign table to prevent adding of categories to the "type" field
		$PA['fieldConf']['config']['foreign_table'] = '';
		$PA['fieldConf']['config']['foreign_table_where'] = '';
		if (!$row['l18n_parent'] && !$row['sys_language_uid']) { // render "type" field only for records in the default language
			$fieldHTML = $fobj->getSingleField_typeSelect($table,$field,$row,$PA);
		}

		return $NA_Items.$fieldHTML;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.tx_ttnews_treeview.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.tx_ttnews_treeview.php']);
}
?>
