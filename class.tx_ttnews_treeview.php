<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Rupert Germann <rupi@gmx.li>
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
 * @author	Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage tt_news
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   58: class tx_ttnews_tceFunc_selectTreeView extends t3lib_treeview
 *   70:     function wrapTitle($title,$v)
 *
 *
 *   89: class tx_ttnews_treeview
 *   99:     function displayCategoryTree($PA, $fobj)
 *  353:     function getNotAllowedItems($PA,$SPaddWhere)
 *  397:     function findRecursiveCategories ($PA,$row,$table,$storagePid,$treeIds)
 *  438:     function compareCategoryVals ($treeIds,$catString)
 *  466:     function displayTypeFieldCheckCategories($PA, $fobj)
 *
 * TOTAL FUNCTIONS: 6
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_t3lib.'class.t3lib_treeview.php');
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
		if($v['uid']>0) {
			if (in_array($v['uid'],$this->TCEforms_nonSelectableItemsArray)) {
				return '<a href="#" title="'.$v['description'].'"><span style="color:#999;cursor:default;">'.$title.'</span></a>';
			} else {
				$hrefTitle = $v['description'];
				$aOnClick = 'setFormValueFromBrowseWin(\''.$this->TCEforms_itemFormElName.'\','.$v['uid'].',\''.$title.'\'); return false;';
				return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'" title="'.htmlentities($v['description']).'">'.$title.'</a>';
			}
		} else {
			return $title;
		}
	}
}

	/**
	 * this class displays a tree selector with nested tt_news categories.
	 *
	 */
class tx_ttnews_treeview {

	/**
	 * Generation of TCEform elements of the type "select"
	 * This will render a selector box element, or possibly a special construction with two selector boxes. That depends on configuration.
	 *
	 * @param	array		$PA: the parameter array for the current field
	 * @param	object		$fobj: Reference to the parent object
	 * @return	string		the HTML code for the field
	 */
	function displayCategoryTree($PA, $fobj)    {

		$table = $PA['table'];
		$field = $PA['field'];
		$row = $PA['row'];

		$this->pObj = &$PA['pObj'];

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
			} else if (isset($PA['fieldTSConfig']['altLabels.'][$p[1]])) {
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
			if ($row['sys_language_uid'] && $row['l18n_parent'] ) { // the current record is a translation of another record
				if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']) { // get tt_news extConf array
					$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);
				}
				if ($confArr['useStoragePid']) {
					$TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig($table,$row);
					$storagePid = $TSconfig['_STORAGE_PID']?$TSconfig['_STORAGE_PID']:0;
					$SPaddWhere = ' AND tt_news_cat.pid IN (' . $storagePid . ')';
				}
				$errorMsg = array();
				$notAllowedItems = array();
				if ($GLOBALS['BE_USER']->getTSConfigVal('options.useListOfAllowedItems') && !$GLOBALS['BE_USER']->isAdmin()) {
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
			} else { // build tree selector
				$item.= '<input type="hidden" name="'.$PA['itemFormElName'].'_mul" value="'.($config['multiple']?1:0).'" />';

					// Set max and min items:
				$maxitems = t3lib_div::intInRange($config['maxitems'],0);
				if (!$maxitems)	$maxitems=100000;
				$minitems = t3lib_div::intInRange($config['minitems'],0);

					// Register the required number of elements:
				$this->pObj->requiredElements[$PA['itemFormElName']] = array($minitems,$maxitems,'imgName'=>$table.'_'.$row['uid'].'_'.$field);


				if($config['treeView'] AND $config['foreign_table']) {
					global $TCA, $LANG;

					if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']) { // get tt_news extConf array
						$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);
					}
					if ($confArr['useStoragePid']) {
						$TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig($table,$row);
						$storagePid = $TSconfig['_STORAGE_PID']?$TSconfig['_STORAGE_PID']:0;
						$SPaddWhere = ' AND tt_news_cat.pid IN (' . $storagePid . ')';

					}
					if ($GLOBALS['BE_USER']->getTSConfigVal('options.useListOfAllowedItems') && !$GLOBALS['BE_USER']->isAdmin()) {
						$notAllowedItems = $this->getNotAllowedItems($PA,$SPaddWhere);
					}

