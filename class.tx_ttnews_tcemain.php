<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Rupert Germann (rupi@gmx.li)
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
 * $Id:
 *
 * @author     Rupert Germann <rupi@gmx.li>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   57: class tx_ttnews_tcemain
 *   72:     function processDatamap_preProcessFieldArray(&$fieldArray, $table, $id, &$pObj)
 *
 *
 *  115: class tx_ttnews_tcemain_cmdmap
 *  128:     function processCmdmap_preProcess($command, &$table, $id, $value, &$pObj)
 *
 * TOTAL FUNCTIONS: 2
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
	function processDatamap_preProcessIncomingFieldArray() {
		// this function seems to needed for compatibility with TYPO3 3.7.0. In this TYPO3 version tcemain ckecks the existence of the method "processDatamap_preProcessIncomingFieldArray()" and calls "processDatamap_preProcessFieldArray()"
	}
	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a record is saved. We use it to disable saving of the current record if it has categories assigned that are not allowed for the BE user.
	 *
	 * @param	string		$status: The TCEmain operation status, fx. 'update'
	 * @param	string		$table: The table TCEmain is currently processing
	 * @param	string		$id: The records id (if any)
	 * @param	array		$fieldArray: The field names and their values to be processed
	 * @param	object		$reference: Reference to the parent object (TCEmain)
	 * @return	void
	 * @access public
	 */
	function processDatamap_preProcessFieldArray(&$fieldArray, $table, $id, &$pObj) {
		if ($table == 'tt_news') {
		
				// copy "type" field in localized records
			if (!is_int($id) && $fieldArray['l18n_parent']) { // record is a new localization
				$rec = t3lib_BEfunc::getRecord($table,$fieldArray['l18n_parent'],'type'); // get "type" from parent record
				$fieldArray['type'] = $rec['type']; // set type of current record
			}
			
				// direct preview
			if (isset($GLOBALS['_POST']['_savedokview_x']) && !$fieldArray['type'])	{
					// if "savedokview" has been pressed and current article has "type" 0 (= normal news article) open current record in single view
				$pagesTSC = t3lib_BEfunc::getPagesTSconfig($GLOBALS['_POST']['popViewId']); // get page TSconfig
				$GLOBALS['_POST']['popViewId_addParams'] = ($fieldArray['sys_language_uid']>0?'&L='.$fieldArray['sys_language_uid']:'').'&no_cache=1&tx_ttnews[tt_news]='.$id;
				$GLOBALS['_POST']['popViewId'] = $pagesTSC['tx_ttnews.']['singlePid'];
			}
// 			debug(t3lib_div::_GP('popViewId_addParams'),__FUNCTION__);
				// check permissions of assigned categories
			if ($GLOBALS['BE_USER']->getTSConfigVal('options.useListOfAllowedItems') && !$GLOBALS['BE_USER']->isAdmin() && is_int($id)) {

					// get categories from the tt_news record in db
				$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query ('tt_news_cat.uid,tt_news_cat_mm.sorting AS mmsorting', 'tt_news', 'tt_news_cat_mm', 'tt_news_cat', ' AND tt_news_cat_mm.uid_local='.(is_int($fieldArray['l18n_parent'])?$fieldArray['l18n_parent']:$id).t3lib_BEfunc::BEenableFields('tt_news_cat'));
				$categories = array();
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$categories[] = $row['uid'];
				}
				$notAllowedItems = array();
				if ($categories[0]) { // original record has categories
					$allowedItemsList=$GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.allowedItems');
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
	function processCmdmap_preProcess($command, &$table, $id, $value, &$pObj) {
		if ($table == 'tt_news' && !$GLOBALS['BE_USER']->isAdmin()) {
			$rec = t3lib_BEfunc::getRecord($table,$id,'editlock'); // get record to check if it has an editlock
			if ($rec['editlock']) {
				$pObj->log($table,$id,2,0,1,"processCmdmap [editlock]: Attempt to ".$command." a record from table '%s' which is locked by an 'editlock' (= record can only be edited by admins).",1,array($table));
				$error = true;
			}
			if ($GLOBALS['BE_USER']->getTSConfigVal('options.useListOfAllowedItems') && is_int($id)) {
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
						$allowedItemsList=$GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.allowedItems');
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
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.tx_ttnews_tcemain.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.tx_ttnews_tcemain.php']);
}

?>