<?php

namespace RG\TtNews\Tree;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2020 Rupert Germann <rupi@gmx.li>
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
 * @author     Rupert Germann <rupi@gmx.li>
 */

use Doctrine\DBAL\DBALException;
use RG\TtNews\Plugin\TtNews;
use RG\TtNews\Utility\Div;
use RG\TtNews\Utility\IconFactory;
use TYPO3\CMS\Backend\Tree\View\AbstractTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * extend class t3lib_treeview to change function wrapTitle().
 */
class Categorytree extends AbstractTreeView
{
    /**
     * @var array
     */
    public $categoryCountCache = [];
    /**
     * @var bool
     */
    public $cacheHit = false;

    /**
     * @var
     */
    public $expandable;
    /**
     * @var TtNews
     */
    public $tt_news_obj;
    /**
     * @var
     */
    public $newsSelConf;
    /**
     * @var
     */
    public $category;
    /**
     * @var
     */
    public $storagePid;
    /**
     * @var
     */
    public $useStoragePid;
    /**
     * @var
     */
    public $getCatNewsCount;
    /**
     * @var
     */
    public $useAjax;
    /**
     * @var
     */
    public $titleLen;
    /**
     * @var
     */
    public $pageID;
    /**
     * @var string
     */
    protected $addStyle;
    /**
     * @var bool
     */
    protected $ajaxStatus;
    public $domIdPrefix;
    public $stored;
    public $MOUNTS;
    public $expandFirst;
    public $addSelfId;
    public $setRecs;
    public $expandAll = false;

    public function init($clause = '', $orderByFields = '')
    {
        // Setting BE_USER by default
        $this->BE_USER = $GLOBALS['BE_USER'];
        // Setting clause
        if ($clause) {
            $this->clause = $clause;
        }
        if ($orderByFields) {
            $this->orderByFields = $orderByFields;
        }
        if (!is_array($this->MOUNTS)) {
            // Dummy
            $this->MOUNTS = [0 => 0];
        }
        // Sets the tree name which is used to identify the tree, used for JavaScript and other things
        $this->treeName = str_replace('_', '', (string)($this->treeName ?: $this->table));
    }

    /**
     * @return string
     */
    protected function handleCache()
    {
        $storeKey = '';

        if ($this->tt_news_obj->cache_categoryCount) {
            $storeKey = md5(serialize([
                $this->stored,
                $this->MOUNTS,
                $this->newsSelConf['pidInList'] . $this->newsSelConf['where'] . $this->tt_news_obj->enableFields . $this->clause,
            ]));

            $tmpCCC = $this->tt_news_obj->cache->get($storeKey);
            if ($tmpCCC) {
                $this->categoryCountCache = unserialize($tmpCCC);
                $this->cacheHit = true;
            }
        }

        return $storeKey;
    }

    /**
     * Will create and return the HTML code for a browsable tree
     * Is based on the mounts found in the internal array ->MOUNTS (set in the constructor)
     *
     * @param bool $groupByPages
     *
     * @return    string        HTML code for the browsable tree
     * @throws DBALException
     */
    public function getBrowsableTree($groupByPages = false)
    {
        // Get stored tree structure AND updating it if needed according to incoming PM GET var.
        $this->initializePositionSaving();

        // Init done:
        $treeArr = [];
        $tmpClause = $this->clause;
        $savedTable = $this->table;

        $storeKey = $this->handleCache();
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
            $cmd = $this->bank . '_' . ($isOpen ? '0_' : '1_') . $uid . '_' . $this->treeName;

            $icon = '<img' . IconFactory::skinImg('ol/' . ($isOpen ? 'minus' : 'plus') . 'only.gif') . ' alt="" />';
            if ($this->expandable && !$this->expandFirst) {
                $firstHtml = $this->PMiconATagWrap($icon, $cmd);
            } else {
                $firstHtml = $icon;
            }

            $this->addStyle = '';

            // Preparing rootRec for the mount
            if ($groupByPages) {
                $this->table = 'pages';
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
                    $rootRec = $this->getRootRecord();
                    $firstHtml .= $this->getRootIcon($rootRec);
                }
            }

            if ($groupByPages) {
                $this->clause = $tmpClause . ' AND tt_news_cat.pid=' . $uid;
                $rootRec['uid'] = 0;
            }

            // restore $this->table
            $this->table = $savedTable;