					if($config['treeViewClass'] AND is_object($treeViewObj = &t3lib_div::getUserObj($config['treeViewClass'],'user_',false)))      {
					} else {
						$treeViewObj = t3lib_div::makeInstance('tx_ttnews_tceFunc_selectTreeView');
					}
					$treeViewObj->table = $config['foreign_table'];
					$treeViewObj->init($SPaddWhere);
					$treeViewObj->backPath = $this->pObj->backPath;
					$treeViewObj->parentField = $TCA[$config['foreign_table']]['ctrl']['treeParentField'];
					$treeViewObj->expandAll = 1;
					$treeViewObj->expandFirst = 1;
					$treeViewObj->fieldArray = array('uid','title','description'); // those fields will be filled to the array $treeViewObj->tree

					$treeViewObj->ext_IconMode = '1'; // no context menu on icons
					$treeViewObj->title = $LANG->sL($TCA[$config['foreign_table']]['ctrl']['title']);

					$treeViewObj->TCEforms_itemFormElName = $PA['itemFormElName'];
					if ($table==$config['foreign_table']) {
						$treeViewObj->TCEforms_nonSelectableItemsArray[] = $row['uid'];
					}

					if (is_array($notAllowedItems) && $notAllowedItems[0]) {
						foreach ($notAllowedItems as $k) {
							$treeViewObj->TCEforms_nonSelectableItemsArray[] = $k;
						}
					}

						// get default items
					$defItems = array();
					if (is_array($config['items']) && $table == 'tt_content' && $row['CType']=='list' && $row['list_type']==9 && $field == 'pi_flexform')	{
						reset ($config['items']);
						while (list($itemName,$itemValue) = each($config['items']))	{
							if ($itemValue[0]) {
								$ITitle = $this->pObj->sL($itemValue[0]);
								$defItems[] = '<a href="#" onclick="setFormValueFromBrowseWin(\'data['.$table.']['.$row['uid'].']['.$field.'][data][sDEF][lDEF][categorySelection][vDEF]\','.$itemValue[1].',\''.$ITitle.'\'); return false;" style="text-decoration:none;">'.$ITitle.'</a>';
							}
						}
					}
						// render tree html
					$treeContent=$treeViewObj->getBrowsableTree();
					$treeItemC = count($treeViewObj->ids);

					if ($defItems[0]) { // add default items to the tree table. In this case the value [not categorized]
						$treeItemC += count($defItems);
						$treeContent .= '<table border="0" cellpadding="0" cellspacing="0"><tr>
							<td>'.$this->pObj->sL($config['itemsHeader']).'&nbsp;</td><td>'.implode($defItems,'<br />').'</td>
							</tr></table>';
					}

						// find recursive categories or "storagePid" related errors and if there are some, add a message to the $errorMsg array.
					$errorMsg = $this->findRecursiveCategories($PA,$row,$table,$storagePid,$treeViewObj->ids) ;

					$width = 280; // default width for the field with the category tree
					if (intval($confArr['categoryTreeWidth'])) { // if a value is set in extConf take this one.
						$width = t3lib_div::intInRange($confArr['categoryTreeWidth'],1,600);
					} elseif ($GLOBALS['CLIENT']['BROWSER']=='msie') { // to suppress the unneeded horizontal scrollbar IE needs a width of at least 320px
						$width = 320;
					}

					$config['autoSizeMax'] = t3lib_div::intInRange($config['autoSizeMax'],0);
					$height = $config['autoSizeMax'] ? t3lib_div::intInRange($treeItemC+2,t3lib_div::intInRange($size,1),$config['autoSizeMax']) : $size;
						// hardcoded: 16 is the height of the icons
					$height=$height*16;

