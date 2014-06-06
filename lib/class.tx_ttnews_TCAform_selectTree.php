<?php
/***************************************************************
*  Copyright notice
*
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
 * This function displays a selector with nested categories.
 * The original code is borrowed from the extension "Digital Asset Management" (tx_dam) author: René Fritz <r.fritz@colorcube.de>
 *
 * $Id$
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
 *   79: class tx_ttnews_TCAform_selectTree
 *   86:     function init(&$PA)
 *  105:     function setDefVals()
 *  135:     function renderCategoryFields(&$PA, &$fobj)
 *  341:     function setSelectedItems()
 *  373:     function registerRequiredProperty(&$fobj, $type, $name, $value)
 *  393:     function registerNestedElement(&$fobj, $itemName)
 *  412:     function printError($NACats,$row=array())
 *  430:     function printMsg($msgLbl, $sev)
 *  449:     function ajaxExpandCollapse($params, &$ajaxObj)
 *  508:     function renderCatTree($calledFromAjax=false)
 *  668:     function getStoragePageIcon(&$treeObj)
 *  695:     function getCategoryFolders($where)
 *  722:     function getCatRootline ($SPaddWhere)
 *  757:     function getNotSelectableItems($SPaddWhere,$allowedItemsList=false)
 *  795:     function getNotAllowedItems($SPaddWhere)
 *
 *
 *  830: class tx_ttnews_tceforms_categorytree extends tx_ttnews_categorytree
 *  842:     function wrapTitle($title,$v)
 *  873:     function getTitleStyles($v, &$hrefTitle)
 *  902:     function PMiconATagWrap($icon, $cmd, $isExpand = true)
 *
 * TOTAL FUNCTIONS: 18
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
require_once(t3lib_extMgm::extPath('tt_news').'lib/class.tx_ttnews_categorytree.php');
require_once(t3lib_extMgm::extPath('tt_news').'lib/class.tx_ttnews_div.php');
	/**
	 * this class displays a tree selector with nested tt_news categories.
	 *
	 */
class tx_ttnews_TCAform_selectTree {
	var $divObj;
	var $selectedItems = array();
	var $confArr = array();
	var $PA = array();
	var $useAjax = FALSE;

	function init(&$PA) {
		$this->confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);



		$this->PA = &$PA;
		$this->table = $PA['table'];
		$this->field = $PA['field'];
		$this->row = $PA['row'];
		$this->fieldConfig = $PA['fieldConf']['config'];
		$this->setDefVals();
		$this->setSelectedItems();
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function setDefVals() {
		if (!is_int($this->row['uid'])) { // defVals only for new records
			$defVals = t3lib_div::_GP('defVals');

			if (is_array($defVals) && $defVals[$this->table][$this->field]) {
				$defCat = intval($defVals[$this->table][$this->field]);
				/**
				 * TODO:
				 * check for allowed categories
				 */
				if ($defCat) {
					$row = t3lib_BEfunc::getRecord('tt_news_cat', $defCat);
					$title = t3lib_BEfunc::getRecordTitle($this->table,$row);

					$this->PA['itemFormElValue'] = $defCat.'|'.$title;
					$this->row['category'] = $this->PA['itemFormElValue'];
				}
			}
		}
	}