            if (!is_array($rootRec)) {
                continue;
            }

            // In case it was swapped inside getRecord due to workspaces.
            $uid = $rootRec['uid'];

            // Add the root of the mount to ->tree
            $this->tree[] = [
                'HTML' => $firstHtml,
                'row' => $rootRec,
                'bank' => $this->bank,
                'hasSub' => true,
                'invertedDepth' => 1000,
            ];

            // If the mount is expanded, go down:
            if ($isOpen) {
                // Set depth:
                if ($this->addSelfId) {
                    $this->ids[] = $uid;
                }
                $this->getNewsCategoryTree($uid, 999, '');
            }
            // Add tree:
            $treeArr = array_merge($treeArr, $this->tree);
        }

        if ($this->tt_news_obj->cache_categoryCount && count($this->categoryCountCache) && !$this->cacheHit) {
            $this->tt_news_obj->cache->set($storeKey, serialize($this->categoryCountCache), ['categoryCounts']);
        }

        return $this->printTree($treeArr);
    }

    /**
     * @param $catID
     *
     * @return bool|int|mixed
     * @throws DBALException
     */
    protected function getNewsCountForCategory($catID)
    {
        $sum = false;

        if (isset($this->categoryCountCache[$catID])) {
            $sum = $this->categoryCountCache[$catID];
        }

        if ($sum !== false) {
            return $sum;
        }
        $hash = '';
        if ($this->tt_news_obj->cache_categoryCount) {
            $hash = sha1(serialize($catID . $this->newsSelConf['pidInList'] . $this->newsSelConf['where'] . $this->tt_news_obj->enableFields . $this->clause));
            $sum = $this->tt_news_obj->cache->get($hash);
        }

        if ($sum === false) {
            $result = [];
            $result['sum'] = 0;

            $news_clause = '';
            if (is_object($this->tt_news_obj)) {
                $news_clause .= ' AND ' . $this->newsSelConf['where'] . $this->tt_news_obj->enableFields;
                if ($this->newsSelConf['pidInList']) {
                    $news_clause .= ' AND tt_news.pid IN (' . $this->newsSelConf['pidInList'] . ') ';
                }
            }

            Div::getNewsCountForSubcategory($result, $catID, $news_clause, $this->clause);
            $sum = $result['sum'];
        }
        $this->categoryCountCache[$catID] = (int)$sum;
        if ($this->tt_news_obj->cache_categoryCount) {
            $this->tt_news_obj->cache->set($hash, (string)$sum, ['categoryCounts']);
        }

        return $sum;
    }

    /**
     * Fetches the data for the tree
     *
     *
     * @param        $uid
     * @param int    $depth
     * @param string $blankLineCode
     *
     * @return    int        The count of items on the level
     * @throws DBALException
     */
    protected function getNewsCategoryTree($uid, $depth = 999, $blankLineCode = '')
    {
        $treeKey = null;
        // Buffer for id hierarchy is reset:
        $this->buffer_idH = [];

        // Init vars
        $depth = (int)$depth;
        $HTML = '';
        $a = 0;

        $res = $this->getDataInit($uid);
        $c = $this->getDataCount($res);
        $crazyRecursionLimiter = 999;
        $allRows = [];
        while ($crazyRecursionLimiter > 0 && $row = $this->getDataNext($res)) {
            if ($this->getCatNewsCount) {
                $row['newsCount'] = $this->getNewsCountForCategory($row['uid']);
            }

            $crazyRecursionLimiter--;
            $allRows[] = $row;
        }

        // Traverse the records:
        foreach ($allRows as $row) {
            $a++;

            $newID = $row['uid'];
            $this->tree[] = [];
            $treeKey = array_key_last($this->tree); // Get the key for this space
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
                $nextCount = $this->getNewsCategoryTree($newID, $depth - 1, $blankLineCode . ',' . $LN);
                if (!empty($this->buffer_idH)) {
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
            $this->tree[$treeKey] = [
                'row' => $row,
                'HTML' => $HTML,
                'hasSub' => $nextCount && $this->expandNext($newID),
                'isFirst' => $a == 1,
                'isLast' => false,
                'invertedDepth' => $depth,
                'blankLineCode' => $blankLineCode,
                'bank' => $this->bank,
            ];
        }

        if ($a) {
            $this->tree[$treeKey]['isLast'] = true;
        }

        $this->getDataFree($res);
        $this->buffer_idH = $idH;

        return $c;
    }

    /**
     * @param $itemHTML
     * @param $v
     * @param $classAttr
     * @param $uid
     * @param $idAttr
     * @param $titleLen
     */
    protected function addItem(&$itemHTML, $v, $classAttr, $uid, $idAttr, $titleLen)
    {
        // add CSS classes to the list item
        if ($v['hasSub']) {
            $classAttr .= ($classAttr ? ' ' : '') . 'expanded';
        }
        if ($v['isLast']) {
            $classAttr .= ($classAttr ? ' ' : '') . 'last';
        }
        if ($uid && $uid == $this->category) {
            $classAttr .= ($classAttr ? ' ' : '') . 'active';
        }

        $itemHTML .= '
				<li id="' . $idAttr . '"' . ($classAttr ? ' class="' . $classAttr . '"' : '') . '>' . $v['HTML'] . $this->wrapTitle($this->getTitleStr(
            $v['row'],
            $titleLen
        ), $v['row'], $v['bank']) . "\n";

        if (!$v['hasSub']) {
            $itemHTML .= '</li>';
        }
    }

    /**
     * @param $itemHTML
     * @param $v
     * @param $doCollapse
     * @param $doExpand
     * @param $expandedPageUid
     * @param $uid
     * @param $closeDepth
     */
    protected function closeTree(&$itemHTML, $v, $doCollapse, $doExpand, $expandedPageUid, $uid, &$closeDepth)
    {
        // if this is the last one and does not have subitems, we need to close
        // the tree as long as the upper levels have last items too
        if ($v['isLast'] && !$v['hasSub'] && !$doCollapse && !($doExpand && $expandedPageUid == $uid)) {
            for ($i = $v['invertedDepth']; $closeDepth[$i] == 1; $i++) {
                $closeDepth[$i] = 0;
                $itemHTML .= '</ul></li>';
            }
        }
    }

    /**
     * @param $doExpand
     * @param $expandedPageUid
     * @param $collapsedPageUid
     * @param $doCollapse
     * @param $ajaxOutput
     * @param $invertedDepthOfAjaxRequestedItem
     */
    protected function evaluateAJAXRequest(
        &$doExpand,
        &$expandedPageUid,
        &$collapsedPageUid,
        &$doCollapse,
        &$ajaxOutput,
        &$invertedDepthOfAjaxRequestedItem
    ) {
        $PM = GeneralUtility::_GP('PM');

        if (($PMpos = strpos((string)$PM, '#')) !== false) {
            $PM = substr((string)$PM, 0, $PMpos);
        }
        $PM = explode('_', (string)$PM);
        if (is_array($PM) && count($PM) == 4 && $this->useAjax) {
            if ($PM[1] == 1) {
                $expandedPageUid = $PM[2];
                $ajaxOutput = '';
                // We don't know yet. Will be set later.
                $invertedDepthOfAjaxRequestedItem = 0;
                $doExpand = true;
            } else {
                $collapsedPageUid = $PM[2];
                $doCollapse = true;
            }
        }
    }

    /**
     * Compiles the HTML code for displaying the structure found inside the ->tree array
     *
     * @param string $treeArr
     *
     * @return    string        The HTML code for the tree
     */
    public function printTree($treeArr = '')
    {
        $doExpand = false;
        $expandedPageUid = 0;
        $collapsedPageUid = 0;
        $doCollapse = false;
        $ajaxOutput = '';
        $invertedDepthOfAjaxRequestedItem = 0;

        if (!is_array($treeArr)) {
            $treeArr = $this->tree;
        }

        $out = '
			<!-- TYPO3 tree structure. -->
			<ul class="tree" id="treeRoot">
		';

        $this->evaluateAJAXRequest(
            $doExpand,
            $expandedPageUid,
            $collapsedPageUid,
            $doCollapse,
            $ajaxOutput,
            $invertedDepthOfAjaxRequestedItem
        );

        // we need to count the opened <ul>'s every time we dig into another level,
        // so we know how many we have to close when all children are done rendering
        $closeDepth = [];
        foreach ($treeArr as $v) {
            $uid = $v['row']['uid'];
            $itemHTML = '';

            // if this item is the start of a new level,
            // then a new level <ul> is needed, but not in ajax mode
            if ($v['isFirst'] && !($doCollapse) && !($doExpand && $expandedPageUid == $uid)) {
                $itemHTML = '<ul>';
            }

            $this->addItem(
                $itemHTML,
                $v,
                $v['row']['_CSSCLASS'],
                $uid,
                htmlspecialchars($this->domIdPrefix . $this->getId($v['row']) . '_' . $v['bank']),
                $this->titleLen
            );

            // we have to remember if this is the last one
            // on level X so the last child on level X+1 closes the <ul>-tag
            if ($v['isLast'] && !($doExpand && $expandedPageUid == $uid)) {
                $closeDepth[$v['invertedDepth']] = 1;
            }

            $this->closeTree($itemHTML, $v, $doCollapse, $doExpand, $expandedPageUid, $uid, $closeDepth);

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
        return $out . '</ul>';
    }

    /**
     * Generate the plus/minus icon for the browsable tree.
     *
     * @param    array          record for the entry
     * @param    int        The current entry number
     * @param    int        The total number of entries. If equal to $a, a "bottom" element is returned.
     * @param    int        The number of sub-elements to the current element.
     * @param    bool        The element was expanded to render subelements if this flag is set.
     *
     * @return    string        Image tag with the plus/minus icon.
     */
    public function PMicon($row, $a, $c, $nextCount, $exp)
    {
        if ($this->expandable) {
            $PM = $nextCount ? ($exp ? 'minus' : 'plus') : 'join';
        } else {
            $PM = 'join';
        }

        $BTM = ($a == $c) ? 'bottom' : '';
        /**
         * @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory
         */
        $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
        $icon = $iconFactory->getIcon('ttnews-gfx-ol-' . $PM . $BTM, Icon::SIZE_SMALL)->render();

        if ($nextCount) {
            $cmd = $this->bank . '_' . ($exp ? '0_' : '1_') . $row['uid'] . '_' . $this->treeName;
            $icon = $this->PMiconATagWrap($icon, $cmd, !$exp);
        }

        return $icon;
    }

    /**
     * Wrap the plus/minus icon in a link
     *
     * @param      $icon
     * @param      $cmd
     * @param bool $isExpand
     *
     * @return    string        Link-wrapped input string
     */
    public function PMiconATagWrap($icon, $cmd, $isExpand = true)
    {
        if ($this->thisScript && $this->expandable) {
            return '<a class="pm pmiconatag" data-params="' . $cmd . '" data-isexpand="' . (int)$isExpand . '" data-pid="' . (int)($this->pageID) . '">' . $icon . '</a>';
        }
        return $icon;
    }
    /**
     * Returns the record for a uid.
     * For tables: Looks up the record in the database.
     * For arrays: Returns the fake record for uid id.
     *
     * @param int $uid UID to look up
     * @return array The record
     */
    public function getRecord($uid)
    {
        return BackendUtility::getRecordWSOL($this->table, $uid);
    }

    /**
     * Adds a red "+" to the input string, $str, if the field "php_tree_stop" in the $row (pages) is set
     *
     * @param string $str Input string, like a page title for the tree
     * @param array $row record row with "php_tree_stop" field
     * @return string Modified string
     * @internal
     */
    public function wrapStop($str, $row)
    {
        if ($row['php_tree_stop']) {
            $str .= '<a href="' . htmlspecialchars((string)GeneralUtility::linkThisScript(['setTempDBmount' => $row['uid']])) . '" class="text-danger">+</a> ';
        }
        return $str;
    }
    /**
     * Returns the id from the record (typ. uid)
     *
     * @param array $row Record array
     * @return int The "uid" field value.
     */
    public function getId($row)
    {
        return $row['uid'];
    }

    /**
     * Wrapping the image tag, $icon, for the row, $row (except for mount points)
     *
     * @param string $icon The image tag for the icon
     * @param array $row The row for the current element
     * @return string The processed icon input value.
     * @internal
     */
    public function wrapIcon($icon, $row)
    {
        return $icon;
    }

    /**
     * Returns TRUE/FALSE if the next level for $id should be expanded - based on
     * data in $this->stored[][] and ->expandAll flag.
     * Extending parent function
     *
     * @param int $id Record id/key
     * @return bool
     * @internal
     * @see \TYPO3\CMS\Backend\Tree\View\PageTreeView::expandNext()
     */
    public function expandNext($id)
    {
        return !empty($this->stored[$this->bank][$id]) || $this->expandAll;
    }
}
