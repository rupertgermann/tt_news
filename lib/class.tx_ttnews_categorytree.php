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
 * generates a tree from tt_news categories.
 *
 * $Id$
 *
 * @author	Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage tt_news
 */

require_once (t3lib_extMgm::extPath('tt_news') . 'lib/class.tx_ttnews_div.php');
/**
 * extend class t3lib_treeview to change function wrapTitle().
 *
 */
class tx_ttnews_categorytree extends t3lib_treeview {

	var $categoryCountCache = array();
	var $cacheHit = false;


	/**
	 * Will create and return the HTML code for a browsable tree
	 * Is based on the mounts found in the internal array ->MOUNTS (set in the constructor)
	 *
	 * @param	[type]		$groupByPages: ...
	 * @return	string		HTML code for the browsable tree
	 */
	function getBrowsableTree($groupByPages = false) {

		// Get stored tree structure AND updating it if needed according to incoming PM GET var.
		$this->initializePositionSaving();

		// Init done:
		$treeArr = array();
		$tmpClause = $this->clause;
		$savedTable = $this->table;

		$this->tmpC = 0;

		if ($this->tt_news_obj->cache_categoryCount) {
			$storeKey = md5(serialize(array($this->stored,$this->MOUNTS,
					$this->newsSelConf['pidInList'] . $this->newsSelConf['where'] . $this->tt_news_obj->enableFields . $this->clause)));

//			$tmpCCC = $GLOBALS['TSFE']->sys_page->getHash($storeKey);
			$tmpCCC = $this->tt_news_obj->cache->get($storeKey);
			if ($tmpCCC) {
				if ($this->tt_news_obj->writeCachingInfoToDevlog>1) {
					t3lib_div::devLog('categoryCountCache CACHE HIT (' . __CLASS__ . '::' . __FUNCTION__ . ')', 'tt_news', - 1, array());
				}

				$this->categoryCountCache = unserialize($tmpCCC);
				$this->cacheHit = TRUE;
			} else {
				if ($this->tt_news_obj->writeCachingInfoToDevlog) {
					t3lib_div::devLog('categoryCountCache CACHE MISS (' . __CLASS__ . '::' . __FUNCTION__ . ')', 'tt_news', 2, array($this->stored,$this->MOUNTS,
					$this->newsSelConf['pidInList'] . $this->newsSelConf['where'] . $this->tt_news_obj->enableFields . $this->clause));
				}
			}

		}

		// Traverse mounts:
		foreach ($this->MOUNTS as $idx => $uid) {

			// Set first:
			$this->bank = $idx;
			$isOpen = $this->stored[$idx][$uid] || $this->expandFirst;

			// Save ids while resetting everything else.
			$curIds = $this->ids;
			$this->reset();
			$this->ids = $curIds;

			// Set PM icon for root of mount:
			$cmd = $this->bank . '_' . ($isOpen ? "0_" : "1_") . $uid . '_' . $this->treeName;

			$icon = '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/ol/' . ($isOpen ? 'minus' : 'plus') . 'only.gif') . ' alt="" />';
			if ($this->expandable && ! $this->expandFirst) {
				$firstHtml = $this->PMiconATagWrap($icon, $cmd);
			} else {
				$firstHtml = $icon;
			}

			$this->addStyle = '';

			// Preparing rootRec for the mount
			if ($groupByPages) {
				$this->table = 'pages';
				//				$this->addStyle = ' style="margin-bottom:10px;"';
			}

			if ($uid) {
				$rootRec = $this->getRecord($uid);
				if (is_array($rootRec)) {
					$firstHtml .= $this->getIcon($rootRec);
				}
			} else {

				if ($this->storagePid > 0 && $this->useStoragePid) {
					// root = page record of current GRSP
					$this->table = 'pages';
					$rootRec = $this->getRecord($this->storagePid);
					$firstHtml .= $this->getIcon($rootRec);
					$rootRec['uid'] = 0;
				} else {
					// Artificial record for the tree root, id=0
					$rootRec = $this->getRootRecord($uid);
					$firstHtml .= $this->getRootIcon($rootRec);
				}
			}
			if ($groupByPages) {
				$this->clause = $tmpClause . ' AND tt_news_cat.pid=' . $uid;
				$rootRec['uid'] = 0;
			}

			// restore $this->table
			$this->table = $savedTable;

			if (is_array($rootRec)) {
				// In case it was swapped inside getRecord due to workspaces.
				$uid = $rootRec['uid'];

				// Add the root of the mount to ->tree
				$this->tree[] = array('HTML' => $firstHtml, 'row' => $rootRec, 'bank' => $this->bank, 'hasSub' => true,
						'invertedDepth' => 1000);

				// If the mount is expanded, go down:
				if ($isOpen) {
					// Set depth:
					if ($this->addSelfId) {
						$this->ids[] = $uid;
					}
					$this->getTree($uid, 999, '', $rootRec['_SUBCSSCLASS']);
				}
				// Add tree:
				$treeArr = array_merge($treeArr, $this->tree);
			}
		}

