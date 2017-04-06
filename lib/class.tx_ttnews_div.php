<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2009 Rupert Germann <rupi@gmx.li>
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

namespace WMDB\TtNews\Lib;

/**
 * tt_news misc functions
 *
 * $Id$
 *
 * @author	Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage tt_news
 */
class tx_ttnews_div {


	/**
	 * Get category mounts of the current user
	 *
	 * @param bool $withSub Also return subcategories
	 * @return string commeseparated list of mounts
	 */
	static public function getBeUserCatMounts($withSub = TRUE) {
		global $BE_USER;

		$cmounts = array();

		if (is_array($BE_USER->userGroups)) {
			foreach ($BE_USER->userGroups as $group) {
				if ($group['tt_news_categorymounts']) {
					$cmounts[] = $group['tt_news_categorymounts'];
				}
			}
		}
		if ($BE_USER->user['tt_news_categorymounts']) {
			$cmounts[] = $BE_USER->user['tt_news_categorymounts'];
		}
		$categoryMounts = implode(',',$cmounts);

		if ($withSub && $categoryMounts) {
			$subcats = tx_ttnews_div::getSubCategories($categoryMounts);
			$categoryMounts = implode(',', array_unique(explode(',', $categoryMounts.($subcats?','.$subcats:''))));
		}
		return $categoryMounts;
	}


	/**
	 * Extends a given list of categories by their subcategories
	 *
	 * @param string $catlist list of categories which will be extended by subcategories
	 * @param string $addWhere additional WHERE restricion
	 * @param int $cc counter to detect recursion in nested categories
	 * @return string extended $catlist
	 */
	static public function getSubCategories($catlist, $addWhere = '', $cc = 0) {

		if (!$catlist) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('EMPTY $catlist ('.__CLASS__.'::'.__FUNCTION__.')', 'tt_news', 3, array());
		}


		$sCatArr = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			'tt_news_cat',
			'tt_news_cat.parent_category IN ('.$catlist.') AND deleted=0 '.$addWhere);

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$cc++;
			if ($cc > 10000) {
				$GLOBALS['TT']->setTSlogMessage('tt_news: one or more recursive categories where found');
				return implode(',', $sCatArr);
			}
			$subcats = tx_ttnews_div::getSubCategories($row['uid'],$addWhere, $cc);
			$subcats = $subcats ? ',' . $subcats : '';
			$sCatArr[] = $row['uid'].$subcats;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return implode(',', $sCatArr);
	}

	static public function getNewsCountForSubcategory(&$result, $cat, $news_clause, $catclause) {
		// count news in current category

		$select_fields = 'COUNT(DISTINCT tt_news.uid)';
		$from_table = '	tt_news_cat, tt_news_cat_mm, tt_news ';

		$where_clause = '
			tt_news_cat.uid='.$cat.'
			AND tt_news_cat.uid=tt_news_cat_mm.uid_foreign
			AND tt_news_cat_mm.uid_local=tt_news.uid


		';
		$where_clause .= $news_clause;
		$where_clause .= $catclause;

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select_fields, $from_table, $where_clause);
		$cRow = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		$result['sum'] += $cRow[0];

		// get subcategories
		$select_fields = 'tt_news_cat.uid';
		$from_table = '	tt_news_cat';

		$where_clause = '
			tt_news_cat.parent_category='.$cat.'
			AND tt_news_cat.deleted=0 AND tt_news_cat.hidden=0

		';
		$where_clause .= $catclause;

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select_fields, $from_table, $where_clause);

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			tx_ttnews_div::getNewsCountForSubcategory($result, $row['uid'], $news_clause,$catclause);
		}

		$GLOBALS['TYPO3_DB']->sql_free_result($res);

	}

	/**
	 * returns a list of all allowed categories for the current user.
	 * Subcategories are included, categories from "tt_newsPerms.tt_news_cat.excludeList" are excluded
	 *
	 * @return array tree IDs
	 */
	static public function getAllowedTreeIDs() {

		$catlistWhere = tx_ttnews_div::getCatlistWhere();
		$treeIDs = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tt_news_cat', '1=1' . $catlistWhere . ' AND deleted=0');
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$treeIDs[] = $row['uid'];
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $treeIDs;
	}

	/**
	 * Get WHERE restrictions for the category list query of the current user
	 *
	 * @return string WHERE query part
	 */
	static public function getCatlistWhere() {
		$catlistWhere = '';
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			// get include/exclude items
			$excludeList = $GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.excludeList');
			$includeCatArray = tx_ttnews_div::getIncludeCatArray();

			if ($excludeList) {
				$catlistWhere .= ' AND tt_news_cat.uid NOT IN ('.implode(\TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',',$excludeList),',').')';
			}
			if (!empty($includeCatArray)) {
				$catlistWhere .= ' AND tt_news_cat.uid IN ('.implode(',',$includeCatArray).')';
			}
		}

		return $catlistWhere;
	}

	/**
	 * Get categories to include for the current user
	 *
	 * @return array ids of categories to include
	 */
	static public function getIncludeCatArray() {
		$includeList = $GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.includeList');
		$catmounts = tx_ttnews_div::getBeUserCatMounts();
		if ($catmounts) {
			$includeList = $catmounts;
		}

		return \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',',$includeList, 1);
	}

	/**
	 * Returns the correct splitLabel string for a locallang file in EXT:lang,
	 * respecting the current TYPO3 version.
	 *
	 * Example:
	 *
	 * $fileNameWithoutExtension = 'locallang_general';
	 * $key = 'LGL.hidden';
	 * Would return:
	 * - 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden' (TYPO3 < 8) or
	 * - 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden' (TYPO3 >= 8)
	 *
	 * @param string $fileNameWithoutExtension
	 * @param string $key
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public static function getLocallangSplitLabelForExtLang($fileNameWithoutExtension, $key)
	{
	    if (!\is_string($fileNameWithoutExtension) || empty($fileNameWithoutExtension)) {
	        throw new \InvalidArgumentException('$fileNameWithoutExtension must be not empty string', 1491496347);
	    } elseif (!\is_string($key) || empty($key)) {
	        throw new \InvalidArgumentException('$key must be not empty string', 1491496368);
	    }

	    static $useV8Style = null;
	    if ($useV8Style === null) {
	        $useV8Style = \version_compare(TYPO3_version, '8', '>=');
	    }

	    $splitLabel = 'LLL:EXT:lang/';
	    if ($useV8Style) {
	        $splitLabel .= 'Resources/Private/Language/';
	    }

	    return $splitLabel . $fileNameWithoutExtension . '.xlf:' . $key;
	}
}
