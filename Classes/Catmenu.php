<?php
namespace RG\TtNews;
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
 * class.tx_ttnews_catmenu.php
 *
 * renders the tt_news CATMENU content element - inspired by class.webpagetree.php which renders the pagetree in the TYPO3 BackEnd
 *
 * $Id$
 *
 * @author Rupert Germann <rupi@gmx.li>
 */

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tt_news') . 'lib/class.tx_ttnews_categorytree.php');

class Catmenu
{
    var $titleLen = 60;
    var $treeObj;
    var $mode = false;

    /**
     * @param tx_ttnews $pObj
     */
    function init(&$pObj)
    {
        $lConf = $pObj->conf['displayCatMenu.'];
        $this->treeObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_ttnews_FEtreeview');
        $this->treeObj->tt_news_obj = &$pObj;
        $this->treeObj->category = $pObj->piVars_catSelection;
        $this->treeObj->table = 'tt_news_cat';
        $this->treeObj->init($pObj->SPaddWhere . $pObj->enableCatFields . $pObj->catlistWhere, $pObj->config['catOrderBy']);
        $this->treeObj->backPath = TYPO3_mainDir;
        $this->treeObj->parentField = 'parent_category';
        $this->treeObj->thisScript = 'index.php?eID=tt_news';
        $this->treeObj->cObjUid = intval($pObj->cObj->data['uid']);
        $this->treeObj->fieldArray = array('uid', 'title', 'title_lang_ol', 'description', 'image'); // those fields will be filled to the array $this->treeObj->tree
        $this->treeObj->ext_IconMode = '1'; // no context menu on icons

        $expandable = $lConf['expandable'];
        if ($lConf['mode'] == 'ajaxtree') {
            $expandable = true;
            $this->treeObj->useAjax = true;
        }

        $this->treeObj->expandAll = $lConf['expandAll'];
        $this->treeObj->expandable = $expandable;
        $this->treeObj->expandFirst = $lConf['expandFirst'];
        $this->treeObj->titleLen = $this->titleLen;

        $this->treeObj->getCatNewsCount = $lConf['showNewsCountForCategories'];
        $this->treeObj->newsSelConf = $pObj->getSelectConf('');
        $this->treeObj->title = $pObj->pi_getLL('catmenuHeader');

        $allcatArr = explode(',', $pObj->catExclusive);
        $selcatArr = explode(',', $pObj->actuallySelectedCategories);
        $subcatArr = array_diff($allcatArr, $selcatArr);

        // get all selected category records from the current storagePid which are not 'root' categories
        // and add them as tree mounts. Subcategories of selected categories will be excluded.
        $cMounts = array();
        $nonRootMounts = FALSE;
        foreach ($selcatArr as $catID) {
            $tmpR = $GLOBALS['TSFE']->sys_page->getRecordsByField('tt_news_cat', 'uid', $catID, $pObj->SPaddWhere . $pObj->enableCatFields . $pObj->catlistWhere);
            if (is_array($tmpR[0]) && !in_array($catID, $subcatArr)) {
                if ($tmpR[0]['parent_category'] > 0) {
                    $nonRootMounts = TRUE;
                }
                $cMounts[] = $catID;
            }
        }
        if ($nonRootMounts) {
            $this->treeObj->MOUNTS = $cMounts;

        }
    }

    /**
     * [Describe function...]
     *
     * @param    [type]        $$params: ...
     * @param    [type]        $ajaxObj: ...
     * @return    [type]        ...
     */
    function ajaxExpandCollapse(&$params, &$ajaxObj)
    {

        $this->init($params['tt_newsObj']);
        $this->treeObj->FE_USER = &$params['feUserObj'];
        $tree = $this->treeObj->getBrowsableTree();
        if (!$this->treeObj->ajaxStatus) {
            $ajaxObj->setError($tree);
        } else {
            $ajaxObj->addContent('tree', $tree);
        }
    }
}
