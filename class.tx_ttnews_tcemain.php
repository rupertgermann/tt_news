<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2007 Rupert Germann (rupi@gmx.li)
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
 * Class 'tx_ttnews_tcemain' for the tt_news extension.
 *
 * $Id: class.tx_ttnews_tcemain.php 5071 2007-02-27 23:16:44Z rupertgermann $
 *
 * @author     Rupert Germann <rupi@gmx.li>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   63: class tx_ttnews_tcemain
 *   76:     function processDatamap_postProcessFieldArray ($status, $table, $id, &$fieldArray, &$pObj)
 *   96:     function processDatamap_preProcessIncomingFieldArray()
 *  107:     function getSubCategories($catlist, $cc = 0)
 *  138:     function processDatamap_preProcessFieldArray(&$fieldArray, $table, $id, &$pObj)
 *
 *
 *  219: class tx_ttnews_tcemain_cmdmap
 *  233:     function processCmdmap_preProcess($command, &$table, &$id, &$value, &$pObj)
 *  291:     function processCmdmap_postProcess($command, $table, $srcId, $destId, &$pObj)
 *  338:     function int_recordTreeInfo($CPtable, $srcId, $counter, $rootID, $table, &$pObj)
 *
 * TOTAL FUNCTIONS: 7
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(t3lib_extMgm::extPath('tt_news').'class.tx_ttnews_div.php');;

/**
 * Class being included by TCEmain using a hook
 *
 * @author	Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage tt_news
 */
class tx_ttnews_tcemain {

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a record is saved. We use it to fix the value of the field "fe_group" which must not be empty in TYPO3 versions below 4.0.
	 *
	 * @param	string		$status: The TCEmain operation status, fx. 'update'
	 * @param	string		$table: The table TCEmain is currently processing
	 * @param	string		$id: The records id (if any)
	 * @param	array		$fieldArray: The field names and their values to be processed (passed by reference)
	 * @param	object		$pObj: Reference to the parent object (TCEmain)
	 * @return	void
	 * @access public
	 */
	function processDatamap_postProcessFieldArray ($status, $table, $id, &$fieldArray, &$pObj) {
		if (($table == 'tt_news' || $table == 'tt_news_cat') && t3lib_div::int_from_ver(TYPO3_version) < 4000000) {
			if ($status == 'new') {
				if (!strcmp($fieldArray['fe_group'],'')) {
					$fieldArray['fe_group'] = '0';
				}
			} elseif ($status == 'update') {
				if (isset($fieldArray['fe_group']) && !strcmp($fieldArray['fe_group'],'')) {
					$fieldArray['fe_group'] = '0';
				}
			}
		}
	}

	/**
	 * this function seems to needed for compatibility with TYPO3 3.7.0.
	 * In this TYPO3 version tcemain ckecks the existence of the method "processDatamap_preProcessIncomingFieldArray()" but calls "processDatamap_preProcessFieldArray()"
	 *
	 * @return	void
	 */
	function processDatamap_preProcessIncomingFieldArray() {

	}

