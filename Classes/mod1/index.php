<?php

/*
 * Copyright notice
 *
 * (c) 2004-2018 Rupert Germann <rupi@gmx.li>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use RG\TtNews\Utility\IconFactory;

/**
 * Module 'News Admin' for the 'tt_news' extension.
 *
 *
 * $Id$
 *
 */
class tx_ttnews_module1 extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{
    public $pageinfo;
    /*
     * @var tx_ttnewscatmanager_treeView
     */
    public $treeObj;
    public $markers = [];
    public $docHeaderButtons = [];
    // list of selected category from GETvars extended by subcategories
    public $selectedCategories;
    public $useSubCategories = true;

    public $limit = 20;
    public $TSprop = [];
    public $fieldList = 'uid,title,datetime,archivedate,tstamp,category;author';
    public $permsCache = [];
    public $pidList = 0;
    public $storagePid = 0;

    // CALC_PERMS for the current page. Used for the module header.
    public $localCalcPerms = 0;
    // CALC_PERMS for the "general record storage page". Used for categories.
    public $grspCalcPerms = 0;
    // CALC_PERMS for the page with news articles (newArticlePid).
    public $newArticleCalcPerms = 0;

    public $excludeCats = [];
    public $includeCats = [];

    public $confArr;
    public $newArticlePid;
    public $isAdmin;
    public $script;
    public $mayUserEditCategories;
    public $mayUserEditArticles;
    public $singlePid;
    public $mData;
    public $current_sys_language;
    public $searchLevels;
    public $thumbs;
    public $showLimit;
    public $treeContent;
    public $listContent;
    public $pidChash;
    public $returnUrl;
    public $category;
    public $editablePagesList;
    public $pointer;
    public $search_field;
    public $catlistWhere;
    public $sPageIcon;

    /**
     * Main module action
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function mainAction(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ) {
        $GLOBALS['LANG']->includeLLFile('EXT:tt_news/mod1/locallang.xml');

        $GLOBALS['SOBE'] = $this;
        $this->init();
        $this->main();
        $response->getBody()->write($this->printContent());

        return $response;
    }

    /**
     * Initializes the Module
     *
     * @return    void
     */
    public function init()
    {
        if (!$this->MCONF['name']) {
            $this->MCONF = $GLOBALS['MCONF'];
            if (!$this->MCONF['name']) {
                $MCONF = '';
                require('conf.php');
                $this->MCONF = $MCONF;
            }
        }
        $this->isAdmin = $GLOBALS['BE_USER']->isAdmin();

        $this->id = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'));
        $this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);

