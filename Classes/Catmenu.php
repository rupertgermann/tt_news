<?php

namespace RG\TtNews;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2018 Rupert Germann <rupi@gmx.li>
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

use RG\TtNews\Helper\Helpers;
use RG\TtNews\Plugin\TtNews;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Utility\EidUtility;

/**
 *
 * renders the tt_news CATMENU content element
 *
 * @author Rupert Germann <rupi@gmx.li>
 */
class Catmenu
{
    /**
     * @var int
     */
    public $titleLen = 60;
    /**
     * @var FeTreeView
     */
    public $treeObj;
    /**
     * @var bool
     */
    public $mode = false;

    /**
     * @param TtNews $pObj
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function init(&$pObj)
    {
        $lConf = $pObj->conf['displayCatMenu.'];
        $this->treeObj = GeneralUtility::makeInstance(FeTreeView::class);
        $this->treeObj->tt_news_obj = &$pObj;
        $this->treeObj->category = $pObj->piVars_catSelection;
        $this->treeObj->table = 'tt_news_cat';
        $this->treeObj->init($pObj->SPaddWhere . $pObj->enableCatFields . $pObj->catlistWhere,
            $pObj->config['catOrderBy']);
        $this->treeObj->backPath = TYPO3_mainDir;
        $this->treeObj->parentField = 'parent_category';
        $this->treeObj->thisScript = 'index.php?eID=tt_news_catmenu';
        $this->treeObj->cObjUid = intval($pObj->cObj->data['uid']);
        $this->treeObj->fieldArray = array(
            'uid',
            'title',
            'title_lang_ol',
            'description',
            'image'
        ); // those fields will be filled to the array $this->treeObj->tree
        $this->treeObj->ext_IconMode = '1'; // no context menu on icons

        $expandable = $lConf['expandable'];
        if ($lConf['mode'] == 'ajaxtree') {
            $expandable = true;
            $this->treeObj->useAjax = true;
        }

        $this->treeObj->expandAll = 1;#$lConf['expandAll'];
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
        $nonRootMounts = false;
        foreach ($selcatArr as $catID) {
            $tmpR = $GLOBALS['TSFE']->sys_page->getRecordsByField('tt_news_cat', 'uid', $catID,
                $pObj->SPaddWhere . $pObj->enableCatFields . $pObj->catlistWhere);
            if (is_array($tmpR[0]) && !in_array($catID, $subcatArr)) {
                if ($tmpR[0]['parent_category'] > 0) {
                    $nonRootMounts = true;
                }
                $cMounts[] = $catID;
            }
        }
        if ($nonRootMounts) {
            $this->treeObj->MOUNTS = $cMounts;

        }
    }

    /**
     *
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function ajaxExpandCollapse()
    {
        $params = $this->initAjaxEnv();

        $this->init($params['tt_newsObj']);
        $this->treeObj->FE_USER = &$params['feUserObj'];
        $tree = $this->treeObj->getBrowsableTree();

        return $tree;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function initAjaxEnv()
    {
        $L = intval(GeneralUtility::_GP('L'));

        $idAndTarget = rawurldecode(GeneralUtility::_GP('id'));
        $idParts = GeneralUtility::trimExplode(' ', $idAndTarget, 1);
        $id = intval($idParts[0]);

        EidUtility::initTCA();
        /** @var TypoScriptFrontendController $GLOBALS['TSFE'] */
        $GLOBALS['TSFE'] = GeneralUtility::makeInstance(TypoScriptFrontendController::class,
            $GLOBALS['TYPO3_CONF_VARS'], $id, (int)GeneralUtility::_GP('type'));

        // don't cache ajax responses
        $GLOBALS['TSFE']->no_cache = true;
        $GLOBALS['TSFE']->connectToDB();
        $GLOBALS['TSFE']->initFEuser();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->getConfigArray();

        if ($L > 0) {
            $GLOBALS['TSFE']->settingLanguage();
            $GLOBALS['TSFE']->settingLocale();
        }

        $ajaxParams = array();

        $tt_newsObj = new TtNews();
        $tt_newsObj->helpers = new Helpers($tt_newsObj);
        $tt_newsObj->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $tt_newsObj->local_cObj = &$tt_newsObj->cObj;

        $cObjUid = intval(GeneralUtility::_GP('cObjUid'));
        $tt_newsObj->cObj->data = $GLOBALS['TSFE']->sys_page->checkRecord('tt_content', $cObjUid, 1);
        $tt_newsObj->pi_initPIflexForm();
        $tt_newsObj->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tt_news.'];

        // variables needed to get the newscount per category
        if (!$tt_newsObj->conf['dontUsePidList']) {
            $tt_newsObj->initPidList();
        }

        $tt_newsObj->enableFields = $tt_newsObj->getEnableFields('tt_news');
        $tt_newsObj->initCategoryVars();
        $tt_newsObj->initCatmenuEnv($tt_newsObj->conf['displayCatMenu.']);

        $ajaxParams['tt_newsObj'] = &$tt_newsObj;
        $ajaxParams['feUserObj'] = $GLOBALS['TSFE']->fe_user;

        return $ajaxParams;
    }
}