	/**
	 * Generation of TCEform elements of the type "select"
	 * This will render a selector box element, or possibly a special construction with two selector boxes. That depends on configuration.
	 *
	 * @param	array		$PA: the parameter array for the current field
	 * @param	object		$fobj: Reference to the parent object
	 * @return	string		the HTML code for the field
	 */
	function renderCategoryFields(&$PA, &$fobj)    {

		$this->intT3ver = $this->compatibility()->int_from_ver(TYPO3_version);
		if ($this->intT3ver < 4001000) {
			// load some additional styles for the BE trees in TYPO3 version lower that 4.1
			// expand/collapse is disabled

			$fobj->additionalCode_pre[] = '
				<link rel="stylesheet" type="text/css" href="'.t3lib_extMgm::extRelPath('tt_news').'compat/tree_styles_for_4.0.css" />';

		} else { // enable ajax expand/collapse for TYPO3 versions > 4.1
			if ($this->intT3ver >= 4002000) {
				$jsFile = 'js/tceformsCategoryTree.js';
			} else {
				$jsFile = 'compat/tceformsCategoryTree_for_4.1.js';
			}
			$this->useAjax = TRUE;
			$fobj->additionalCode_pre[] = '
				<script src="'.t3lib_extMgm::extRelPath('tt_news').$jsFile.'" type="text/javascript"></script>';

		}



		$this->init($PA);

		$table = $this->table;
		$field = $this->field;
		$row = $this->row;
		$this->recID = $row['uid'];
		$itemFormElName = $this->PA['itemFormElName'];

			// it seems TCE has a bug and do not work correctly with '1'
		$this->fieldConfig['maxitems'] = ($this->fieldConfig['maxitems']==2) ? 1 : $this->fieldConfig['maxitems'];

			// Getting the selector box items from the system
		$selItems = $fobj->addSelectOptionsToItemArray($fobj->initItemArray($this->PA['fieldConf']),$this->PA['fieldConf'],$fobj->setTSconfig($table,$row),$field);
		$selItems = $fobj->addItems($selItems,$this->PA['fieldTSConfig']['addItems.']);

			// Possibly remove some items:
		$removeItems=t3lib_div::trimExplode(',',$this->PA['fieldTSConfig']['removeItems'],1);

		foreach($selItems as $tk => $p)	{
			if (in_array($p[1],$removeItems))	{
				unset($selItems[$tk]);
			}
		}

			// Creating the label for the "No Matching Value" entry.
		if (isset($this->PA['fieldTSConfig']['noMatchingValue_label'])) {
			$nMV_label = $GLOBALS['LANG']->sL($this->PA['fieldTSConfig']['noMatchingValue_label']);
		} else {
			$nMV_label = '[ '.$fobj->getLL('l_noMatchingValue').' ]';
		}
		$nMV_label = @sprintf($nMV_label, $this->PA['itemFormElValue']);

					// Set max and min items:
		$maxitems = $this->compatibility()->intInRange($this->fieldConfig['maxitems'],0);
		if (!$maxitems)	$maxitems = 1000;
		$minitems = $this->compatibility()->intInRange($this->fieldConfig['minitems'],0);




		if ($this->fieldConfig['treeView'])	{
			// the current record is a translation of another record
			if ($row['sys_language_uid'] && $row['l18n_parent'] && ($table == 'tt_news' || $table == 'tt_news_cat')) {
				$categories = array();
				$NACats = array();
				$na = false;

				// get categories of the translation original
				$catres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
								'tt_news_cat.uid, tt_news_cat.title, tt_news_cat.deleted',
								'tt_news_cat, tt_news_cat_mm',
								'tt_news_cat_mm.uid_foreign=tt_news_cat.uid AND tt_news_cat.deleted=0 AND tt_news_cat_mm.uid_local='.$row['l18n_parent']);

				$assignedCategories = array();
				while (($catrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($catres))) {
					$assignedCategories[$catrow['uid']] = $catrow['title'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($catres);

				$treeIDs = tx_ttnews_div::getAllowedTreeIDs();

				foreach ($assignedCategories as $cuid => $ctitle) {
					if (!in_array($cuid,$treeIDs)) {
						$categories[$cuid] = $NACats[] = '<p style="padding:0px;color:red;font-weight:bold;">- '.$ctitle.' <span class="typo3-dimmed"><em>['.$cuid.']</em></span></p>';
						$na = true;
					} else {
						$categories[$cuid] = '<p style="padding:0px;">- '.$ctitle.' <span class="typo3-dimmed"><em>['.$cuid.']</em></span></p>';
					}
				}

				if ($na) {
					$this->NA_Items = $this->printError($NACats,$row);
				}
				$item = implode($categories,chr(10));



				if ($item) {
					$item = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang_tca.xml:tt_news.treeSelect.translOrgCat').'<br />'.$item;
				} else {
					$item = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang_tca.xml:tt_news.treeSelect.translOrgNoCat').'<br />';
				}
				$item = '<div class="typo3-TCEforms-originalLanguageValue">'.$item.'</div>';

			} else { // build tree selector

				if ($table == 'tt_news' && $this->intT3ver >= 4001000) {
					$this->registerRequiredProperty($fobj,'range', $itemFormElName, array($minitems,$maxitems,'imgName'=>$table.'_'.$row['uid'].'_'.$field));
				}
				$item.= '<input type="hidden" name="'.$itemFormElName.'_mul" value="'.($this->fieldConfig['multiple']?1:0).'" />';

				if ($this->fieldConfig['treeView'] AND $this->fieldConfig['foreign_table']) {
						// get default items
					$defItems = array();
					if (is_array($this->fieldConfig['items']) && $this->table == 'tt_content' && $this->row['CType']=='list' && $this->row['list_type']==9 && $this->field == 'pi_flexform')	{
						reset ($this->fieldConfig['items']);
						while (list(,$itemValue) = each($this->fieldConfig['items']))	{
							if ($itemValue[0]) {
								$ITitle = $GLOBALS['LANG']->sL($itemValue[0]);
								$data = 'data['.$this->table.']['.$this->row['uid'].']['.$this->field.'][data][sDEF][lDEF][categorySelection][vDEF]';
								$onclick = ' onclick="setFormValueFromBrowseWin(\''.$data.'\','.$itemValue[1].',\''.$ITitle.'\'); return false;"';
								$defItems[] = '<a href="#"'.$onclick.' style="text-decoration:none;">'.$ITitle.'</a>';
							}
						}
					}

					// render the tree
					$treeContent = '<span id="tt_news_cat_tree">'.$this->renderCatTree().'<span>';

					if ($defItems[0]) { // add default items to the tree table. In this case the value: "[not categorized]"
//						$this->treeItemC += count($defItems);
						$treeContent .= '<table border="0" cellpadding="0" cellspacing="0"><tr>
							<td>'.$GLOBALS['LANG']->sL($this->fieldConfig['itemsHeader']).'&nbsp;</td><td>'.implode($defItems,'<br />').'</td>
							</tr></table>';
					}
//					$errorMsg = array();

					$width = 350; // default width for the field with the category tree
					if (intval($this->confArr['categoryTreeWidth'])) { // if a value is set in extConf take this one.
						$width = $this->compatibility()->intInRange($this->confArr['categoryTreeWidth'],1,600);
					}

					$divStyle = 'position:relative; left:0px; top:0px; width:'.$width.'px; border:solid 1px #999;background:#fff;margin-bottom:5px;padding: 0 10px 10px 0;';
					$thumbnails = '<div  name="'.$itemFormElName.'_selTree" id="tree-div" style="'.htmlspecialchars($divStyle).'">';
					$thumbnails .= $treeContent;
					$thumbnails .= '</div>';
				}

					// Perform modification of the selected items array:
				$itemArray = t3lib_div::trimExplode(',',$this->PA['itemFormElValue'],1);
				foreach($itemArray as $tk => $tv) {
					$tvP = explode('|',$tv,2);
					$evalValue = rawurldecode($tvP[0]);
					if (in_array($evalValue,$removeItems) && !$this->PA['fieldTSConfig']['disableNoMatchingValueElement'])	{
						$tvP[1] = rawurlencode($nMV_label);
					} else {
						$tvP[1] = rawurldecode($tvP[1]);
					}
					$itemArray[$tk]=implode('|',$tvP);
				}
				$sWidth = 200; // default width for the left field of the category select
				if (intval($this->confArr['categorySelectedWidth'])) {
					$sWidth = $this->compatibility()->intInRange($this->confArr['categorySelectedWidth'],1,600);
				}
				$params = array(
					'autoSizeMax' => $this->fieldConfig['autoSizeMax'],
					'style' => ' style="width:'.$sWidth.'px;"',
					'dontShowMoveIcons' => ($maxitems<=1),
					'maxitems' => $maxitems,
					'info' => '',
					'headers' => array(
						'selector' => $fobj->getLL('l_selected').':<br />',
						'items' => $fobj->getLL('l_items').':<br />'
					),
					'noBrowser' => 1,
					'thumbnails' => $thumbnails
				);
				$item.= $fobj->dbFileIcons($itemFormElName,'','',$itemArray,'',$params,$this->PA['onFocus']);
				// Wizards:
				$altItem = '<input type="hidden" name="'.$itemFormElName.'" value="'.htmlspecialchars($this->PA['itemFormElValue']).'" />';
				$item = $fobj->renderWizards(array($item,$altItem),$this->fieldConfig['wizards'],$table,$row,$field,$this->PA,$itemFormElName,array());
			}
		}
		if (($table == 'tt_news' || $table == 'tt_news_cat') && $this->NA_Items && $this->intT3ver >= 4001000) {
			$this->registerRequiredProperty(
					$fobj,
					'range',
					'data['.$table.']['.$row['uid'].'][noDisallowedCategories]',
					array(1,1,'imgName'=>$table.'_'.$row['uid'].'_noDisallowedCategories'));
			$item .= '<input type="hidden" name="data['.$table.']['.$row['uid'].'][noDisallowedCategories]" value="'.($this->NA_Items?'':'1').'" />';

		}
		return $this->NA_Items.$item;
	}




	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function setSelectedItems() {
		if ($this->table == 'tt_content') {
			if ($this->row['pi_flexform']) {
				$cfgArr = t3lib_div::xml2array($this->row['pi_flexform']);
				if (is_array($cfgArr) && is_array($cfgArr['data']['sDEF']['lDEF']) && is_array($cfgArr['data']['sDEF']['lDEF']['categorySelection'])) {
					$selectedCategories = $cfgArr['data']['sDEF']['lDEF']['categorySelection']['vDEF'];
				}
			}
		} else {
			$selectedCategories = $this->row[$this->field];
		}

		if ($selectedCategories) {
			$selvals = explode(',',$selectedCategories);
			if (is_array($selvals)) {
				foreach ($selvals as $vv) {
					$cuid = explode('|',$vv);
					$this->selectedItems[] = $cuid[0];
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$fobj: ...
	 * @param	[type]		$type: ...
	 * @param	[type]		$name: ...
	 * @param	[type]		$value: ...
	 * @return	[type]		...
	 */
	function registerRequiredProperty(&$fobj, $type, $name, $value) {
		if ($type == 'field' && is_string($value)) {
			$fobj->requiredFields[$name] = $value;
				// requiredFields have name/value swapped! For backward compatibility we keep this:
			$itemName = $value;
		} elseif ($type == 'range' && is_array($value)) {
			$fobj->requiredElements[$name] = $value;
			$itemName = $name;
		}
			// Set the situation of nesting for the current field:
		$this->registerNestedElement($fobj,$itemName);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$fobj: ...
	 * @param	[type]		$itemName: ...
	 * @return	[type]		...
	 */
	function registerNestedElement(&$fobj, $itemName) {
		$dynNestedStack = $fobj->getDynNestedStack();
		$match = array();
		if (count($dynNestedStack) && preg_match('/^(.+\])\[(\w+)\]$/', $itemName, $match)) {
			array_shift($match);
			$fobj->requiredNested[$itemName] = array(
				'parts' => $match,
				'level' => $dynNestedStack,
			);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$NACats: ...
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function printError($NACats,$row=array()) {

		$msgHeader = 'SAVING DISABLED!!';
		$msgBody = ($row['l18n_parent'] && $row['sys_language_uid'] ? 'The translation original of this' : 'This') .
					' record has the following categories assigned that are not defined in your BE usergroup: ' .
					urldecode(implode($NACats, chr(10)));

		if ($this->compatibility()->int_from_ver(TYPO3_version) < 4003000) {
			$msg = '
				<div style="padding:15px 15px 20px 0;">
					<div class="typo3-message message-warning">
						<div class="message-header">' . $msgHeader . '</div>
						<div class="message-body">' . $msgBody . '</div>
					</div>
				</div>';

				// add flashmessages styles to older TYPO3 versions
			$cssPath = $GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('tt_news');
			$msg = '<link rel="stylesheet" type="text/css" href="' . $cssPath . 'compat/flashmessages.css" media="screen" />' . $msg;
		} else {
				// in TYPO3 4.3 or higher we use flashmessages to display the message
			$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$msgBody,
						$msgHeader,
						t3lib_FlashMessage::WARNING
				);
			t3lib_FlashMessageQueue::addMessage($flashMessage);

			$inlineFlashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$msgBody,
						'',
						t3lib_FlashMessage::WARNING
				);

			$msg = $inlineFlashMessage->render();

		}
		return $msg;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$msgLbl: ...
	 * @param	[type]		$sev: ...
	 * @return	[type]		...
	 */
	function printMsg($msgLbl, $sev) {

		$content = '<div style="padding:10px;">
			<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/icon_'.$sev.'.gif','width="18" height="16"').' title="" alt="" />';
		$content .= $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang_tca.xml:tt_news.treeSelect.msg_'.$msgLbl);
		$content .= '</div>';

		return $content;
	}



	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$params: ...
	 * @param	[type]		$ajaxObj: ...
	 * @return	[type]		...
	 */
	function ajaxExpandCollapse($params, &$ajaxObj) {
		$this->useAjax = TRUE;
		$this->confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);


		$this->table = trim(t3lib_div::_GP('tceFormsTable'));
		$this->storagePidFromAjax = intval(t3lib_div::_GP('storagePid'));
		$this->recID = trim(t3lib_div::_GP('recID')); // no intval() here because it might be a new record
		if (intval($this->recID) == $this->recID) {
			$this->row = t3lib_BEfunc::getRecord($this->table,$this->recID);
		}

		// set selected items
		if ($this->table == 'tt_news') {
			$this->field = 'category';
			if (is_array($this->row) && $this->row['pid']) {
				$cRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_foreign', 'tt_news_cat_mm', 'uid_local='.intval($this->recID));
				while (($cRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($cRes))) {
					$this->selectedItems[] = $cRow['uid_foreign'];
				}
			}
		} else {
			if ($this->table == 'tt_news_cat') {
				$this->field = 'parent_category';
			} elseif ($this->table == 'tt_content') {
				$this->field = 'pi_flexform';
			} else { // be_users or be_groups
				$this->field = 'tt_news_categorymounts';
			}
			if (is_array($this->row)) {
				$this->setSelectedItems($this->row['uid']);
			}

		}

		if ($this->table == 'tt_content') {
			$this->PA['itemFormElName'] = 'data[tt_content]['.$this->recID.'][pi_flexform][data][sDEF][lDEF][categorySelection][vDEF]';
		} else {
			$this->PA['itemFormElName'] = 'data['.$this->table.']['.$this->recID.']['.$this->field.']';
		}

		$tree = $this->renderCatTree(TRUE);

		if (!$this->treeObj_ajaxStatus) {
			$ajaxObj->setError($tree);
		} else	{
			$ajaxObj->addContent('tree', $tree);
		}
	}




	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$cmd: ...
	 * @return	[type]		...
	 */
	function renderCatTree($calledFromAjax=false) {

// 		$tStart = microtime(true);
// 		$this->debug['start'] = time();

		if (substr($this->table,0,3) != 'be_') {
		// ignore useStoragePid if table is be_user/groups.
			if ($this->confArr['useStoragePid']) {
				if ($this->storagePidFromAjax) {
					$this->storagePid = $this->storagePidFromAjax;
				} else {
					$TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig($this->table,$this->row);
					$this->storagePid = ($TSconfig['_STORAGE_PID']?$TSconfig['_STORAGE_PID']:intval($this->row['pid']));
				}
				$SPaddWhere = ' AND tt_news_cat.pid IN (' . $this->storagePid . ')';
				if ($this->table == 'tt_news_cat' && intval($this->row['pid']) > 0 && $this->row['pid'] != $this->storagePid) {
					$msg = $this->printMsg('notInGRSP','warning2');
					$notInGRSP = true;
				}
			}

			$catlistWhere = tx_ttnews_div::getCatlistWhere();
			if ($catlistWhere) {
				$this->getNotAllowedItems($SPaddWhere);
			}

		}



		$treeOrderBy = $this->confArr['treeOrderBy']?$this->confArr['treeOrderBy']:'uid';

		// instantiate tree object
		$treeViewObj = t3lib_div::makeInstance('tx_ttnews_tceforms_categorytree');

		$treeViewObj->treeName = $this->table.'_tree';
		$treeViewObj->table = 'tt_news_cat';
		$treeViewObj->tceFormsTable = $this->table;
		$treeViewObj->tceFormsRecID = $this->recID;
		$treeViewObj->storagePid = $this->storagePid;
		$treeViewObj->useStoragePid = $this->confArr['useStoragePid'];


		$treeViewObj->init($SPaddWhere.$catlistWhere,$treeOrderBy);
		$treeViewObj->backPath = $GLOBALS['BACK_PATH'];
		$treeViewObj->thisScript = 'class.tx_ttnews_tceformsSelectTree.php';
		$treeViewObj->fieldArray = array('uid','title','description','hidden','starttime','endtime','fe_group'); // those fields will be filled to the array $treeViewObj->tree
		$treeViewObj->parentField = 'parent_category';
		$treeViewObj->expandable = $this->useAjax;
		$treeViewObj->expandAll = !$this->useAjax;

		$treeViewObj->useAjax = $this->useAjax;
		$treeViewObj->titleLen = 60;
		$treeViewObj->disableAll = $notInGRSP;
		$treeViewObj->ext_IconMode = '1'; // no context menu on icons
		$treeViewObj->title = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang_tca.xml:tt_news.treeSelect.treeTitle');



		$treeViewObj->TCEforms_itemFormElName = $this->PA['itemFormElName'];

		if ($this->table=='tt_news_cat') {
			$treeViewObj->TCEforms_nonSelectableItemsArray[] = $this->row['uid'];
		}


		/**
		 * FIXME
		 * making categories not-selectable with tt_newsPerms.tt_news_cat.allowedItems doesn't work anymore
		 */
//		if (tx_ttnews_div::useAllowedCategories() && !tx_ttnews_div::allowedItemsFromTreeSelector) {
//			// 'options.useListOfAllowedItems' is set but no category is selected --> check the 'allowedItems' list
//			$notAllowedItems = $this->getNotAllowedItems($SPaddWhere);
//		}
//		if (is_array($notAllowedItems) && $notAllowedItems[0]) {
//			foreach ($notAllowedItems as $k) {
//				$treeViewObj->TCEforms_nonSelectableItemsArray[] = $k;
//			}
//		}



		$sPageIcon = '';

		// mark selected categories
		$treeViewObj->TCEforms_selectedItemsArray = $this->selectedItems;
		$treeViewObj->selectedItemsArrayParents = $this->getCatRootline($SPaddWhere);

		if (substr($this->table,0,3) == 'be_') {
			// if table is 'be_users' or 'be_groups' group the categories by folder. 'useStoragePid' is ignored
 			$cf = $this->getCategoryFolders($SPaddWhere.$catlistWhere);

 			/**
 			 * FIXME:
 			 * currently 'expandFirst' is required to prevent js errors when expanding/collapsing the tree
 			 * the problems are caused by multiple "root" records with uid=0
 			 */

 			$treeViewObj->expandFirst = 1;
 			$treeViewObj->MOUNTS = $cf;
 			$groupByPages = TRUE;

		} else {

			if ($this->storagePid > 0) {
				$addWhere = ' AND pid='.$this->storagePid;
			} else { // useStoragePid=0 and table != beusers/groups
				$addWhere = '';

			}
			// get selected categories from be user/group without subcategories
			$tmpsc = tx_ttnews_div::getBeUserCatMounts(FALSE);
			$beUserSelCatArr = t3lib_div::intExplode(',',$tmpsc);
			$includeListArr = tx_ttnews_div::getIncludeCatArray();
			$subcatArr = array_diff($includeListArr,$beUserSelCatArr);

			// get all selected category records from the current storagePid which are not 'root' categories
			// and add them as tree mounts. Subcategories of selected categories will be excluded.

			$cMounts = array();
			$nonRootMounts = FALSE;
			foreach ($beUserSelCatArr as $catID) {
				$tmpR = t3lib_BEfunc::getRecord('tt_news_cat',$catID,'parent_category,hidden',$addWhere);
				if (is_array($tmpR) && !in_array($catID,$subcatArr)) {
					if ($tmpR['parent_category'] > 0) {
						$nonRootMounts = TRUE;
						if (!$calledFromAjax) {
							$sPageIcon = $this->getStoragePageIcon($treeViewObj);
						}
					}
					$cMounts[] = $catID;
				}
			}
			if ($nonRootMounts) {
				$treeViewObj->MOUNTS = $cMounts;
			}
		}


			// render tree html
		$treeContent = $sPageIcon.$treeViewObj->getBrowsableTree($groupByPages);
		$this->treeObj_ajaxStatus = $treeViewObj->ajaxStatus;


//		if (count($treeViewObj->ids) == 0) {
//			$msg .= str_replace('###PID###',$this->storagePid,$this->printMsg('emptyTree','note'));
//			$treeContent = '';
//		}



		return $msg.$treeContent;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$treeObj: ...
	 * @return	[type]		...
	 */
	function getStoragePageIcon(&$treeObj) {
		if ($this->storagePid) {
			$tmpt = $treeObj->table;
			$treeObj->table = 'pages';
			$rootRec = $treeObj->getRecord($this->storagePid);
			$icon = $treeObj->getIcon($rootRec);
			$treeObj->table = $tmpt;
			$pidLbl = sprintf($GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang_tca.xml:tt_news.treeSelect.pageTitleSuffix'),intval($this->storagePid));

		} else {
			$rootRec = $treeObj->getRootRecord($this->storagePid);
			$icon = $treeObj->getRootIcon($rootRec);
			$pidLbl = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang_tca.xml:tt_news.treeSelect.pageTitleSuffixNoGrsp');

		}

		$pidLbl = ' <span class="typo3-dimmed"><em>'.$pidLbl.'</em></span>';
		$content = '<div style="margin: 2px 0 -5px 2px;">'.$icon.$rootRec['title'].$pidLbl.'</div>';
		return $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$where: ...
	 * @return	[type]		...
	 */
	function getCategoryFolders($where) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'pid',
					'tt_news_cat',
					'pid>=0'.$where.t3lib_BEfunc::deleteClause('tt_news_cat'),
					'pid'
				);
		$list = array();
		while(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)))	{
			$list[] = $row['pid'];
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

//		debug($list, ' ('.__CLASS__.'::'.__FUNCTION__.')', __LINE__, __FILE__, 3);
		return $list;
	}




	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$selectedItems: ...
	 * @param	[type]		$SPaddWhere: ...
	 * @return	[type]		...
	 */
	function getCatRootline ($SPaddWhere) {
		$selectedItemsArrayParents = array();
		foreach($this->selectedItems as $v) {
			$uid = $v;
			$loopCheck = 100;
			$catRootline = array();
			while ($uid!=0 && $loopCheck>0)	{
				$loopCheck--;
				$row = t3lib_BEfunc::getRecord('tt_news_cat', $uid, 'parent_category', $SPaddWhere);
				if (is_array($row) && $row['parent_category'] > 0)	{
					$uid = $row['parent_category'];
					$catRootline[] = $uid;
				} else {
					break;
				}
			}
			$selectedItemsArrayParents[$v] = $catRootline;
		}
		return $selectedItemsArrayParents;
	}


	/**
	 * This function checks if there are categories selectable that are not allowed for this BE user and if the current record has
	 * already categories assigned that are not allowed.
	 * If such categories were found they will be returned and "$this->NA_Items" is filled with an error message.
	 * The array "$itemArr" which will be returned contains the list of all non-selectable categories. This array will be added
	 * to "$treeViewObj->TCEforms_nonSelectableItemsArray". If a category is in this array the "select item" link will not be added to it.
	 *
	 * @param	array		$PA: the paramter array
	 * @param	string		$SPaddWhere: this string is added to the query for categories when "useStoragePid" is set.
	 * @param	[type]		$allowedItemsList: ...
	 * @return	array		array with not allowed categories
	 * @see tx_ttnews_tceFunc_selectTreeView::wrapTitle()
	 */
	function getNotSelectableItems($SPaddWhere,$allowedItemsList=false) {
		$fTable = 'tt_news_cat';
			// get list of allowed categories for the current BE user
		if (!$allowedItemsList) {
			$allowedItemsList = $GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.allowedItems');
		}
		$itemArr = array();
		if ($allowedItemsList) {
				// get all categories
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $fTable, '1=1' .$SPaddWhere. ' AND deleted=0');
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				if (!t3lib_div::inList($allowedItemsList,$row['uid'])) { // remove all allowed categories from the category result
					$itemArr[]=$row['uid'];
				}
			}
			if (!$this->row['sys_language_uid'] && !$this->row['l18n_parent']) {
				$catvals = explode(',',$this->row['category']); // get categories from the current record
				$notAllowedCats = array();
				foreach ($catvals as $k) {
					$c = explode('|',$k);
					if($c[0] && !t3lib_div::inList($allowedItemsList,$c[0])) {
						$notAllowedCats[]= '<p style="padding:0px;color:red;font-weight:bold;">- '.$c[1].' <span class="typo3-dimmed"><em>['.$c[0].']</em></span></p>';
					}
				}
				if ($notAllowedCats[0]) {
					$this->NA_Items = $this->printError($notAllowedCats,array());
				}
			}
		}
		return $itemArr;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$SPaddWhere: ...
	 * @return	[type]		...
	 */
	function getNotAllowedItems($SPaddWhere) {
		if ($this->row['category']) {
			$treeIDs = tx_ttnews_div::getAllowedTreeIDs();
			if (!$this->row['sys_language_uid'] && !$this->row['l18n_parent']) {
				$catvals = explode(',',$this->row['category']); // get categories from the current record
				$notAllowedCats = array();

				foreach ($catvals as $k) {
					$c = explode('|',$k);
					if($c[0] && !in_array($c[0],$treeIDs)) {
						$notAllowedCats[]= '<p style="padding:0px;color:red;font-weight:bold;">- '.$c[1].' <span class="typo3-dimmed"><em>['.$c[0].']</em></span></p>';
					}
				}
				if (count($notAllowedCats)) {
					$this->NA_Items = $this->printError($notAllowedCats,array());
				}
			}
		}
	}

	/**
	 * @return tx_ttnews_compatibility
	 */
	protected function compatibility() {
		return tx_ttnews_compatibility::getInstance();
	}
}






	/**
	 * extend class t3lib_treeview to change function wrapTitle().
	 *
	 */
class tx_ttnews_tceforms_categorytree extends tx_ttnews_categorytree {

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
		if ($v['uid'] > 0) {
			$hrefTitle = htmlentities('[id='.$v['uid'].'] '.$v['description']);
			if (in_array($v['uid'],$this->TCEforms_nonSelectableItemsArray) || $this->disableAll) {
				$style = $this->getTitleStyles($v,$hrefTitle);
				return '<a href="#" title="'.$hrefTitle.'"><span style="color:#999;cursor:default;'.$style.'">'.$title.'</span></a>';
			} else {
				$aOnClick = 'setFormValueFromBrowseWin(\''.$this->TCEforms_itemFormElName.'\','.$v['uid'].',\''.t3lib_div::slashJS($title).'\'); return false;';
				$style = $this->getTitleStyles($v,$hrefTitle);
				return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'" title="'.$hrefTitle.'"><span style="'.$style.'">'.$title.'</span></a>';
			}
		} else {
			if ($this->useStoragePid || isset($v['doktype'])) {
				$pid = ($this->storagePid ? $this->storagePid : $v['pid']);
				$pidLbl = sprintf($GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang_tca.xml:tt_news.treeSelect.pageTitleSuffix'),$pid);
			} else {
				$pidLbl = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang_tca.xml:tt_news.treeSelect.pageTitleSuffixNoGrsp');
			}
			$pidLbl = ' <span class="typo3-dimmed"><em>'.$pidLbl.'</em></span>';

			return $title.$pidLbl;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$v: ...
	 * @param	[type]		$hrefTitle: ...
	 * @return	[type]		...
	 */
	function getTitleStyles($v, &$hrefTitle) {
		$style = '';
		if (in_array($v['uid'], $this->TCEforms_selectedItemsArray)) {
			$style .= 'font-weight:bold;';
		}
		$p = false;
		foreach ($this->TCEforms_selectedItemsArray as $selitems) {
			if (is_array($this->selectedItemsArrayParents[$selitems]) && in_array($v['uid'], $this->selectedItemsArrayParents[$selitems])) {
				$p = true;
				break;
			}
		}
		if ($p) {
			$style .= 'text-decoration:underline;background:#ffc;';
			$hrefTitle .= ' (subcategory selected)';
		}

		return $style;
	}

	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param	string		HTML string to wrap, probably an image tag.
	 * @param	string		Command for 'PM' get var
	 * @param	[type]		$isExpand: ...
	 * @return	string		Link-wrapped input string
	 * @access private
	 */
	function PMiconATagWrap($icon, $cmd, $isExpand = true)	{
		if ($this->thisScript && $this->expandable && !$this->disableAll) {

			// activate dynamic ajax-based tree
			$js = htmlspecialchars('tceFormsCategoryTree.load(\''.$cmd.'\', '.intval($isExpand).', this, \''.$this->tceFormsTable.'\', \''.$this->tceFormsRecID.'\', \''.$this->storagePid.'\');');
			return '<a class="pm" onclick="'.$js.'">'.$icon.'</a>';
		} else {
			return $icon;
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_TCAform_selectTree.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_TCAform_selectTree.php']);
}
?>