		if ($this->tt_news_obj->cache_categoryCount && count($this->categoryCountCache) && !$this->cacheHit) {
//			$GLOBALS['TSFE']->sys_page->storeHash($storeKey, serialize($this->categoryCountCache), 'news_categoryCountCache');
			$this->tt_news_obj->cache->set($storeKey,serialize($this->categoryCountCache),'categoryCounts');
		}

		return $this->printTree($treeArr);
	}


	function getNewsCountForCategory($catID) {
		$sum = false;

		if (isset($this->categoryCountCache[$catID])) {
			$sum = $this->categoryCountCache[$catID];
		}

		if ($sum !== false) {
			//			t3lib_div::devLog('CACHE HIT (' . __CLASS__ . '::' . __FUNCTION__ . ')', 'tt_news', - 1, array());
		} else {
			if ($this->tt_news_obj->cache_categoryCount) {
				$hash = t3lib_div::shortMD5(serialize($catID . $this->newsSelConf['pidInList'] . $this->newsSelConf['where'] . $this->tt_news_obj->enableFields . $this->clause), 30);
				$sum = $this->tt_news_obj->cache->get($hash);

			}

			if ($sum === false) {
				if ($this->tt_news_obj->writeCachingInfoToDevlog) {
					t3lib_div::devLog('CACHE MISS (single count) (' . __CLASS__ . '::' . __FUNCTION__ . ')', 'tt_news', 2, array());
				}

				$result = array();
				$result['sum'] = 0;

				$news_clause = '';
				if (is_object($this->tt_news_obj)) {
					$news_clause .= ' AND ' . $this->newsSelConf['where'] . $this->tt_news_obj->enableFields;
					if ($this->newsSelConf['pidInList']) {
						$news_clause .= ' AND tt_news.pid IN (' . $this->newsSelConf['pidInList'] . ') ';
					}
				}

				tx_ttnews_div::getNewsCountForSubcategory($result, $catID, $news_clause, $this->clause);
				$sum = $result['sum'];

			}
			$this->categoryCountCache[$catID] = (int) $sum;
			if ($this->tt_news_obj->cache_categoryCount) {
				$this->tt_news_obj->cache->set($hash, (string)$sum,'categoryCounts');
			}

		}

		return $sum;
	}


	/**
	 * Fetches the data for the tree
	 *
	 * @param	integer		item id for which to select subitems (parent id)
	 * @param	integer		Max depth (recursivity limit)
	 * @param	string		? (internal)
	 * @param	[type]		$subCSSclass: ...
	 * @return	integer		The count of items on the level
	 */
	function getTree($uid, $depth = 999, $blankLineCode = '', $subCSSclass = '') {

		//		echo $this->tmpC++."\n";


		// Buffer for id hierarchy is reset:
		$this->buffer_idH = array();

		// Init vars
		$depth = intval($depth);
		$HTML = '';
		$a = 0;

		$res = $this->getDataInit($uid, $subCSSclass);
		$c = $this->getDataCount($res);
		$crazyRecursionLimiter = 999;
		$allRows = array();
		while ($crazyRecursionLimiter > 0 && $row = $this->getDataNext($res, $subCSSclass)) {
			if ($this->getCatNewsCount) {
				$row['newsCount'] = $this->getNewsCountForCategory($row['uid']);
			}

			//debug($this->tmpC++, '$this->tmpC ('.__CLASS__.'::'.__FUNCTION__.')', __LINE__, __FILE__, 3);


			$crazyRecursionLimiter--;
			$allRows[] = $row;
		}

		//debug($allRows, ' ('.__CLASS__.'::'.__FUNCTION__.')', __LINE__, __FILE__, 3);


		// Traverse the records:
		foreach ($allRows as $row) {
			$a++;

			$newID = $row['uid'];
			$this->tree[] = array(); // Reserve space.
			end($this->tree);
			$treeKey = key($this->tree); // Get the key for this space
			$LN = ($a == $c) ? 'blank' : 'line';

			// If records should be accumulated, do so
			if ($this->setRecs) {
				$this->recs[$row['uid']] = $row;
			}

			// Accumulate the id of the element in the internal arrays
			$this->ids[] = $idH[$row['uid']]['uid'] = $row['uid'];
			$this->ids_hierarchy[$depth][] = $row['uid'];

			// Make a recursive call to the next level
			if ($depth > 1 && $this->expandNext($newID)) {
				$nextCount = $this->getTree($newID, $depth - 1, $blankLineCode . ',' . $LN, $row['_SUBCSSCLASS']);
				if (count($this->buffer_idH)) {
					$idH[$row['uid']]['subrow'] = $this->buffer_idH;
				}
				$exp = 1; // Set "did expand" flag
			} else {
				$nextCount = $this->getCount($newID);
				$exp = 0; // Clear "did expand" flag
			}

			// Set HTML-icons, if any:
			if ($this->makeHTML) {
				$HTML = '';
				$HTML .= $this->PMicon($row, $a, $c, $nextCount, $exp);
				$HTML .= $this->wrapStop($this->getIcon($row), $row);
			}

			// Finally, add the row/HTML content to the ->tree array in the reserved key.
			$this->tree[$treeKey] = array('row' => $row, 'HTML' => $HTML, 'hasSub' => $nextCount && $this->expandNext($newID),
					'isFirst' => $a == 1, 'isLast' => false, 'invertedDepth' => $depth, 'blankLineCode' => $blankLineCode, 'bank' => $this->bank);
		}

		if ($a) {
			$this->tree[$treeKey]['isLast'] = true;
		}

		$this->getDataFree($res);
		$this->buffer_idH = $idH;
		return $c;
	}


	/**
	 * Compiles the HTML code for displaying the structure found inside the ->tree array
	 *
	 * @param	array		"tree-array" - if blank string, the internal ->tree array is used.
	 * @return	string		The HTML code for the tree
	 */
	function printTree($treeArr = '') {
		$titleLen = $this->titleLen;

		if (! is_array($treeArr)) {
			$treeArr = $this->tree;
		}

		$out = '
			<!-- TYPO3 tree structure. -->
			<ul class="tree" id="treeRoot">
		';

		// -- evaluate AJAX request
		// IE takes anchor as parameter
		$PM = t3lib_div::_GP('PM');

		if (($PMpos = strpos($PM, '#')) !== false) {
			$PM = substr($PM, 0, $PMpos);
		}
		$PM = explode('_', $PM);
		if (is_array($PM) && count($PM) == 4 && $this->useAjax) {

			if ($PM[1]) {
				$expandedPageUid = $PM[2];
				$ajaxOutput = '';
				$invertedDepthOfAjaxRequestedItem = 0; // We don't know yet. Will be set later.
				$doExpand = true;
			} else {
				$collapsedPageUid = $PM[2];
				$doCollapse = true;
			}
		}

		// we need to count the opened <ul>'s every time we dig into another level,
		// so we know how many we have to close when all children are done rendering
		$closeDepth = array();

		foreach ($treeArr as $v) {
			$classAttr = $v['row']['_CSSCLASS'];
			$uid = $v['row']['uid'];
			$idAttr = htmlspecialchars($this->domIdPrefix . $this->getId($v['row']) . '_' . $v['bank']);
			$itemHTML = '';
			$addStyle = '';

			// if this item is the start of a new level,
			// then a new level <ul> is needed, but not in ajax mode
			if ($v['isFirst'] && ! ($doCollapse) && ! ($doExpand && $expandedPageUid == $uid)) {
				$itemHTML = '<ul>';
			}

			// add CSS classes to the list item
			if ($v['hasSub']) {
				$classAttr .= ($classAttr ? ' ' : '') . 'expanded';
			}
			if ($v['isLast']) {
				$classAttr .= ($classAttr ? ' ' : '') . 'last';
				//				$addStyle = $this->addStyle;
			}
			if ($uid && $uid == $this->category) {
				$classAttr .= ($classAttr ? ' ' : '') . 'active';
			}

			$itemHTML .= '
				<li id="' . $idAttr . '"' . $addStyle . ($classAttr ? ' class="' . $classAttr . '"' : '') . '>' . $v['HTML'] . $this->wrapTitle($this->getTitleStr($v['row'], $titleLen), $v['row'], $v['bank']) . "\n";

			if (! $v['hasSub']) {
				$itemHTML .= '</li>';
			}

			// we have to remember if this is the last one
			// on level X so the last child on level X+1 closes the <ul>-tag
			if ($v['isLast'] && ! ($doExpand && $expandedPageUid == $uid)) {
				$closeDepth[$v['invertedDepth']] = 1;
			}

			// if this is the last one and does not have subitems, we need to close
			// the tree as long as the upper levels have last items too
			if ($v['isLast'] && ! $v['hasSub'] && ! $doCollapse && ! ($doExpand && $expandedPageUid == $uid)) {
				for ($i = $v['invertedDepth']; $closeDepth[$i] == 1; $i++) {
					$closeDepth[$i] = 0;
					$itemHTML .= '</ul></li>';
				}
			}
			// ajax request: collapse
			if ($doCollapse && $collapsedPageUid == $uid) {
				$this->ajaxStatus = true;
				return $itemHTML;
			}

			// ajax request: expand
			if ($doExpand && $expandedPageUid == $uid) {
				$ajaxOutput .= $itemHTML;
				$invertedDepthOfAjaxRequestedItem = $v['invertedDepth'];
			} elseif ($invertedDepthOfAjaxRequestedItem) {
				if ($v['invertedDepth'] < $invertedDepthOfAjaxRequestedItem) {
					$ajaxOutput .= $itemHTML;
				} else {
					$this->ajaxStatus = true;
					return $ajaxOutput;
				}
			}
			$out .= $itemHTML;
		}

		if ($ajaxOutput) {
			$this->ajaxStatus = true;
			return $ajaxOutput;
		}

		// finally close the first ul
		$out .= '</ul>';
		return $out;
	}


	/**
	 * Generate the plus/minus icon for the browsable tree.
	 *
	 * @param	array		record for the entry
	 * @param	integer		The current entry number
	 * @param	integer		The total number of entries. If equal to $a, a "bottom" element is returned.
	 * @param	integer		The number of sub-elements to the current element.
	 * @param	boolean		The element was expanded to render subelements if this flag is set.
	 * @return	string		Image tag with the plus/minus icon.
	 * @access private
	 * @see t3lib_pageTree::PMicon()
	 */
	function PMicon($row, $a, $c, $nextCount, $exp) {
		if ($this->expandable) {
			$PM = $nextCount ? ($exp ? 'minus' : 'plus') : 'join';
		} else {
			$PM = 'join';
		}

		$BTM = ($a == $c) ? 'bottom' : '';
		$icon = '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/ol/' . $PM . $BTM . '.gif', 'width="18" height="16"') . ' alt="" />';

		if ($nextCount) {
			$cmd = $this->bank . '_' . ($exp ? '0_' : '1_') . $row['uid'] . '_' . $this->treeName;
			$icon = $this->PMiconATagWrap($icon, $cmd, ! $exp);
		}
		return $icon;
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
	function PMiconATagWrap($icon, $cmd, $isExpand = true) {
		if ($this->thisScript && $this->expandable) {
			// activate dynamic ajax-based tree
			$js = htmlspecialchars('txttnewsM1js.load(\'' . $cmd . '\', ' . intval($isExpand) . ', this, \'' . intval($this->pageID) . '\');');
			return '<a class="pm" onclick="' . $js . '">' . $icon . '</a>';
		} else {
			return $icon;
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_categorytree.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_categorytree.php']);
}
?>