	/**
	 * extends a given list of categories by their subcategories
	 *
	 * @param	string		$catlist: list of categories which will be extended by subcategories
	 * @param	integer		$cc: counter to detect recursion in nested categories
	 * @return	string		extended $catlist
	 */
	function getSubCategories($catlist, $cc = 0) {
		$pcatArr = array();

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			'tt_news_cat',
			'tt_news_cat.parent_category IN ('.$catlist.')'.$this->SPaddWhere.$this->enableCatFields);

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$cc++;
			if ($cc > 10000) { // more than 10k subcategories? looks like a recursion
				return implode(',', $pcatArr);
			}
			$subcats = $this->getSubCategories($row['uid'], $cc);
			$subcats = $subcats?','.$subcats:'';
			$pcatArr[] = $row['uid'].$subcats;
		}
		$catlist = implode(',', $pcatArr);
		return $catlist;
	}

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a record is saved. We use it to disable saving of the current record if it has categories assigned that are not allowed for the BE user.
	 *
	 * @param	array		$fieldArray: The field names and their values to be processed (passed by reference)
	 * @param	string		$table: The table TCEmain is currently processing
	 * @param	string		$id: The records id (if any)
	 * @param	object		$pObj: Reference to the parent object (TCEmain)
	 * @return	void
	 * @access public
	 */
	function processDatamap_preProcessFieldArray(&$fieldArray, $table, $id, &$pObj) {

		if ($table == 'tt_news_cat' && is_int($id)) { // prevent moving of categories into their rootline
			$newParent = intval($fieldArray['parent_category']);
			if ($newParent) {
				$subcategories = $this->getSubCategories($id);
				if (t3lib_div::inList($subcategories,$newParent)) {
					$sourceRec = t3lib_BEfunc::getRecord($table,$id,'title');
					$targetRec = t3lib_BEfunc::getRecord($table,$fieldArray['parent_category'],'title');
					$pObj->log($table,$id,2,0,1,"processDatamap: Attempt to move category '%s' (%s) to inside of its own rootline (at category '%s' (%s)).",1,array($sourceRec['title'],$id,$targetRec['title'],$newParent));
						// unset fieldArray to prevent saving of the record
					$fieldArray = array();
				}
			}
		}


		if ($table == 'tt_news') {

				// copy "type" field in localized records
			if (!is_int($id) && $fieldArray['l18n_parent']) { // record is a new localization
				$rec = t3lib_BEfunc::getRecord($table,$fieldArray['l18n_parent'],'type'); // get "type" from parent record
				$fieldArray['type'] = $rec['type']; // set type of current record
			}
				// direct preview
			if (isset($GLOBALS['_POST']['_savedokview_x']) && !$fieldArray['type'] && !$GLOBALS['BE_USER']->workspace)	{
					// if "savedokview" has been pressed and current article has "type" 0 (= normal news article) and the beUser works in the LIVE workspace open current record in single view
				$pagesTSC = t3lib_BEfunc::getPagesTSconfig($GLOBALS['_POST']['popViewId']); // get page TSconfig
				if ($pagesTSC['tx_ttnews.']['singlePid']) {
					$GLOBALS['_POST']['popViewId_addParams'] = ($fieldArray['sys_language_uid']>0?'&L='.$fieldArray['sys_language_uid']:'').'&no_cache=1&tx_ttnews[tt_news]='.$id;
					$GLOBALS['_POST']['popViewId'] = $pagesTSC['tx_ttnews.']['singlePid'];
				}

			}
// 			debug(t3lib_div::_GP('popViewId_addParams'),__FUNCTION__);

			if (!is_object($divObj)) {
				$divObj = t3lib_div::makeInstance('tx_ttnews_div');
			}
				// check permissions of assigned categories
			if ($divObj->useAllowedCategories() && is_int($id)) {

					// get categories from the tt_news record in db
				$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query ('tt_news_cat.uid,tt_news_cat_mm.sorting AS mmsorting', 'tt_news', 'tt_news_cat_mm', 'tt_news_cat', ' AND tt_news_cat_mm.uid_local='.(is_int($fieldArray['l18n_parent'])?$fieldArray['l18n_parent']:$id).t3lib_BEfunc::BEenableFields('tt_news_cat'));
				$categories = array();
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$categories[] = $row['uid'];
				}
				$notAllowedItems = array();
				if ($categories[0]) { // original record has categories
					if (!$divObj->allowedItemsFromTreeSelector) {
						$allowedItemsList = $GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.allowedItems');
					} else {
						$allowedItemsList = $divObj->getCategoryTreeIDs();
					}
					foreach ($categories as $k) {
						if(!t3lib_div::inList($allowedItemsList,$k)) {
							$notAllowedItems[]=$k;
						}
					}
				}
				if ($notAllowedItems[0]) {

					$pObj->log($table,$id,2,0,1,"processDatamap: Attempt to modify a record from table '%s' without permission. Reason: the record has one or more categories assigned that are not defined in your BE usergroup (".implode($notAllowedItems,',').").",1,array($table));
						// unset fieldArray to prevent saving of the record
					$fieldArray = array();
				}

			}
		}
	}

}

/**
 * Class being included by TCEmain using a hook
 *
 * @author	Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage tt_news
 */
