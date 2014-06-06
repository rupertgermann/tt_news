<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Rupert Germann (rupi@gmx.li)
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
 * Class 'tx_ttnews_tcemain' for the tt_news extension.
 *
 * $Id$
 *
 * @author     Rupert Germann <rupi@gmx.li>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   64: class tx_ttnews_tcemain
 *   73:     function getSubCategories($catlist, $cc = 0)
 *  105:     function processDatamap_preProcessFieldArray(&$fieldArray, $table, $id, &$pObj)
 *
 *
 *  187: class tx_ttnews_tcemain_cmdmap
 *  201:     function processCmdmap_preProcess($command, &$table, &$id, $value, &$pObj)
 *  263:     function processCmdmap_postProcess($command, $table, $srcId, $destId, &$pObj)
 *  310:     function int_recordTreeInfo($CPtable, $srcId, $counter, $rootID, $table, &$pObj)
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

/**
 * Class being included by TCEmain using a hook
 *
 * @author	Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage tt_news
 */
class tx_ttnews_tcemain {

	/**
	 * extends a given list of categories by their subcategories
	 *
	 * @param	string		$catlist: list of categories which will be extended by subcategories
	 * @param	integer		$cc: counter to detect recursion in nested categories
	 * @return	string		extended $catlist
	 */
//	function getSubCategories($catlist, $cc = 0) {
//		$pcatArr = array();
//
//		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
//			'uid',
//			'tt_news_cat',
//			'tt_news_cat.parent_category IN ('.$catlist.')'.$this->SPaddWhere.$this->enableCatFields);
//
//		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
//			$cc++;
//			if ($cc > 10000) { // more than 10k subcategories? looks like a recursion
//				return implode(',', $pcatArr);
//			}
//			$subcats = $this->getSubCategories($row['uid'], $cc);
//			$subcats = $subcats?','.$subcats:'';
//			$pcatArr[] = $row['uid'].$subcats;
//		}
//
//		$catlist = implode(',', $pcatArr);
//		return $catlist;
//	}

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
				$subcategories = tx_ttnews_div::getSubCategories($id, $this->SPaddWhere . $this->enableCatFields);
				if (t3lib_div::inList($subcategories, $newParent)) {
					$sourceRec = t3lib_BEfunc::getRecord($table, $id, 'title');
					$targetRec = t3lib_BEfunc::getRecord($table, $fieldArray['parent_category'], 'title');


					/**
					 * TODO: 19.05.2009
					 * localize
					 */

					$pObj->log($table, $id, 2, 0, 1, "processDatamap: Attempt to move category '%s' (%s) to inside of its own rootline (at category '%s' (%s)).", 1, array($sourceRec['title'], $id, $targetRec['title'], $newParent));
						// unset fieldArray to prevent saving of the record
					$fieldArray = array();
				}
			}
		}


		if ($table == 'tt_news') {

				// copy "type" field in localized records
			if (!is_int($id) && $fieldArray['l18n_parent']) { // record is a new localization
				$rec = t3lib_BEfunc::getRecord($table, $fieldArray['l18n_parent'], 'type'); // get "type" from parent record
				$fieldArray['type'] = $rec['type']; // set type of current record
			}

			// check permissions of assigned categories
			if (is_int($id) && !$GLOBALS['BE_USER']->isAdmin()) {
				$categories = array();
				$recID = (($fieldArray['l18n_parent'] > 0) ? $fieldArray['l18n_parent'] : $id);
					// get categories from the tt_news record in db
				$cRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'uid_foreign, deleted',
						'tt_news_cat_mm, tt_news_cat',
						'uid_foreign=uid AND deleted=0 AND uid_local=' . $recID);

				while (($cRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($cRes))) {
					$categories[] = $cRow['uid_foreign'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($cRes);

				$notAllowedItems = array();
				if ($categories[0]) { // original record has no categories
					$treeIDs = tx_ttnews_div::getAllowedTreeIDs();
					if (count($treeIDs)) {
						$allowedItems = $treeIDs;
					} else {
						$allowedItems = t3lib_div::intExplode(',', $GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.allowedItems'));
					}
					foreach ($categories as $k) {
						if (!in_array($k, $allowedItems)) {
							$notAllowedItems[] = $k;
						}
					}
				}
				if ($notAllowedItems[0]) {

					$pObj->log($table, $id, 2, 0, 1, "processDatamap: Attempt to modify a record from table '%s' without permission. Reason: the record has one or more categories assigned that are not defined in your BE usergroup (" . implode($notAllowedItems, ',') . ").", 1, array($table));
						// unset fieldArray to prevent saving of the record
					$fieldArray = array();
				}

			}
		}
	}

	function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $pObj) {
		if ($table == 'tt_news') {
				// direct preview
			if (!is_numeric($id)) {
				$id = $pObj->substNEWwithIDs[$id];
			}
			if (isset($GLOBALS['_POST']['_savedokview_x']) && !$fieldArray['type'] && !$GLOBALS['BE_USER']->workspace) {
					// if "savedokview" has been pressed and current article has "type" 0 (= normal news article)
					// and the beUser works in the LIVE workspace open current record in single view
				$pagesTSC = t3lib_BEfunc::getPagesTSconfig($GLOBALS['_POST']['popViewId']); // get page TSconfig
				if ($pagesTSC['tx_ttnews.']['singlePid']) {
					$GLOBALS['_POST']['popViewId_addParams'] = ($fieldArray['sys_language_uid'] > 0 ?
						'&L=' . $fieldArray['sys_language_uid'] : '') . '&no_cache=1&tx_ttnews[tt_news]=' . $id;
					$GLOBALS['_POST']['popViewId'] = $pagesTSC['tx_ttnews.']['singlePid'];
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
	function processCmdmap_preProcess($command, &$table, &$id, $value, &$pObj) {

		if ($table == 'tt_news' && !$GLOBALS['BE_USER']->isAdmin()) {
			$rec = t3lib_BEfunc::getRecord($table, $id, 'editlock'); // get record to check if it has an editlock
			if ($rec['editlock']) {
				$pObj->log($table, $id, 2, 0, 1, "processCmdmap [editlock]: Attempt to " . $command . " a record from table '%s' which is locked by an 'editlock' (= record can only be edited by admins).", 1, array($table));
				$error = true;
			}


			if (is_int($id)) {
					// get categories from the (untranslated) record in db
				$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
						'tt_news_cat.uid, tt_news_cat.deleted, tt_news_cat_mm.sorting AS mmsorting',
						'tt_news',
						'tt_news_cat_mm',
						'tt_news_cat',
					' AND tt_news_cat.deleted=0 AND tt_news_cat_mm.uid_local=' . (is_int($id) ? $id : 0) . t3lib_BEfunc::BEenableFields('tt_news_cat'));
				$categories = array();
				while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
					$categories[] = $row['uid'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);

				$notAllowedItems = array();
				if ($categories[0]) { // original record has no categories
					$treeIDs = tx_ttnews_div::getAllowedTreeIDs();
					if (count($treeIDs)) {
						$allowedItems = $treeIDs;
					} else {
						$allowedItems = t3lib_div::intExplode(',', $GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.allowedItems'));
					}
					foreach ($categories as $k) {
						if (!in_array($k, $allowedItems)) {
							$notAllowedItems[] = $k;
						}
					}
				}

				if ($notAllowedItems[0]) {
					$pObj->log($table, $id, 2, 0, 1, "tt_news processCmdmap: Attempt to " . $command . " a record from table '%s' without permission. Reason: the record has one or more categories assigned that are not defined in your BE usergroup (tablename.allowedItems).", 1, array($table));
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
			$srcRec = t3lib_BEfunc::getRecordWSOL('tt_news_cat', $srcId);
			$overrideValues = array('parent_category' => $destId, 'hidden' => 1);
			$newRecID = $pObj->copyRecord($table, $srcId, $srcRec['pid'], 1, $overrideValues);
			$CPtable = $this->int_recordTreeInfo(array(), $srcId, 99, $newRecID, $table, $pObj);

			foreach ($CPtable as $recUid => $recParent) {
				$newParent = $pObj->copyMappingArray[$table][$recParent];
				if (isset($newParent))	{
					$overrideValues = array('parent_category' => $newParent, 'hidden' => 1);
					$pObj->copyRecord($table, $recUid, $srcRec['pid'], 1, $overrideValues);
				} else {
					$pObj->log($table, $srcId, 5, 0, 1, 'Something went wrong during copying branch');
					break;
				}
			}
		}
			// delete records recursively from Context Menu in the category manager
		if ($table == 'tt_news_cat' && $command == 'DDdelete') {
			$pObj->deleteRecord($table, $srcId, FALSE);
			$CPtable = $this->int_recordTreeInfo(array(), $srcId, 99, $srcId, $table, $pObj);

			foreach ($CPtable as $recUid => $p) {
				if (isset($recUid))	{
					$pObj->deleteRecord($table, $recUid, FALSE);
				} else {
					$pObj->log($table, $recUid, 5, 0, 1, 'Something went wrong during deleting branch');
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
			$addW = !$pObj->admin ? ' AND ' . $pObj->BE_USER->getPagePermsClause($pObj->pMap['show']) : '';
			$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, 'parent_category=' . intval($srcId) . $pObj->deleteClause($table) . $addW, '', '');
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres))) {
				if ($row['uid'] != $rootID) {
					$CPtable[$row['uid']] = $srcId;
					if ($counter - 1) { // If the uid is NOT the rootID of the copyaction and if we are supposed to walk further down
						$CPtable = $this->int_recordTreeInfo($CPtable, $row['uid'], $counter - 1, $rootID, $table, $pObj);
					}
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($mres);
		}
		return $CPtable;
	}

}




if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_tcemain.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_tcemain.php']);
}

?>