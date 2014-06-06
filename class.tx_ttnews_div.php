<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2007 Rupert Germann <rupi@gmx.li>
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   50: class tx_ttnews_div
 *   54:     function useAllowedCategories ()
 *   86:     function getAllowedCategories()
 *  137:     function getCategoryTreeIDs()
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



/**
 * Class for updating tt_news content elements and category relations.
 *
 * $Id: class.ext_update.php 3023 2006-04-19 12:10:14Z rupertgermann $
 *
 * @author  Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage tt_news
 */
class tx_ttnews_div {



	function useAllowedCategories () {
		global $BE_USER;
		if (!$BE_USER->isAdmin()) {
			if ($BE_USER->user['tt_news_categorymounts']) {
				$this->allowedItemsFromTreeSelector = TRUE;
				return TRUE;
			} else { // no categorymounts set in be_user record - check groups
				if (is_array($BE_USER->userGroups)) {
					$cmounts = array();
					foreach ($BE_USER->userGroups as $gid => $group) {
						if ($group['tt_news_categorymounts']) {
							$cmounts[] = $group['tt_news_categorymounts'];
						}
					}
					$cMountList = implode(',',$cmounts);
					if ($cMountList) {
						$this->allowedItemsFromTreeSelector = TRUE;
						return TRUE;
					}
				}
			}
			if ($BE_USER->getTSConfigVal('options.useListOfAllowedItems')) {
				return TRUE;
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getAllowedCategories() {
		global $BE_USER, $TYPO3_DB;

		$cmounts = array();

		if (is_array($BE_USER->userGroups)) {
			foreach ($BE_USER->userGroups as $gid => $group) {
				if ($group['tt_news_categorymounts']) {
					$cmounts[] = $group['tt_news_categorymounts'];
				}
			}
		}
		if ($BE_USER->user['tt_news_categorymounts']) {
			$cmounts[] = $BE_USER->user['tt_news_categorymounts'];
		}
			// MOUNTS must only contain the main/parent categories. Therefore it is required to filter out the subcategories from $this->catExclusive or $lConf['includeList']
		$categoryMounts = implode(',',$cmounts);
		if ($categoryMounts) {
			$tmpres = $TYPO3_DB->exec_SELECTquery(
				'uid,parent_category',
				'tt_news_cat',
				'tt_news_cat.uid IN ('.$categoryMounts.')'/*.$this->SPaddWhere.$this->enableCatFields,
				'',
				'tt_news_cat.'.$this->config['catOrderBy']*/);

			$cleanedCategoryMounts = array();

			if ($tmpres) {
				while ($tmprow = $TYPO3_DB->sql_fetch_assoc($tmpres)) {

					if (!t3lib_div::inList($categoryMounts,$tmprow['parent_category'])) {
	// 					$dontStartFromRootRecord = true;
						$cleanedCategoryMounts[] = $tmprow['uid'];
					}
				}
			}
			$cMountList = implode(',',$cleanedCategoryMounts);
		}

// 		 			debug ($cMountList);

		if ($cMountList) {
			return $cMountList;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getCategoryTreeIDs() {

		require_once(t3lib_extMgm::extPath('tt_news').'class.tx_ttnews_treeview.php');;

		global $TCA,$BE_USER;

			// get include/exclude items
		$excludeList = $BE_USER->getTSConfigVal('tt_newsPerms.tt_news_cat.excludeList');
		$includeList = $BE_USER->getTSConfigVal('tt_newsPerms.tt_news_cat.includeList');
		$catmounts = $this->getAllowedCategories();
		if ($catmounts) {
			$includeList = $catmounts;
		}

		if ($excludeList) {
			$catlistWhere = ' AND tt_news_cat.uid NOT IN ('.implode(t3lib_div::intExplode(',',$excludeList),',').')';
		}

		$treeViewObj = t3lib_div::makeInstance('tx_ttnews_tceFunc_selectTreeView');
		$treeViewObj->table = 'tt_news_cat';
		$treeViewObj->init($catlistWhere);
	// 	$treeViewObj->backPath = $this->pObj->backPath;
		$treeViewObj->parentField = 'parent_category';
		$treeViewObj->expandAll = 1;
		$treeViewObj->expandFirst = 1;
		$treeViewObj->fieldArray = array('uid','title','description'); // those fields will be filled to the array $treeViewObj->tree

		if ($includeList) {
			$treeViewObj->MOUNTS = t3lib_div::intExplode(',',$includeList);
		}

		$treeViewObj->TCEforms_selectedItemsArray = array();
		$treeViewObj->TCEforms_nonSelectableItemsArray = array();
		$treeViewObj->makeHTML = 0;
		$treeViewObj->getBrowsableTree();

		if (!is_array($treeViewObj->MOUNTS)) { $treeViewObj->MOUNTS = array(); }
		if (!is_array($treeViewObj->ids)) { $treeViewObj->ids = array(); }
		$treeIdArray = array_merge($treeViewObj->MOUNTS,$treeViewObj->ids);

		if (is_array($treeIdArray)) {
			$treeIDs = implode(',',$treeIdArray);

		}
// 			debug ($treeIDs,'$treeIDs',__FUNCTION__,__CLASS__);
		return $treeIDs;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.tx_ttnews_div.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.tx_ttnews_div.php']);
}
?>