        $this->modTSconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig(
            $this->id,
            'mod.' . $this->MCONF['name']
        );
        $this->TSprop = $this->modTSconfig['properties'];
        $this->confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);

        $tceTSC = [];
        if ($this->confArr['useStoragePid']) {
            $tceTSC = \TYPO3\CMS\Backend\Utility\BackendUtility::getTCEFORM_TSconfig(
                'tt_news_cat',
                ['pid' => $this->id]
            );
        }
        $this->storagePid = $tceTSC['_STORAGE_PID'] ? $tceTSC['_STORAGE_PID'] : $this->id;

        $localNewArticlePid = intval($this->TSprop['list.']['pidForNewArticles']);
        $this->newArticlePid = ($localNewArticlePid ?: $this->id);

        $this->script = 'mod.php?M=web_txttnewsM1';

        if (($localFieldList = $this->TSprop['list.']['fList'])) {
            $this->fieldList = $localFieldList;
        }

        // get pageinfo array for the current page
        $this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $this->localCalcPerms = $GLOBALS['BE_USER']->calcPerms($this->pageinfo);

        // get pageinfo array for the GRSP
        $grspPI = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->storagePid, $this->perms_clause);
        $this->grspCalcPerms = $GLOBALS['BE_USER']->calcPerms($grspPI);
        $this->mayUserEditCategories = $this->grspCalcPerms & 16;

        // get pageinfo array for newArticlePid
        $newArticlePidPI = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess(
            $this->newArticlePid,
            $this->perms_clause
        );
        $this->newArticleCalcPerms = $GLOBALS['BE_USER']->calcPerms($newArticlePidPI);
        $this->mayUserEditArticles = $this->newArticleCalcPerms & 16;

        $pagesTSC = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->id);
        if ($pagesTSC['tx_ttnews.']['singlePid']) {
            $this->singlePid = intval($pagesTSC['tx_ttnews.']['singlePid']);
        }

        $this->initCategories();

        $this->setPidList();
        $this->initPermsCache();
        if ($this->pidList) {
            $this->setEditablePages($this->pidList);
        }

        $this->menuConfig();
        $this->mData = $GLOBALS['BE_USER']->uc['moduleData']['web_txttnewsM1'];

        $this->current_sys_language = intval($this->MOD_SETTINGS['language']);
        $this->searchLevels = intval($this->MOD_SETTINGS['searchLevels']);
        $this->thumbs = intval($this->MOD_SETTINGS['showThumbs']);

        $localLimit = intval($this->MOD_SETTINGS['showLimit']);
        if ($localLimit) {
            $this->showLimit = $localLimit;
        } else {
            $this->showLimit = intval($this->TSprop['list.']['limit']);
        }

        $this->initGPvars();
    }

    /**
     * Main function of the module. Write the content to $this->content
     * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the
     * uid-number of the page clicked in the page tree
     *
     * @return    [type]        ...
     */
    public function main()
    {
        global $LANG;

        $this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Backend\Template\DocumentTemplate');
        $this->doc->backPath = $GLOBALS['BACK_PATH'];
        $this->doc->setModuleTemplate('EXT:tt_news/mod1/mod_ttnews_admin.html');
        $this->doc->docType = 'xhtml_trans';

        if (!$this->doc->moduleTemplate) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('cannot set moduleTemplate', 'tt_news', 2, [
                'backpath' => $this->doc->backPath,
                'filename from TBE_STYLES' => $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_ttnews_admin.html'],
                'full path' => $this->doc->backPath . $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_ttnews_admin.html']
            ]);
            $tfile = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('tt_news') . 'mod1/mod_ttnews_admin.html';
            $this->doc->moduleTemplate = @file_get_contents(PATH_site . $tfile);
        }

        // Access check!
        // The page will show only if there is a valid page and if this page may be viewed by the user

        $access = (is_array($this->pageinfo) ? 1 : 0);
        $this->markers['MOD_INFO'] = '';

        if ($this->id && $access) {
            // JavaScript
            $this->doc->JScode = $this->doc->wrapScriptTags('
				script_ended = 0;
				function jumpToUrl(URL)	{	//
					window.location.href = URL;
				}
			' . ($this->singlePid ?
                    '
			function openFePreview(URL) {
				previewWin=window.open(URL,\'newTYPO3frontendWindow\');
				previewWin.focus();
			} '
                    : '') . $this->doc->redirectUrls());

            $this->doc->postCode = $this->doc->wrapScriptTags('
					script_ended = 1;
				');
            $this->doc->inDocStylesArray['tt_news_mod1'] = '
				#ttnewsadmin-tree {
					float:left;
					overflow-x: auto;
					overflow-y: auto;
					width: 253px;
					border-right: 1px solid #ccc;
				}
				#ttnews-cat-tree { margin-bottom: 15px; }
				#ttnewsadmin-list {  padding: 0 10px 0 263px; }
				#togglesubcats { background:#ddd; padding: 2px; cursor: pointer; font-style:italic; }
				#newssubcats { background:#f8f9fa; padding: 2px; border:1px solid #ddd; }
				#resetcatselection { float:right; font-style:italic; }
				#ttnewsadmin-search {  padding: 0; margin:0; }
				#ttnewsadmin-search input {  margin: 0 3px; }

				span.hiddencat { color:#999; }
				span.list-cb { padding-right:15px;}

				table.typo3-dblist tr td.col-icon a {
					width: 18px;
					display: inline;
				}
				table.typo3-dblist tr td    {
					padding: 6px 10px;
				}
				div.ttnewsadmin-pagination {
					padding: 5px 0;
					white-space: nowrap;
				}
				div.ttnewsadmin-pagination img, div.ttnewsadmin-pagination span.pageIndicator {
					margin-right: 6px;
				}
				div.ttnewsadmin-pagination img {
					vertical-align: bottom;
					padding-bottom: 2px;
				}
				#ttnewsadmin-tree .checkbox {
				    display:inline;
				}
				#ttnews-cat-tree ul.tree {
				    margin:16px 0 0 0;
				}
				#ttnewsadmin-tree .icon-actions-document-new {
				    margin-right: 5px;
				}

			';

            // Render content:
            $this->moduleContent();
        } else {
            // If no access or if ID == zero
            $this->displayOverview();
        }
        $this->docHeaderButtons = $this->getHeaderButtons();
        $this->markers['FUNC_MENU'] = ''/*\TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])*/
        ;
        $this->markers['TREE'] = $this->treeContent;
        $this->markers['LIST'] = $this->listContent;
        $this->markers['CSH'] = $this->docHeaderButtons['csh'];
        $this->markers['LANG_MENU'] = $this->getLangMenu();
        $this->markers['PAGE_SELECT'] = $this->getPageSelector();

        // put it all together
        $this->content = $this->doc->startPage($LANG->getLL('title'));
        $this->content .= $this->doc->moduleBody($this->pageinfo, $this->docHeaderButtons, $this->markers);
        $this->content .= $this->doc->endPage();
        $this->content = $this->doc->insertStylesAndJS($this->content);

        if (count($this->permsCache)) {
            $GLOBALS['BE_USER']->setAndSaveSessionData('permsCache', [$this->pidChash => $this->permsCache]);
        }
    }

    /**
     * Prints out the module HTML
     */
    public function printContent(): string
    {
        return $this->content;
    }

    /*************************************************************************
     *
     *        Module content
     *
     ************************************************************************/

    /**
     * Generates the module content
     *
     * @return    void
     */
    public function moduleContent()
    {
        $error = false;

        $this->table = 'tt_news_cat';
        if ($this->confArr['useStoragePid']) {
            $catRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                'uid',
                'tt_news_cat',
                'pid=' . $this->storagePid . $this->catlistWhere . ' AND deleted=0'
            );

            if (empty($catRows)) {
                $error = $this->displayOverview();
            }
        }

        if (!$error) {
            $this->getPageRenderer()->addHeaderData('
                <script src="' . TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tt_news') . 'Resources/Public/JavaScript/compat/prototype/prototype.js" type="text/javascript"></script>
                <script src="' . $GLOBALS['BACK_PATH'] . TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tt_news') . 'Resources/Public/JavaScript/tt_news_mod1.js" type="text/javascript"></script>
                ');

            // fixme: throws JS errors, commented out
            //					$this->doc->getDragDropCode('tt_news_cat');
            //					$this->doc->postCode=$this->doc->wrapScriptTags('
            //							txttnewsM1js.registerDragDropHandlers();
            //					');
            $this->doc->getContextMenuCode();

            $this->treeContent = $this->displayCategoryTree();
            $this->listContent .= $this->displayNewsList();
        }
    }

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    public function displayOverview()
    {
        $tRows = [];
        $tRows[] = '<tr>
				<td colspan="2" valign="top"><p><img' . IconFactory::skinImg(
            'gfx/icon_note.gif',
                'width="18" height="16"'
        ) . ' title="" alt="" />
				' . $GLOBALS['LANG']->getLL('nothingfound') . '
				</p><br></td>
				</tr>';

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'pid,count(uid)',
            'tt_news_cat',
            'pid>=0' . $this->catlistWhere . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tt_news_cat'),
            'pid'
        );
        $list = [];
        while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
            $list[$row['pid']]['count'] = $row['count(uid)'];
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        $tRows[] = '
			<tr>
				<td class="c-headLine"><strong>' . $GLOBALS['LANG']->getLL('path') . '</strong></td>
				<td class="c-headLine"><strong>' . $GLOBALS['LANG']->getLL('categories') . '</strong></td>

			</tr>';

        foreach ($list as $pid => $stat) {
            $pa = $this->getPageInfoForOverview($pid);
            if ($pa['path']) {
                $tRows[] = '
					<tr class="bgColor4">
						<td><a href="' . \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl(
                    'web_txttnewsM1',
                        ['id' => $pid]
                ) . '">' . htmlspecialchars($pa['path']) . '</a></td>
						<td>' . htmlspecialchars($stat['count']) . '</td>

					</tr>';
            }
        }

        // Create overview
        $outputString = '<table border="0" cellpadding="1" cellspacing="2" id="typo3-page-stdlist">' . implode(
            '',
                $tRows
        ) . '</table>';

        // Add output:
        $this->markers['MOD_INFO'] = $outputString;

        return true;
    }

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    public function displayCategoryTree()
    {
        $content = '';
        $content .= $this->renderTreeCheckBoxes();
        $content .= $this->renderNewCatButton();

        $this->getTreeObj();

        return $content . '<div id="ttnews-cat-tree">' . $this->sPageIcon . $this->treeObj->getBrowsableTree() . '</div>';
    }

    protected function addWhere(): string
    {
        $addWhere = '';

        if ($this->confArr['useStoragePid']) {
            $addWhere .= ' AND tt_news_cat.pid=' . $this->storagePid;
        }

        if (!$this->mData['showHiddenCategories']) {
            $addWhere .= ' AND tt_news_cat.hidden=0';
        }

        return $addWhere;
    }

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    public function getTreeObj()
    {
        $addWhere = $this->addWhere();

        $treeOrderBy = $this->confArr['treeOrderBy'] ?: 'uid';

        if (!is_object($this->treeObj)) {
            $this->treeObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_ttnewscatmanager_treeView');
        }

        $urlparams = ['id' => $this->id];

        $this->treeObj->table = 'tt_news_cat';
        $this->treeObj->init($this->catlistWhere . $addWhere, $treeOrderBy);
        $this->treeObj->parentField = 'parent_category';
        $this->treeObj->thisScript = $this->script . '&id=' . $this->id;
        $this->treeObj->returnUrl = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl(
            'web_txttnewsM1',
            $urlparams
        );

        // those fields will be filled to the array $this->treeObj->tree
        $this->treeObj->fieldArray = ['uid', 'title', 'description', 'hidden', 'starttime', 'endtime', 'fe_group'];
        $this->treeObj->mayUserEditCategories = $this->mayUserEditCategories;
        $this->treeObj->title = $GLOBALS['LANG']->getLL('treeTitle');
        $this->treeObj->pageID = $this->id;
        $this->treeObj->storagePid = $this->storagePid;
        $this->treeObj->useStoragePid = $this->confArr['useStoragePid'];

        $this->treeObj->expandAll = $GLOBALS['SOBE']->MOD_SETTINGS['expandAll'];
        $this->treeObj->expandable = true;
        $this->treeObj->expandFirst = $this->TSprop['catmenu.']['expandFirst'];
        $this->treeObj->titleLen = 60;
        $this->treeObj->useAjax = true;
        $this->treeObj->showEditIcons = $this->mData['showEditIcons'];
        $this->treeObj->showHiddenCategories = $this->mData['showHiddenCategories'];
        $this->treeObj->category = $this->category;
        $this->treeObj->current_sys_language = $this->current_sys_language;

        // get selected categories from be user/group without subcategories
        $tmpsc = \RG\TtNews\Lib\tx_ttnews_div::getBeUserCatMounts(false);
        $beUserSelCatArr = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $tmpsc);
        $includeListArr = \RG\TtNews\Lib\tx_ttnews_div::getIncludeCatArray();
        $subcatArr = array_diff($includeListArr, $beUserSelCatArr);

        /**
         * TODO:
         * filter out double mounts
         */

        // get all selected category records from the current storagePid which are not 'root' categories
        // and add them as tree mounts. Subcategories of selected categories will be excluded.
        $cMounts = [];
        $nonRootMounts = false;
        foreach ($beUserSelCatArr as $catID) {
            $tmpR = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord(
                'tt_news_cat',
                $catID,
                'parent_category,hidden',
                $addWhere
            );
            if (is_array($tmpR) && !in_array($catID, $subcatArr)) {
                if ($tmpR['parent_category'] > 0) {
                    $nonRootMounts = true;
                    $this->sPageIcon = $this->getStoragePageIcon();
                }

                if ($this->mData['showHiddenCategories'] || $tmpR['hidden'] == 0) {
                    $cMounts[] = $catID;
                }
            }
        }

        if ($nonRootMounts) {
            $this->treeObj->MOUNTS = $cMounts;
        }
    }

    /**
     * returns the root element for a category tree: icon, title and pageID
     *
     * @return    [type]        ...
     */
    public function getStoragePageIcon()
    {
        if ($this->confArr['useStoragePid']) {
            $tmpt = $this->treeObj->table;
            $this->treeObj->table = 'pages';
            $rootRec = $this->treeObj->getRecord($this->storagePid);
            $icon = $this->treeObj->getIcon($rootRec);
            $this->treeObj->table = $tmpt;
            $pidLbl = sprintf(
                $GLOBALS['LANG']->sL('LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.treeSelect.pageTitleSuffix'),
                $this->storagePid
            );
        } else {
            $rootRec = $this->treeObj->getRootRecord($this->storagePid);
            $icon = $this->treeObj->getRootIcon($rootRec);
            $pidLbl = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.treeSelect.pageTitleSuffixNoGrsp');
        }

        $pidLbl = ' <span class="typo3-dimmed"><em>' . $pidLbl . '</em></span>';
        $hrefTitle = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/mod1/locallang.xml:showAllResetSel');

        return '<div style="margin: 2px 0 -5px 0;">'
            . $icon
            . '<a href="' . \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_txttnewsM1') . '&id=' . $this->id . '" title="' . $hrefTitle . '">' . $rootRec['title'] . '</a>'
            . $pidLbl
            . '</div>';
    }

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    public function displayNewsList($ajax = false)
    {
        $content = '';

        $this->initSubCategories();

        $table = 'tt_news';

        /* @var $dblist tx_ttnews_recordlist */
        $dblist = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_ttnews_recordlist');

        $urlparams = ['id' => $this->id];
        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('category') != '') {
            $urlparams['category'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('category');
        }
        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showThumbs') != '') {
            $urlparams['showThumbs'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showThumbs');
        }
        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('searchLevels') != '') {
            $urlparams['searchLevels'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('searchLevels');
        }
        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showLimit') != '') {
            $urlparams['showLimit'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showLimit');
        }
        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pointer') != '') {
            $urlparams['pointer'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pointer');
        }

        $dblist->backPath = $GLOBALS['BACK_PATH'];
        $dblist->script = $this->script;
        $dblist->doEdit = $this->mayUserEditArticles;
        $dblist->ext_CALC_PERMS = $this->newArticleCalcPerms;
        $dblist->perms_clause = $this->perms_clause;
        $dblist->agePrefixes = $GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears');
        $dblist->id = $this->id;
        $dblist->newRecPid = $this->newArticlePid;
        $dblist->singlePid = $this->singlePid;
        $dblist->selectedCategories = $this->selectedCategories;
        $dblist->category = $this->category;
        $dblist->returnUrl = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_txttnewsM1', $urlparams);
        $dblist->excludeCats = $this->excludeCats;
        $dblist->includeCats = $this->includeCats;
        $dblist->isAdmin = $this->isAdmin;
        $dblist->current_sys_language = $this->current_sys_language;
        $dblist->showOnlyEditable = $this->mData['showOnlyEditable'];
        $dblist->pidList = $this->pidList;
        $dblist->editablePagesList = $this->editablePagesList;
        $dblist->searchFields = $this->TSprop['list.']['searchFields'];

        $dblist->start($this->id, $table, $this->pointer, $this->search_field, $this->searchLevels, $this->showLimit);

        $externalTables[$table][0]['fList'] = $this->fieldList;
        $externalTables[$table][0]['icon'] = $this->TSprop['list.']['icon'];

        $dblist->externalTables = $externalTables;
        $dblist->no_noWrap = true;
        $dblist->lTSprop = $this->TSprop['list.'];
        $dblist->thumbs = $this->thumbs;
        $dblist->pObj = &$this;

        $dblist->generateList();

        if (is_array($this->TSprop['list.']['show.']) && $this->TSprop['list.']['show.']['search']) {
            $search = $this->displaySearch($dblist->listURL($this->id, false));
            $content .= '<div style="float:right;">' . $search . '</div>';
        }

        $content .= $this->renderListCheckBoxes($ajax);
        $content .= $this->getListHeaderMsg($dblist);
        $content .= $dblist->HTMLcode;

        $content = '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">' . $content . '</form>';

        return '<div id="ttnewslist">' . $content . '</div>';
    }

    /*************************************************************************
     *
     *        AJAX functions
     *
     ************************************************************************/

    /**
     * [Describe function...]
     *
     * @param     [type]        $$params: ...
     * @param     [type]        $ajaxObj: ...
     *
     * @return    [type]        ...
     */
    public function ajaxExpandCollapse($params, &$ajaxObj)
    {
        $this->init();
        $this->getTreeObj();
        $tree = $this->treeObj->getBrowsableTree();

        if (!$this->treeObj->ajaxStatus) {
            $ajaxObj->setError($tree);
        } else {
            $ajaxObj->addContent('tree', $tree);
        }
    }

    /**
     * [Describe function...]
     *
     * @param     [type]        $$params: ...
     * @param     [type]        $ajaxObj: ...
     *
     * @return    [type]        ...
     */
    public function ajaxLoadList($params, &$ajaxObj)
    {
        $this->processAjaxRequestConstruct();
        $this->init();
        $list = $this->displayNewsList(true);
        $ajaxObj->addContent('ttnewslist', $list);
    }

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    public function processAjaxRequestConstruct()
    {
        global $SOBE;

        // Create a new anonymous object:
        $SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_ttnews_module1');
        // Create an instance of the document template object
        $SOBE->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Backend\Template\DocumentTemplate');
        $SOBE->doc->backPath = $GLOBALS['BACK_PATH'];
        $SOBE->doc->docType = 'xhtml_trans';
    }

    /*************************************************************************
     *
     *        GUI Elements
     *
     ************************************************************************/

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    public function getListHeaderMsg(&$dblist): string
    {
        global $LANG;

        $noCatSelMsg = false;
        if (!$this->selectedCategories) {
            if ($this->TSprop['list.']['noListWithoutCatSelection']) {
                $content = '<img' . IconFactory::skinImg(
                    'gfx/icon_note.gif',
                        'width="18" height="16"'
                ) . ' title="" alt="" />' . $LANG->getLL('selectCategory');
                $noCatSelMsg = true;
            } else {
                $content = $LANG->getLL('showingAll');
            }
        } else {
            $content = $this->getListHeaderMsgForSelectedCategories($LANG);
        }

        if ($dblist->totalItems == 0) {
            $content .= $this->getNoResultsMsg($dblist, $noCatSelMsg);
        }

        return '<div style="padding:5px 0;">' . $content . '</div>';
    }

    public function displaySearch($url): string
    {
        // Table with the search box:
        return '<form action="' . htmlspecialchars($url) . '" method="post">
				<!--
					Search box:
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="ttnewsadmin-search">
					<tr>
						<td>' . $GLOBALS['LANG']->sL(
            'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.enterSearchString',
                1
        ) . '<input type="text" name="search_field" value="' . htmlspecialchars($this->search_field) . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(10) . ' /></td>
						<td>' . $GLOBALS['LANG']->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.showRecords',
                1
                ) . ':<input type="text" name="SET[showLimit]" value="' . htmlspecialchars($this->showLimit ? $this->showLimit : '') . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . ' /></td>
						<td><input type="submit" name="search" value="' . $GLOBALS['LANG']->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.search',
                1
                ) . '" /></td>

					</tr>
				</table>
			</form>';
    }

    public function getNoResultsMsg(&$listObj, $noCatSelMsg)
    {
        $content = '';
        $tRows = [];
        if (!$noCatSelMsg) {
            $tRows[] = '<tr>
					<td valign="top"><p><img' . IconFactory::skinImg('gfx/icon_note.gif', 'width="18" height="16"') . ' title="" alt="" />
					' . $GLOBALS['LANG']->getLL('noNewsFound') . '
					</p></td>
					</tr>';
        }

        if ($this->mayUserEditArticles) {
            $tRows[] = '<tr>
					<td valign="top"><div style="padding:10px 0;">' . $listObj->getNewRecordButton('tt_news', true) . '</div></td>
					</tr>';
        }

        return $content . '<table border="0" cellpadding="1" cellspacing="2" id="typo3-page-stdlist">' . implode(
            '',
                $tRows
        ) . '</table>';
    }

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    public function renderTreeCheckBoxes()
    {
        $show = [];
        if (is_array($this->TSprop['catmenu.']['show.'])) {
            $show = $this->TSprop['catmenu.']['show.'];
        }
        $allowedCbNames = ['expandAll', 'showHiddenCategories'];

        if ($this->mayUserEditCategories) {
            $allowedCbNames[] = 'showEditIcons';
        }

        $params = $this->getLinkParams();
        $out = [];
        foreach ($allowedCbNames as $n) {
            if ((bool)$show['cb_' . $n]) {
                $out[] = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck(
                    $params,
                    'SET[' . $n . ']',
                        $this->MOD_SETTINGS[$n],
                    '',
                    '',
                    'id="cb-' . $n . '"'
                ) .
                    ' <label for="cb-' . $n . '">' . $GLOBALS['LANG']->getLL($n, 1) . '</label>';
            }
        }

        return '<div>' . implode('<br />', $out) . '</div>';
    }

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    public function renderListCheckBoxes($ajax = false)
    {
        $show = [];
        if (is_array($this->TSprop['list.']['show.'])) {
            $show = $this->TSprop['list.']['show.'];
        }
        $allowedCbNames = [];
        if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->fieldList, 'image')) {
            $allowedCbNames[] = 'showThumbs';
        }
        if (!$this->isAdmin) {
            $allowedCbNames[] = 'showOnlyEditable';
        }

        $params = $this->getLinkParams();
        $out = [];
        foreach ($allowedCbNames as $n) {
            if ((bool)$show['cb_' . $n]) {
                $out[] = '<span class="list-cb">' .
                    \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck(
                        $params,
                        'SET[' . $n . ']',
                        $this->MOD_SETTINGS[$n],
                        $ajax ? 'mod.php' : '',
                        '',
                        'id="cb-' . $n . '"'
                    ) .
                    ' <label for="cb-' . $n . '">' . $GLOBALS['LANG']->getLL($n, 1) . '</label></span>';
            }
        }

        return '<div>' . implode('', $out) . '</div>';
    }

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    public function renderNewCatButton()
    {
        $show = [];
        $button = '';
        if (is_array($this->TSprop['catmenu.']['show.'])) {
            $show = $this->TSprop['catmenu.']['show.'];
        }
        if ($this->mayUserEditCategories && (bool)$show['btn_newCategory']) {
            $params = '&edit[tt_news_cat][' . $this->storagePid . ']=new';
            $onclick = htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick(
                $params,
                $GLOBALS['BACK_PATH'],
                $this->returnUrl
            ));
            /**
             * @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory
             */
            $iconFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(IconFactory::class);
            $button = '<a href="#" onclick="' . $onclick . '">' .
                $iconFactory->getIcon('actions-document-new')->render() .
                $GLOBALS['LANG']->getLL('createCategory') .
                '</a>';
        }

        return '<div style="padding:5px 0;">' . $button . '</div>';
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @return    array        all available buttons as an assoc. array
     */
    public function getHeaderButtons()
    {
        global $LANG;

        $buttons = [
            'csh' => '',
            'view' => '',
            'edit' => '',
            'record_list' => '',
            'level_up' => '',
            'reload' => '',
            'shortcut' => '',
            'back' => '',
            'csv' => '',
            'export' => ''
        ];

        $backPath = $GLOBALS['BACK_PATH'];

        if (isset($this->id)) {
            if ($GLOBALS['BE_USER']->check('modules', 'web_list')) {
                $href = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_list', [
                    'id' => $this->pageinfo['uid'],
                    'returnUrl' => \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')
                ]);

                $buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '">' .
                    '<img' . IconFactory::skinImg(
                        'gfx/list.gif',
                        'width="11" height="11"'
                    ) . ' title="' . $LANG->sL(
                            'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.showList',
                        1
                        ) . '" alt="" />' .
                    '</a>';
            }

            // View
            $buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick(
                $this->id,
                    $backPath,
                \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($this->id)
            )) . '">' .
                '<img' . IconFactory::skinImg('gfx/zoom.gif') . ' title="' . $LANG->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.showPage',
                    1
                ) . '" alt="" />' .
                '</a>';

            // If edit permissions are set (see class.t3lib_userauthgroup.php)
            if ($this->localCalcPerms & 2 && !empty($this->id)) {
                // Edit
                $params = '&edit[pages][' . $this->pageinfo['uid'] . ']=edit';
                $buttons['edit'] = '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick(
                    $params,
                        $backPath,
                    -1
                )) . '">' .
                    '<img' . IconFactory::skinImg('gfx/edit2.gif') . ' title="' . $LANG->getLL(
                        'editPage',
                        1
                    ) . '" alt="" />' .
                    '</a>';
            }

            // Reload
            $buttons['reload'] = '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript()) . '">' .
                '<img' . IconFactory::skinImg('gfx/refresh_n.gif') . ' title="' . $LANG->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.reload',
                    1
                ) . '" alt="" />' .
                '</a>';

            // Shortcut
            if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
                $buttons['shortcut'] = $this->doc->makeShortcutIcon(
                    'id, showThumbs, pointer, table, search_field, searchLevels, showLimit, sortField, sortRev',
                    implode(',', array_keys($this->MOD_MENU)),
                    'web_txttnewsM1'
                );
            }

            // Back
            if ($this->returnUrl) {
                $buttons['back'] = '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisUrl(
                    $this->returnUrl,
                        ['id' => $this->id]
                )) . '" class="typo3-goBack">' .
                    '<img' . IconFactory::skinImg('gfx/goback.gif') . ' title="' . $LANG->sL(
                        'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.goBack',
                        1
                    ) . '" alt="" />' .
                    '</a>';
            }
        }

        return $buttons;
    }

    public function getLangMenu()
    {
        $menu = '';
        if (count($this->MOD_MENU['language']) > 1) {
            $menu = $GLOBALS['LANG']->sL(
                'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
                    1
            ) .
                \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(
                    $this->id,
                    'SET[language]',
                    $this->current_sys_language,
                    $this->MOD_MENU['language']
                );
        }

        return $menu;
    }

    public function getPageSelector()
    {
        $menu = '';
        if (count($this->MOD_MENU['searchLevels']) > 1) {
            $menu = $GLOBALS['LANG']->getLL('enterSearchLevels') .
                \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(
                    $this->id,
                    'SET[searchLevels]',
                    $this->searchLevels,
                    $this->MOD_MENU['searchLevels']
                );
        }

        return $menu;
    }

    /*************************************************************************
     *
     *        Internal helper functions
     *
     ************************************************************************/

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    public function setPidList()
    {
        if ($this->isAdmin) {
            return;
        }

        // get allowed pages
        $webmounts = $GLOBALS['BE_USER']->returnWebmounts();
        if (!is_array($webmounts)) {
            return;
        }

        $pidList = '';
        foreach ($webmounts as $mount) {
            $pidList .= ',' . $mount . ',' . $this->getSubPages($mount);
        }

        $pidList = \TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList($pidList);
        $this->pidList = ($pidList ? $pidList : 0);
    }

    /**
     * [Describe function...]
     *
     * @param     [type]        $pidlist: ...
     *
     * @return    [type]        ...
     */
    public function setEditablePages($pidlist)
    {
        $pids = explode(',', $pidlist);
        $editPids = [];

        foreach ($pids as $pid) {
            if (($this->checkPageAccess($pid))) {
                $editPids[] = $pid;
            }
        }

        $this->editablePagesList = implode(',', $editPids);
    }

    /**
     * [Describe function...]
     *
     * @param     [type]        $pages: ...
     * @param     [type]        $cc: ...
     *
     * @return    [type]        ...
     */
    public function getSubPages($pages, $cc = 0)
    {
        $pArr = [];
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'uid',
            'pages',
            'pages.pid IN (' . $pages . ') AND pages.deleted=0 AND ' . $this->perms_clause
        );

        while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
            $cc++;

            //check if max. number of sub pages reached
            //TODO: Create config var or at least a class constant instead of hard coding here.
            if ($cc > 10000) {
                break;
            }

            $subpages = $this->getSubPages($row['uid'], $cc);
            $subpages = $subpages ? ',' . $subpages : '';
            $pArr[] = $row['uid'] . $subpages;
        }

        return implode(',', $pArr);
    }

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    public function initGPvars()
    {
        $this->pointer = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(
            \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pointer'),
            0,
            100000
        );
        $this->category = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('category'));
        $this->search_field = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('search_field');
    }

    /**
     * Adds items to the ->MOD_MENU array. Used for the function menu selector.
     *
     * @return    void
     */
    public function menuConfig()
    {
        $this->MOD_MENU = [
            'function' => [
                '1' => $GLOBALS['LANG']->getLL('function1'),
            ],
            'showEditIcons' => 0,
            'expandAll' => 0,
            'showOnlyEditable' => 0,
            'showHiddenCategories' => 0,
            'searchLevels' => [
                -1 => $GLOBALS['LANG']->getLL('allPages'),
                0 => $GLOBALS['LANG']->getLL('thisPage'),
                1 => $GLOBALS['LANG']->getLL('oneLevel'),
                2 => $GLOBALS['LANG']->getLL('twoLevels'),
                3 => $GLOBALS['LANG']->getLL('threeLevels'),
                4 => $GLOBALS['LANG']->getLL('fourLevels')

            ],
            'showThumbs' => 1,
            'showLimit' => 0,
            'language' => [
                0 => $GLOBALS['LANG']->getLL('defaultLangLabel')
            ]

        ];
        $this->initLanguageMenu();

        $this->MOD_MENU['function'] = $this->mergeExternalItems(
            $this->MCONF['name'],
            'function',
            $this->MOD_MENU['function']
        );
        $this->MOD_MENU['function'] = \TYPO3\CMS\Backend\Utility\BackendUtility::unsetMenuItems(
            $this->modTSconfig['properties'],
            $this->MOD_MENU['function'],
            'menu.function'
        );

        $this->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData(
            $this->MOD_MENU,
            \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET'),
            $this->MCONF['name'],
            $this->modMenu_type,
            $this->modMenu_dontValidateList,
            $this->modMenu_setDefaultList
        );
    }

    public function initLanguageMenu()
    {
        if ($this->isAdmin) {
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'sys_language.*',
                'sys_language',
                'sys_language.hidden=0',
                '',
                'sys_language.title'
            );
        } else {
            $exQ = \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('pages_language_overlay');
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'sys_language.*',
                'pages_language_overlay,sys_language',
                'pages_language_overlay.sys_language_uid=sys_language.uid AND pages_language_overlay.pid IN (' . $this->pidList . ')' . $exQ,
                'pages_language_overlay.sys_language_uid,sys_language.uid,sys_language.pid,sys_language.tstamp,sys_language.hidden,sys_language.title,sys_language.static_lang_isocode,sys_language.flag',
                'sys_language.title'
            );
        }

        while (($lrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
            if ($GLOBALS['BE_USER']->checkLanguageAccess($lrow['uid'])) {
                $this->MOD_MENU['language'][$lrow['uid']] = ($lrow['hidden'] ? '(' . $lrow['title'] . ')' : $lrow['title']);
            }
        }

        // add "all" language
        $this->MOD_MENU['language'][-1] = $GLOBALS['LANG']->getLL('allLanguages');

        // Setting alternative default label:
        $dl = trim($this->TSprop['defaultLanguageLabel']);
        if ($dl && isset($this->MOD_MENU['language'][0])) {
            $this->MOD_MENU['language'][0] = $dl;
        }
    }

    public function initCategories()
    {
        if ($this->isAdmin) {
            return;
        }

        // get include/exclude items
        if (($excludeList = $GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.excludeList'))) {
            $this->excludeCats = $this->posIntExplode($excludeList);
        }

        $this->includeCats = \RG\TtNews\Lib\tx_ttnews_div::getIncludeCatArray();
        $this->catlistWhere = \RG\TtNews\Lib\tx_ttnews_div::getCatlistWhere();
    }

    public function posIntExplode($list)
    {
        $arr = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $list);
        $out = [];

        foreach ($arr as $v) {
            if ($v > 0) {
                $out[] = $v;
            }
        }

        return $out;
    }

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    public function initSubCategories()
    {
        if ($this->useSubCategories && $this->category) {
            $subcats = \RG\TtNews\Lib\tx_ttnews_div::getSubCategories($this->category);
            $this->selectedCategories = \TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList($this->category . ($subcats ? ',' . $subcats : ''));
        } else {
            $this->selectedCategories = $this->category;
        }
    }

    /**
     * Checks if a PID value is accessible and if so returns the path for the page.
     * Processing is cached so many calls to the function are OK.
     *
     * @param    int        Page id for check
     *
     * @return    string        Page path of PID if accessible. otherwise zero.
     */
    public function getPageInfoForOverview($pid)
    {
        $out = [];
        $localPageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($pid, $this->perms_clause);
        $out['path'] = $localPageinfo['_thePath'];

        $calcPerms = $GLOBALS['BE_USER']->calcPerms($localPageinfo);
        if (($calcPerms & 16)) {
            $out['edit'] = true;
        }

        return $out;
    }

    public function checkPageAccess($pid)
    {
        if (isset($this->permsCache[$pid])) {
            return $this->permsCache[$pid];
        }

        $calcPerms = $GLOBALS['BE_USER']->calcPerms(\TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess(
            $pid,
            $this->perms_clause
        ));
        if (($calcPerms & 16)) {
            $this->permsCache[$pid] = true;
        } else {
            $this->permsCache[$pid] = false;
        }

        return $this->permsCache[$pid];
    }

    public function initPermsCache()
    {
        if ($this->isAdmin) {
            return;
        }

        $this->pidChash = md5($this->pidList);
        $pc = $GLOBALS['BE_USER']->getSessionData('permsCache');
        if (is_array($pc) && is_array($pc[$this->pidChash])) {
            $this->permsCache = $pc[$this->pidChash];
        }
    }

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    public function getLinkParams()
    {
        $params = ['id' => $this->id];

        if ($this->category) {
            $params['category'] = $this->category;
        }

        return $params;
    }

    /**
     * @param $LANG
     *
     * @return string
     */
    protected function getListHeaderMsgForSelectedCategories($LANG): string
    {
        $table = 'tt_news_cat';
        $row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $this->category);
        $title = '<strong>' . \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $row) . '</strong>';
        $content = '<div id="newscatsmsg">' . $LANG->getLL('showingOnlyCat') . $title . '</div>';

        if ($this->useSubCategories && ($subCats = \TYPO3\CMS\Core\Utility\GeneralUtility::rmFromList(
            $this->category,
                $this->selectedCategories
        ))) {
            $scRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                'uid,title,hidden',
                $table,
                'uid IN (' . $subCats . ')' . !$this->mData['showHiddenCategories'] ? ' AND hidden=0' : ''
            );
            $scTitles = [];
            foreach ($scRows as $scRow) {
                $recTitle = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $scRow);
                if ($scRow['hidden']) {
                    $recTitle = '<span class="hiddencat">' . $recTitle . '</span>';
                }
                $scTitles[] = $recTitle;
            }

            if (count($scTitles)) {
                $showLbl = $LANG->getLL('showSubcatgories');
                $hideLbl = $LANG->getLL('hideSubcatgories');
                $btnID = 'togglesubcats';
                $elID = 'newssubcats';
                $onclick = htmlspecialchars('if
						($(\'' . $elID . '\').visible()) {
							$(\'' . $elID . '\').hide();
							$(\'' . $btnID . '\').update(' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($showLbl) . ');
						} else {
							$(\'' . $elID . '\').show();$(\'' . $btnID . '\').update(' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($hideLbl) . ');}');
                $content .= '<div id="' . $btnID . '" onclick="' . $onclick . '">' . $showLbl . '</div>';
                $content .= '<div id="' . $elID . '" style="display:none;">' . implode(', ', $scTitles) . '</div>';
            }
        }

        return $content;
    }
}

/**
 * [Describe function...]
 *
 */
class tx_ttnewscatmanager_treeView extends tx_ttnews_categorytree
{
    public $TCEforms_itemFormElName = '';
    public $TCEforms_nonSelectableItemsArray = [];

    public $returnUrl;
    public $showEditIcons;
    public $pageID;
    public $storagePid;
    public $useStoragePid;
    public $mayUserEditCategories;
    public $LL;

    /**
     * [Describe function...]
     *
     * @param     [type]        $icon: ...
     * @param     [type]        $row: ...
     *
     * @return    [type]        ...
     */
    public function wrapIcon($icon, $row)
    {
        $theIcon = $this->addTagAttributes($icon, $this->titleAttrib . '="' . $this->getTitleAttrib($row) . '"');

        if ($row['uid'] > 0 && !isset($row['doktype'])) {
            // no clickmenu for pages
            $theIcon = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapClickMenuOnIcon(
                $theIcon,
                'tt_news_cat_CM',
                $row['uid'],
                0,
                '&bank=' . $this->bank
            );
            $theIcon = '<span class="dragIcon" id="dragIconID_' . $row['uid'] . '">' . $theIcon . '</span>';
        } else {
            $theIcon = '<span class="dragIcon" id="dragIconID_0">' . $theIcon . '</span>';
        }

        return $theIcon;
    }

    /**
     * wraps the record titles in the tree with links or not depending on if they are in the
     * TCEforms_nonSelectableItemsArray.
     *
     * @param    string $title : the title
     * @param    array  $v     : an array with uid and title of the current item.
     *
     * @return    string        the wrapped title
     */
    public function wrapTitle($title, $v, $bank = 0)
    {

        // TODO: language overlay

        if ($v['uid'] > 0) {
            $hrefTitle = htmlentities('[id=' . $v['uid'] . '] ' . $v['description']);
            $js = htmlspecialchars('txttnewsM1js.loadList(\'' . $v['uid'] . '\', $(\'ttnewslist\'), \'' . intval($this->pageID) . '\');');
            $out = '<a href="#" onclick="' . $js . '" title="' . $hrefTitle . '">' . $title . '</a>';

            // Wrap title in a drag/drop span.
            $out = '<span class="dragTitle" id="dragTitleID_' . $v['uid'] . '">' . $out . '</span>';
            if ($this->showEditIcons) {
                $out .= $this->makeControl('tt_news_cat', $v);
            }
        } else {
            $grsp = '';
            if ($this->storagePid != $this->pageID) {
                $grsp = ' GRSP';
            }
            if ($this->useStoragePid) {
                $pidLbl = sprintf(
                    $GLOBALS['LANG']->sL('LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.treeSelect.pageTitleSuffix'),
                    $this->storagePid . $grsp
                );
            } else {
                $pidLbl = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.treeSelect.pageTitleSuffixNoGrsp');
            }
            $pidLbl = ' <span class="typo3-dimmed"><em>' . $pidLbl . '</em></span>';
            $hrefTitle = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/mod1/locallang.xml:showAllResetSel');

            $out = '<span class="dragTitle" id="dragTitleID_0">
						<a href="' . \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_txttnewsM1') . '&id=' . $this->pageID . '" title="' . $hrefTitle . '">' . $title . '</a>
					</span>' . $pidLbl;
        }

        return $out;
    }

    /**
     * Creates the control panel for a single record in the listing.
     *
     * @param    string        The table
     * @param    array         The record for which to make the control panel.
     *
     * @return    string        HTML table with the control panel (unless disabled)
     */
    public function makeControl($table, $row)
    {
        global $TCA, $LANG;

        // Initialize:
        $cells = [];
        // "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
        if ($this->mayUserEditCategories) {
            $params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
            $cells[] = '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick(
                $params,
                    $this->backPath,
                $this->returnUrl
            )) . '">' .
                '<img' . IconFactory::skinImg(
                    'gfx/edit2' . (!$TCA[$table]['ctrl']['readOnly'] ? '' : '_d') . '.gif',
                    'width="11" height="12"'
                ) . ' title="' . $LANG->getLLL('edit', $this->LL) . '" alt="" />' .
                '</a>';
        }

        // "Hide/Unhide" links:
        $hiddenField = $TCA[$table]['ctrl']['enablecolumns']['disabled'];
        if ($this->mayUserEditCategories && $hiddenField && $TCA[$table]['columns'][$hiddenField] &&
            (!$TCA[$table]['columns'][$hiddenField]['exclude'] || $GLOBALS['BE_USER']->check(
                'non_exclude_fields',
                    $table . ':' . $hiddenField
            ))
        ) {
            /**
             * @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory
             */
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            if ($row[$hiddenField]) {
                $params = '&data[' . $table . '][' . $row['uid'] . '][' . $hiddenField . ']=0';
                $cells[] = '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $this->issueCommand(
                    $params,
                            $this->returnUrl
                ) . '\');') . '">' .
                    $iconFactory->getIcon('actions-edit-unhide')->render() .
                    '</a>';
            } else {
                $params = '&data[' . $table . '][' . $row['uid'] . '][' . $hiddenField . ']=1';
                $cells[] = '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $this->issueCommand(
                    $params,
                            $this->returnUrl
                ) . '\');') . '">' .
                    $iconFactory->getIcon('actions-edit-hide')->render() .
                    '</a>';
            }
        }

        return '
				<!-- CONTROL PANEL: ' . $table . ':' . $row['uid'] . ' -->
				<span style="padding:0 0 0 7px;">' . implode('', $cells) . '</span>';
    }

    /**
     * [Describe function...]
     *
     * @param    [type]        $params: ...
     * @param    [type]        $rUrl: ...
     *
     * @return   string
     */
    public function issueCommand($params, $rUrl = ''): string
    {
        $rUrl = $rUrl ?: \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI');

        $urlParameters = \TYPO3\CMS\Core\Utility\GeneralUtility::explodeUrl2Array($params, true);
        $urlParameters['vC'] = \rawurlencode($GLOBALS['BE_USER']->veriCode());
        $urlParameters['prErr'] = '1';
        $urlParameters['uPT'] = '1';

        $url = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('tce_db', $urlParameters);
        $url .= '&redirect=' . ($rUrl == -1 ? "'+T3_THIS_LOCATION+'" : rawurlencode($rUrl));

        return $url;
    }
}