					$divStyle = 'position:relative; left:0px; top:0px; height:'.$height.'px; width:'.$width.'px;border:solid 1px;overflow:auto;background:#fff;margin-bottom:5px;';
					$thumbnails='<div  name="'.$PA['itemFormElName'].'_selTree" style="'.htmlspecialchars($divStyle).'">';
					$thumbnails.=$treeContent;
					$thumbnails.='</div>';

				} else {

					$sOnChange = 'setFormValueFromBrowseWin(\''.$PA['itemFormElName'].'\',this.options[this.selectedIndex].value,this.options[this.selectedIndex].text); '.implode('',$PA['fieldChangeFunc']);

						// Put together the select form with selected elements:
					$selector_itemListStyle = isset($config['itemListStyle']) ? ' style="'.htmlspecialchars($config['itemListStyle']).'"' : ' style="'.$this->pObj->defaultMultipleSelectorStyle.'"';
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
					if (in_array($tvP[0],$removeItems) && !$PA['fieldTSConfig']['disableNoMatchingValueElement'])	{
						$tvP[1] = rawurlencode($nMV_label);
					} elseif (isset($PA['fieldTSConfig']['altLabels.'][$tvP[0]])) {
						$tvP[1] = rawurlencode($this->pObj->sL($PA['fieldTSConfig']['altLabels.'][$tvP[0]]));
					} else {
						$tvP[1] = rawurlencode($this->pObj->sL(rawurldecode($tvP[1])));
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
				$item = $this->pObj->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'],$specConf);
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
	 * @return	array		array with not allowed categories
	 * @see tx_ttnews_tceFunc_selectTreeView::wrapTitle()
	 */
	function getNotAllowedItems($PA,$SPaddWhere) {
		$fTable = $PA['fieldConf']['config']['foreign_table'];
			// get list of allowed categories for the current BE user
		$allowedItemsList=$GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.'.$fTable.'.allowedItems');

		$itemArr = array();
		if ($allowedItemsList) {
				// get all categories
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $fTable, '1=1' .$SPaddWhere. ' AND NOT deleted');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if(!t3lib_div::inList($allowedItemsList,$row['uid'])) { // remove all allowed categories from the category result
					$itemArr[]=$row['uid'];
				}
			}
			if (!$PA['row']['sys_language_uid'] && !$PA['row']['l18n_parent']) {
				$catvals = explode(',',$PA['row']['category']); // get categories from the current record
				$notAllowedCats = array();
				foreach ($catvals as $k) {
					$c = explode('|',$k);
					if($c[0] && !t3lib_div::inList($allowedItemsList,$c[0])) {
						$notAllowedCats[]= '<p style="padding:0px;color:red;font-weight:bold;">- '.$c[1].' <span class="typo3-dimmed"><em>['.$c[0].']</em></span></p>';
					}
				}
				if ($notAllowedCats[0]) {
					$this->NA_Items = '<table class="warningbox" border="0" cellpadding="0" cellspacing="0"><tbody><tr><td><img src="gfx/icon_fatalerror.gif" class="absmiddle" alt="" height="16" width="18">SAVING DISABLED!! <br />This record has the following categories assigned that are not defined in your BE usergroup: '.implode($notAllowedCats,chr(10)).'</td></tr></tbody></table>';
				}
			}
		}


		return $itemArr;

	}

	/**
	 * detects recursive categories and returns an error message if recursive categories where found
	 *
	 * @param	array		$PA: the paramter array
	 * @param	array		$row: the current row
	 * @param	array		$table: current table
	 * @param	integer		$storagePid: the StoragePid (pid of the category folder)
	 * @param	array		$treeIds: array with the ids of the categories in the tree
	 * @return	array		error messages
	 */
	function findRecursiveCategories ($PA,$row,$table,$storagePid,$treeIds) {
		$errorMsg = array();
		if ($table == 'tt_content' && $row['CType']=='list' && $row['list_type']==9) { // = tt_content element which inserts plugin tt_news
			$cfgArr = t3lib_div::xml2array($row['pi_flexform']);
			if (is_array($cfgArr) && is_array($cfgArr['data']['sDEF']['lDEF']) && $cfgArr['data']['sDEF']['lDEF']['categorySelection']) {
				$rcList = $this->compareCategoryVals ($treeIds,$cfgArr['data']['sDEF']['lDEF']['categorySelection']['vDEF']);
			}
		} elseif ($table == 'tt_news_cat' || $table == 'tt_news') {
			if ($table == 'tt_news_cat' && $row['pid'] == $storagePid && intval($row['uid']) && !in_array($row['uid'],$treeIds))	{ // if the selected category is not empty and not in the array of tree-uids it seems to be part of a chain of recursive categories
				$recursionMsg = 'RECURSIVE CATEGORIES DETECTED!! <br />This record is part of a chain of recursive categories. The affected categories will not be displayed in the category tree.  You should remove the parent category of this record to prevent this.';
			}
			if ($table == 'tt_news' && $row['category']) { // find recursive categories in the tt_news db-record
				$rcList = $this->compareCategoryVals ($treeIds,$row['category']);
			}
			// in case of localized records this doesn't work
			if ($storagePid && $row['pid'] != $storagePid && $table == 'tt_news_cat') { // if a storagePid is defined but the current category is not stored in storagePid
				$errorMsg[] = '<p style="padding:10px;"><img src="gfx/icon_warning.gif" class="absmiddle" alt="" height="16" width="18"><strong style="color:red;"> Warning:</strong><br />tt_news is configured to display categories only from the "General record storage page" (GRSP). The current category is not located in the GRSP and will so not be displayed. To solve this you should either define a GRSP or disable "Use StoragePid" in the extension manager.</p>';
			}
		}
		if (strlen($rcList)) {
			$recursionMsg = 'RECURSIVE CATEGORIES DETECTED!! <br />This record has the following recursive categories assigned: '.$rcList.'<br />Recursive categories will not be shown in the category tree and will therefore not be selectable. ';

			if ($table == 'tt_news') {
				$recursionMsg .= 'To solve this problem mark these categories in the left select field, click on "edit category" and clear the field "parent category" of the recursive category.';
			} else {
				$recursionMsg .= 'To solve this problem you should clear the field "parent category" of the recursive category.';
			}
		}
		if ($recursionMsg) $errorMsg[] = '<table class="warningbox" border="0" cellpadding="0" cellspacing="0"><tbody><tr><td><img src="gfx/icon_fatalerror.gif" class="absmiddle" alt="" height="16" width="18">'.$recursionMsg.'</td></tr></tbody></table>';
		return $errorMsg;
	}

	/**
	 * This function compares the selected categories ($catString) with the categories from the category tree ($treeIds).
	 * If there are categories selected that are not present in the array $treeIds it assumes that those categories are
	 * parts of a chain of recursive categories returns their uids.
	 *
	 * @param	array		$treeIds: array with the ids of the categories in the tree
	 * @param	string		$catString: the selected categories in a string (format: uid|title,uid|title,...)
	 * @return	string		list of recursive categories
	 */
	function compareCategoryVals ($treeIds,$catString) {
		$recursiveCategories = array();
		$showncats = implode($treeIds,','); // the displayed categories (tree)
		$catvals = explode(',',$catString); // categories of the current record (left field)
		foreach ($catvals as $k) {
			$c = explode('|',$k);
			if(!t3lib_div::inList($showncats,$c[0])) {
				$recursiveCategories[]=$c;
			}
		}
		if ($recursiveCategories[0])  {
			$rcArr = array();
			foreach ($recursiveCategories as $key => $cat) {
				if ($cat[0]) $rcArr[] = $cat[1].' ('.$cat[0].')'; // format result: title (uid)
			}
			$rcList = implode($rcArr,', ');
		}
		return $rcList;
	}

	/**
	 * This functions displays the title field of a news record and checks if the record has categories assigned that are not allowed for the current BE user.
	 * If there are non allowed categories an error message will be displayed.
	 *
	 * @param	array		$PA: the parameter array for the current field
	 * @param	object		$fobj: Reference to the parent object
	 * @return	string		the HTML code for the field and the error message
	 */
	function displayTypeFieldCheckCategories(&$PA, $fobj)    {
		$table = $PA['table'];
		$field = $PA['field'];
		$row = $PA['row'];

		if ($GLOBALS['BE_USER']->getTSConfigVal('options.useListOfAllowedItems') && !$GLOBALS['BE_USER']->isAdmin()) {
			$notAllowedItems = array();
			if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']) { // get tt_news extConf array
				$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);
			}
			if ($confArr['useStoragePid']) {
				$TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig($table,$row);
				$storagePid = $TSconfig['_STORAGE_PID']?$TSconfig['_STORAGE_PID']:0;
				$SPaddWhere = ' AND tt_news_cat.pid IN (' . $storagePid . ')';
			}
			$notAllowedItems = $this->getNotAllowedItems($PA,$SPaddWhere);
			if ($notAllowedItems[0]) {
					// get categories of the record in db
				$uidField = $row['l18n_parent']&&$row['sys_language_uid']?$row['l18n_parent']:$row['uid'];
				$catres = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query ('tt_news_cat.uid,tt_news_cat.title,tt_news_cat_mm.sorting AS mmsorting', 'tt_news', 'tt_news_cat_mm', 'tt_news_cat', ' AND tt_news_cat_mm.uid_local='.$uidField.$SPaddWhere,'', 'mmsorting');
				$NACats = array();
				while ($catrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($catres)) {
					if($catrow['uid'] && $notAllowedItems[0] && in_array($catrow['uid'],$notAllowedItems)) {
						$NACats[]= '<p style="padding:0px;color:red;font-weight:bold;">- '.$catrow['title'].' <span class="typo3-dimmed"><em>['.$catrow['uid'].']</em></span></p>';
					}
				}
				if($NACats[0]) {
					$NA_Items =  '<table class="warningbox" border="0" cellpadding="0" cellspacing="0"><tbody><tr><td><img src="gfx/icon_fatalerror.gif" class="absmiddle" alt="" height="16" width="18">SAVING DISABLED!! <br />'.($row['l18n_parent']&&$row['sys_language_uid']?'The translation original of this':'This').' record has the following categories assigned that are not defined in your BE usergroup: '.implode($NACats,chr(10)).'</td></tr></tbody></table>';
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