class tx_ttnews_tcemain_cmdmap {

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a command was executed (copy,move,delete...).
	 * For tt_news it is used to disable saving of the current record if it has an editlock or if it has categories assigned that are not allowed for the current BE user.
	 *
	 * @param	string		$command: The TCEmain command, fx. 'delete'
	 * @param	string		$table: The table TCEmain is currently processing
	 * @param	string		$id: The records id (if any)
	 * @param	array		$value: The new value of the field which has been changed
	 * @param	object		$pObj: Reference to the parent object (TCEmain)
	 * @return	void
	 * @access public
	 */
	function processCmdmap_preProcess($command, &$table, &$id, &$value, &$pObj) {

		if ($table == 'tt_news' && !$GLOBALS['BE_USER']->isAdmin()) {
			$rec = t3lib_BEfunc::getRecord($table,$id,'editlock'); // get record to check if it has an editlock
			if ($rec['editlock']) {
				$pObj->log($table,$id,2,0,1,"processCmdmap [editlock]: Attempt to ".$command." a record from table '%s' which is locked by an 'editlock' (= record can only be edited by admins).",1,array($table));
				$error = true;
			}

			if (!is_object($divObj)) {
				$divObj = t3lib_div::makeInstance('tx_ttnews_div');
			}

			if ($divObj->useAllowedCategories() && is_int($id)) {
					// get categories from the (untranslated) record in db
				if ($table == 'tt_news') {
					$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query ('tt_news_cat.uid,tt_news_cat_mm.sorting AS mmsorting', 'tt_news', 'tt_news_cat_mm', 'tt_news_cat', ' AND tt_news_cat_mm.uid_local='.(is_int($id)?$id:0).t3lib_BEfunc::BEenableFields('tt_news_cat'));
					$categories = array();
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
						$categories[] = $row['uid'];
					}
					if (!$categories[0]) { // original record has no categories
						$notAllowedItems = array();
					} else { // original record has categories
						if (!$divObj->allowedItemsFromTreeSelector) {
							$allowedItemsList = $GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.allowedItems');
						} else {
							$allowedItemsList = $divObj->getCategoryTreeIDs();
						}
						$notAllowedItems = array();
						foreach ($categories as $k) {
							if(!t3lib_div::inList($allowedItemsList,$k)) {
								$notAllowedItems[]=$k;
							}
						}
					}
				}
				if ($notAllowedItems[0]) {
					$pObj->log($table,$id,2,0,1,"tt_news processCmdmap: Attempt to ".$command." a record from table '%s' without permission. Reason: the record has one or more categories assigned that are not defined in your BE usergroup (tablename.allowedItems).",1,array($table));
					$error = true;
				}
				if ($error) {
					$table = ''; // unset table to prevent saving
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$command: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$srcId: ...
	 * @param	[type]		$destId: ...
	 * @param	[type]		$pObj: ...
	 * @return	[type]		...
	 */
	function processCmdmap_postProcess($command, $table, $srcId, $destId, &$pObj) {

			// copy records recursively from Drag&Drop in the category manager
		if ($table == 'tt_news_cat' && $command == 'DDcopy') {
			$srcRec = t3lib_BEfunc::getRecordWSOL('tt_news_cat',$srcId);
			$overrideValues = array('parent_category' => $destId, 'hidden' => 1);
			$newRecID = $pObj->copyRecord($table,$srcId,$srcRec['pid'],1,$overrideValues);
			$CPtable = $this->int_recordTreeInfo(array(), $srcId, 99, $newRecID, $table, $pObj);

			foreach($CPtable as $recUid => $recParent)	{
				$newParent = $pObj->copyMappingArray[$table][$recParent];
				if (isset($newParent))	{
					$overrideValues = array('parent_category' => $newParent, 'hidden' => 1);
					$pObj->copyRecord($table,$recUid,$srcRec['pid'],1,$overrideValues);
				} else {
					$pObj->log($table,$srcId,5,0,1,'Something went wrong during copying branch');
					break;
				}
			}
		}
			// delete records recursively from Context Menu in the category manager
		if ($table == 'tt_news_cat' && $command == 'DDdelete') {
			$pObj->deleteRecord($table,$srcId, $noRecordCheck=FALSE);
			$CPtable = $this->int_recordTreeInfo(array(), $srcId, 99, $srcId, $table, $pObj);

			foreach($CPtable as $recUid => $p)	{
				if (isset($recUid))	{
					$pObj->deleteRecord($table,$recUid, $noRecordCheck=FALSE);
				} else {
					$pObj->log($table,$recUid,5,0,1,'Something went wrong during deleting branch');
					break;
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$CPtable: ...
	 * @param	[type]		$srcId: ...
	 * @param	[type]		$counter: ...
	 * @param	[type]		$rootID: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$pObj: ...
	 * @return	[type]		...
	 */
	function int_recordTreeInfo($CPtable, $srcId, $counter, $rootID, $table, &$pObj)	{
		if ($counter)	{
			$addW =  !$pObj->admin ? ' AND '.$pObj->BE_USER->getPagePermsClause($pObj->pMap['show']) : '';
			$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, 'parent_category='.intval($srcId).$pObj->deleteClause($table).$addW, '', '');
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres))	{
				if ($row['uid']!=$rootID)	{
					$CPtable[$row['uid']] = $srcId;
					if ($counter-1)	{	// If the uid is NOT the rootID of the copyaction and if we are supposed to walk further down
						$CPtable = $this->int_recordTreeInfo($CPtable,$row['uid'],$counter-1, $rootID, $table, $pObj);
					}
				}
			}
		}
		return $CPtable;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.tx_ttnews_tcemain.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.tx_ttnews_tcemain.php']);
}

?>