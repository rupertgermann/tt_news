<?php

namespace RG\TtNews\Module;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2018 Rupert Germann <rg@rgdata.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Doctrine\DBAL\DBALException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RG\TtNews\Database\Database;
use RG\TtNews\Utility\Div;
use RG\TtNews\Utility\IconFactory;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageTreeView;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Module 'News Admin' for the 'tt_news' extension.
 *
 *
 * $Id$
 *
 * @author        Rupert Germann <rg@rgdata.de>
 * @package       TYPO3
 * @subpackage    tt_news
 */
class NewsAdminModule extends BaseScriptClass
{
    /**
     * @var
     */
    public $pageinfo;

    /**
     * @var CategoryManager
     */
    public $treeObj;
    /**
     * @var array
     */
    public $markers = array();
    /**
     * @var array
     */
    public $docHeaderButtons = array();
    // list of selected category from GETpublics extended by subcategories
    /**
     * @var
     */
    public $selectedCategories;
    /**
     * @var bool
     */
    public $useSubCategories = true;

    /**
     * @var int
     */
    public $limit = 20;
    /**
     * @var array
     */
    public $TSprop = array();
    /**
     * @var string
     */
    public $fieldList = 'uid,title,datetime,archivedate,tstamp,category;author';
    /**
     * @var array
     */
    public $permsCache = array();
    /**
     * @var int
     */
    public $pidList = 0;
    /**
     * @var int
     */
    public $storagePid = 0;

    // CALC_PERMS for the current page. Used for the module header.
    /**
     * @var int
     */
    public $localCalcPerms = 0;
    // CALC_PERMS for the "general record storage page". Used for categories.
    /**
     * @var int
     */
    public $grspCalcPerms = 0;
    // CALC_PERMS for the page with news articles (newArticlePid).
    /**
     * @var int
     */
    public $newArticleCalcPerms = 0;

    /**
     * @var array
     */
    public $excludeCats = array();
    /**
     * @var array
     */
    public $includeCats = array();

    /**
     * @var
     */
    public $confArr;
    /**
     * @var
     */
    public $newArticlePid;
    /**
     * @var
     */
    public $isAdmin;
    /**
     * @var
     */
    public $script;
    /**
     * @var
     */
    public $mayUserEditCategories;
    /**
     * @var
     */
    public $mayUserEditArticles;
    /**
     * @var
     */
    public $singlePid;
    /**
     * @var
     */
    public $mData;
    /**
     * @var
     */
    public $current_sys_language;
    /**
     * @var
     */
    public $searchLevels;
    /**
     * @var
     */
    public $thumbs;
    /**
     * @var
     */
    public $showLimit;
    /**
     * @var
     */
    public $treeContent;
    /**
     * @var
     */
    public $listContent;
    /**
     * @var
     */
    public $pidChash;
    /**
     * @var
     */
    public $returnUrl;
    /**
     * @var
     */
    public $category;
    /**
     * @var
     */
    public $editablePagesList;
    /**
     * @var
     */
    public $pointer;
    /**
     * @var
     */
    public $search_field;
    /**
     * @var
     */
    public $catlistWhere;
    /**
     * @var
     */
    public $sPageIcon;


    /**
     * @var array
     */
    public $MCONF = [
        'name' => 'web_txttnewsM1',
    ];

    /**
     * @var \TYPO3\CMS\Core\Imaging\IconFactory
     */
    protected $iconFactory;

    /**
     * NewsAdminModule constructor.
     * call to parent constructor ist deliberately missing to maintain compatibility with TYPO3 8.7
     */
    public function __construct()
    {
        parent::__construct();
        $GLOBALS['BACK_PATH'] = '../';
        $GLOBALS['SOBE'] = $this;

        $this->getLanguageService()->includeLLFile('EXT:tt_news/Classes/Module/locallang.xml');

        $this->getPageRenderer()->addCssFile('EXT:tt_news/Resources/Public/Css/BackendModule.css', 'stylesheet',
            'screen');
        $this->iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);

    }

    /**
     * Main module action
     *
     * @param ServerRequestInterface $request the current request
     *
     * @return ResponseInterface the response with the content
     * @throws DBALException
     */
    public function mainAction(
        ServerRequestInterface $request
    ) {
        $this->init();
        $this->main();
        return new HtmlResponse($this->printContent());
    }

    /**
     * Initializes the Module
     *
     * @return    void
     * @throws DBALException
     */
    public function init()
    {
        if (!$this->MCONF['name']) {
            $this->MCONF = $GLOBALS['MCONF'];

        }
        $this->isAdmin = $this->getBackendUser()->isAdmin();

        $this->id = intval(GeneralUtility::_GP('id'));
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(1);

        $this->TSprop = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_txttnewsM1.'] ?? [];
        $this->confArr = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tt_news'];

        $tceTSC = array();
        if ($this->confArr['useStoragePid']) {
            $tceTSC = BackendUtility::getTCEFORM_TSconfig('tt_news_cat',
                array('pid' => $this->id));
        }
        $this->storagePid = $tceTSC['_STORAGE_PID'] ? $tceTSC['_STORAGE_PID'] : $this->id;

        $localNewArticlePid = intval($this->TSprop['list.']['pidForNewArticles']);
        $this->newArticlePid = ($localNewArticlePid ?: $this->id);

        $this->script = 'mod.php?M=web_txttnewsM1';

        if (($localFieldList = $this->TSprop['list.']['fList'])) {
            $this->fieldList = $localFieldList;
        }

        // get pageinfo array for the current page
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $this->localCalcPerms = $this->getBackendUser()->calcPerms($this->pageinfo);

        // get pageinfo array for the GRSP
        $grspPI = BackendUtility::readPageAccess($this->storagePid, $this->perms_clause);
        $this->grspCalcPerms = $this->getBackendUser()->calcPerms($grspPI);
        $this->mayUserEditCategories = $this->grspCalcPerms & 16;

        // get pageinfo array for newArticlePid
        $newArticlePidPI = BackendUtility::readPageAccess($this->newArticlePid,
            $this->perms_clause);
        $this->newArticleCalcPerms = $this->getBackendUser()->calcPerms($newArticlePidPI);
        $this->mayUserEditArticles = $this->newArticleCalcPerms & 16;

        $pagesTSC = BackendUtility::getPagesTSconfig($this->id);
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
        $this->mData = $this->getBackendUser()->uc['moduleData']['web_txttnewsM1'];


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
     * @throws DBALException
     */
    public function main()
    {

        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->doc->backPath = $GLOBALS['BACK_PATH'];
        $this->doc->setModuleTemplate('EXT:tt_news/Classes/Module/mod_ttnews_admin.html');
        $this->doc->docType = 'xhtml_trans';

        if (!$this->doc->moduleTemplate) {
            $tfile = PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath('tt_news')) . 'Classes/Module/mod_ttnews_admin.html';
            $this->doc->moduleTemplate = @file_get_contents(Environment::getPublicPath() . '/' . $tfile);
        }

        // Access check!
        // The page will show only if there is a valid page and if this page may be viewed by the user

        $access = (is_array($this->pageinfo) ? 1 : 0);
        $this->markers['MOD_INFO'] = '';


        if ($this->id && $access) {
            // JavaScript
            $this->doc->JScode = GeneralUtility::wrapJS('
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

            $this->doc->postCode = GeneralUtility::wrapJS('
					script_ended = 1;
				');


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
        $this->content = $this->doc->startPage($this->getLanguageService()->getLL('title'));
        $this->content .= $this->doc->moduleBody($this->pageinfo, $this->docHeaderButtons, $this->markers);
        $this->content .= $this->doc->endPage();
        $this->content = $this->doc->insertStylesAndJS($this->content);

        if (count($this->permsCache)) {
            $this->getBackendUser()->setAndSaveSessionData('permsCache', array($this->pidChash => $this->permsCache));
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
     * @throws DBALException
     * @throws SiteNotFoundException
     */
    public function moduleContent()
    {
        $error = false;

        $this->table = 'tt_news_cat';
        if ($this->confArr['useStoragePid']) {
            $catRows = Database::getInstance()->exec_SELECTgetRows('uid', 'tt_news_cat',
                'pid=' . $this->storagePid . $this->catlistWhere . ' AND deleted=0');

            if (empty($catRows)) {
                $error = $this->displayOverview();
            }
        }

        if (!$error) {


            // fixme: throws JS errors, commented out
//					$this->doc->getDragDropCode('tt_news_cat');
//					$this->doc->postCode=GeneralUtility::wrapJS('
//							txttnewsM1js.registerDragDropHandlers();
//					');
//            $this->getPageRenderer()->loadJquery();
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/TtNews/NewsBackendModule');

            $this->treeContent = $this->displayCategoryTree();
            $this->listContent .= $this->displayNewsList();
        }
    }

    /**
     * @return bool
     * @throws DBALException
     */
    public function displayOverview()
    {
        $tRows = array();
        $tRows[] = '<tr>
				<td colspan="2" valign="top"><p><img' . IconFactory::skinImg('icon_note.gif',
                'width="18" height="16"') . ' title="" alt="" />
				' . $this->getLanguageService()->getLL('nothingfound') . '
				</p><br></td>
				</tr>';

        $res = Database::getInstance()->exec_SELECTquery(
            'pid,count(uid)',
            'tt_news_cat',
            'pid>=0' . $this->catlistWhere . ' AND deleted = 0',
            'pid'
        );
        $list = array();
        while (($row = Database::getInstance()->sql_fetch_assoc($res))) {
            $list[$row['pid']]['count'] = $row['count(uid)'];
        }

        $tRows[] = '
			<tr>
				<td class="c-headLine"><strong>' . $this->getLanguageService()->getLL('path') . '</strong></td>
				<td class="c-headLine"><strong>' . $this->getLanguageService()->getLL('categories') . '</strong></td>

			</tr>';

        foreach ($list as $pid => $stat) {
            $pa = $this->getPageInfoForOverview($pid);
            if ($pa['path']) {
                $tRows[] = '
					<tr class="bgColor4">
						<td><a href="' . LegacyBackendUtility::getModuleUrl('web_txttnewsM1',
                        array('id' => $pid)) . '">' . htmlspecialchars($pa['path']) . '</a></td>
						<td>' . htmlspecialchars($stat['count']) . '</td>

					</tr>';
            }
        }

        // Create overview
        $outputString = '<table border="0" cellpadding="1" cellspacing="2" id="typo3-page-stdlist">' . implode('',
                $tRows) . '</table>';

        // Add output:
        $this->markers['MOD_INFO'] = $outputString;

        return true;

    }



    /**
     * @return string
     * @throws DBALException
     */
    public function displayCategoryTree()
    {
        $content = '';
        $content .= $this->renderTreeCheckBoxes();
        $content .= $this->renderNewCatButton();

        $this->getTreeObj();

        return $content . '<div id="ttnews-cat-tree">' . $this->sPageIcon . $this->treeObj->getBrowsableTree() . '</div>';
    }

    /**
     * @return string
     */
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
     * @throws DBALException
     */
    public function getTreeObj()
    {
        $addWhere = $this->addWhere();

        $treeOrderBy = $this->confArr['treeOrderBy'] ?: 'uid';

        if (!is_object($this->treeObj)) {
            $this->treeObj = GeneralUtility::makeInstance(CategoryManager::class);
        }

        $urlparams = array('id' => $this->id);

        $this->treeObj->table = 'tt_news_cat';
        $this->treeObj->init($this->catlistWhere . $addWhere, $treeOrderBy);
        $this->treeObj->parentField = 'parent_category';
        $this->treeObj->thisScript = $this->script . '&id=' . $this->id;
        $this->treeObj->returnUrl = LegacyBackendUtility::getModuleUrl('web_txttnewsM1',
            $urlparams);

        // those fields will be filled to the array $this->treeObj->tree
        $this->treeObj->fieldArray = array('uid', 'title', 'description', 'hidden', 'starttime', 'endtime', 'fe_group');
        $this->treeObj->mayUserEditCategories = $this->mayUserEditCategories;
        $this->treeObj->title = $this->getLanguageService()->getLL('treeTitle');
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
        $tmpsc = Div::getBeUserCatMounts(false);
        $beUserSelCatArr = GeneralUtility::intExplode(',', $tmpsc);
        $includeListArr = Div::getIncludeCatArray();
        $subcatArr = array_diff($includeListArr, $beUserSelCatArr);

        /**
         * TODO:
         * filter out double mounts
         */

        // get all selected category records from the current storagePid which are not 'root' categories
        // and add them as tree mounts. Subcategories of selected categories will be excluded.
        $cMounts = array();
        $nonRootMounts = false;
        foreach ($beUserSelCatArr as $catID) {
            $tmpR = BackendUtility::getRecord('tt_news_cat', $catID,
                'parent_category,hidden', $addWhere);
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
     */
    protected function getStoragePageIcon()
    {
        if ($this->confArr['useStoragePid']) {
            $tmpt = $this->treeObj->table;
            $this->treeObj->table = 'pages';
            $rootRec = $this->treeObj->getRecord($this->storagePid);
            $icon = $this->treeObj->getIcon($rootRec);
            $this->treeObj->table = $tmpt;
            $pidLbl = sprintf($this->getLanguageService()->sL('LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.treeSelect.pageTitleSuffix'),
                $this->storagePid);
        } else {
            $rootRec = $this->treeObj->getRootRecord($this->storagePid);
            $icon = $this->treeObj->getRootIcon($rootRec);
            $pidLbl = $this->getLanguageService()->sL('LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.treeSelect.pageTitleSuffixNoGrsp');
        }

        $pidLbl = ' <span class="typo3-dimmed"><em>' . $pidLbl . '</em></span>';
        $hrefTitle = $this->getLanguageService()->sL('LLL:EXT:tt_news/Classes/Module/locallang.xml:showAllResetSel');

        return '<div style="margin: 2px 0 -5px 0;">'
            . $icon
            . '<a href="' . LegacyBackendUtility::getModuleUrl('web_txttnewsM1') . '&id=' . $this->id . '" title="' . $hrefTitle . '">' . $rootRec['title'] . '</a>'
            . $pidLbl
            . '</div>';
    }

    /**
     * @param bool $ajax
     *
     * @return string
     * @throws DBALException
     * @throws SiteNotFoundException
     */
    public function displayNewsList($ajax = false)
    {
        $content = '';

        $this->initSubCategories();

        $table = 'tt_news';

        /* @var $dblist NewsRecordlist */
        $dblist = GeneralUtility::makeInstance(NewsRecordlist::class);

        $urlparams = array('id' => $this->id);
        if (GeneralUtility::_GP('category') != '') {
            $urlparams['category'] = (int)GeneralUtility::_GP('category');
        }
        if (GeneralUtility::_GP('showThumbs') != '') {
            $urlparams['showThumbs'] = (int)GeneralUtility::_GP('showThumbs');
        }
        if (GeneralUtility::_GP('searchLevels') != '') {
            $urlparams['searchLevels'] = (int)GeneralUtility::_GP('searchLevels');
        }
        if (GeneralUtility::_GP('showLimit') != '') {
            $urlparams['showLimit'] = (int)GeneralUtility::_GP('showLimit');
        }
        if (GeneralUtility::_GP('pointer') != '') {
            $urlparams['pointer'] = (int)GeneralUtility::_GP('pointer');
        }

        $dblist->backPath = $GLOBALS['BACK_PATH'];
        $dblist->script = $this->script;
        $dblist->doEdit = $this->mayUserEditArticles;
        $dblist->ext_CALC_PERMS = $this->newArticleCalcPerms;
        $dblist->perms_clause = $this->perms_clause;
        $dblist->agePrefixes = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears');
        $dblist->id = $this->id;
        $dblist->newRecPid = $this->newArticlePid;
        $dblist->singlePid = $this->singlePid;
        $dblist->selectedCategories = $this->selectedCategories;
        $dblist->category = $this->category;
        $dblist->returnUrl = LegacyBackendUtility::getModuleUrl('web_txttnewsM1', $urlparams);
        $dblist->excludeCats = $this->excludeCats;
        $dblist->includeCats = $this->includeCats;
        $dblist->isAdmin = $this->isAdmin;
        $dblist->current_sys_language = $this->current_sys_language;
        $dblist->showOnlyEditable = $this->mData['showOnlyEditable'];
        $dblist->pidList = $this->pidList;
        $dblist->editablePagesList = $this->editablePagesList;
        $dblist->searchFields = $this->TSprop['list.']['searchFields'];

        $dblist->start($this->id, $table, $this->pointer, $this->search_field, $this->searchLevels, $this->showLimit);
        if ($this->searchLevels > 0) {
            $allowedMounts = $this->getSearchableWebmounts($this->id, $this->searchLevels, $this->perms_clause);
            $pidList = implode(',',$allowedMounts);
            $dblist->pidSelect = 'pid IN (' . $pidList . ')';
        } elseif ($this->searchLevels < 0) {
            // Search everywhere
            $dblist->pidSelect = '1=1';
        } else {
            $dblist->pidSelect = 'pid=' . (int)$this->id;
        }

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
    /**
     * Get all allowed mount pages to be searched in.
     *
     * @param int $id Page id
     * @param int $depth Depth to go down
     * @param string $perms_clause select clause
     * @return int[]
     */
    protected function getSearchableWebmounts($id, $depth, $perms_clause)
    {
        $backendUser = $this->getBackendUser();
        /** @var PageTreeView $tree */
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init('AND ' . $perms_clause);
        $tree->makeHTML = 0;
        $tree->fieldArray = ['uid', 'php_tree_stop'];
        $idList = [];

        $allowedMounts = !$backendUser->isAdmin() && $id === 0
            ? $backendUser->returnWebmounts()
            : [$id];

        foreach ($allowedMounts as $allowedMount) {
            $idList[] = $allowedMount;
            if ($depth) {
                $tree->getTree($allowedMount, $depth, '');
            }
            $idList = array_merge($idList, $tree->ids);
        }

        return $idList;
    }
    /*************************************************************************
     *
     *        AJAX functions
     *
     ************************************************************************/

    /**
     *
     * @throws DBALException
     */
    public function ajaxExpandCollapse($params)
    {
        $this->processAjaxRequestConstruct();
        $this->init();
        $this->getTreeObj();
        $tree = $this->treeObj->getBrowsableTree();

       return $tree;
    }

    /**
     * @throws DBALException
     * @throws SiteNotFoundException
     */
    public function ajaxLoadList($params)
    {
        $this->processAjaxRequestConstruct();
        $this->init();
        $this->id = (int)$params['id'];
        $list = $this->displayNewsList(true);

        return $list;
    }

    /**
     */
    public function processAjaxRequestConstruct()
    {
        global $SOBE;

        $SOBE = GeneralUtility::makeInstance(NewsAdminModule::class);
        // Create an instance of the document template object
        $SOBE->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $SOBE->doc->backPath = $GLOBALS['BACK_PATH'];
        $SOBE->doc->docType = 'xhtml_trans';
    }

    /*************************************************************************
     *
     *        GUI Elements
     *
     ************************************************************************/

    /**
     *
     * @param NewsRecordlist $dblist
     *
     * @return string
     * @throws DBALException
     */
    protected function getListHeaderMsg(&$dblist): string
    {

        $noCatSelMsg = false;
        if (!$this->selectedCategories) {
            if ($this->TSprop['list.']['noListWithoutCatSelection']) {
                $content = '<img' . IconFactory::skinImg('icon_note.gif',
                        'width="18" height="16"') . ' title="" alt="" />' . $this->getLanguageService()->getLL('selectCategory');
                $noCatSelMsg = true;
            } else {
                $content = $this->getLanguageService()->getLL('showingAll');
            }
        } else {
            $content = $this->getListHeaderMsgForSelectedCategories();
        }

        if ($dblist->totalItems == 0) {
            $content .= $this->getNoResultsMsg($dblist, $noCatSelMsg);
        }

        return '<div style="padding:5px 0;">' . $content . '</div>';
    }

    /**
     * @param $url
     *
     * @return string
     */
    protected function displaySearch($url): string
    {
        // Table with the search box:
        return '<form action="' . htmlspecialchars($url) . '" method="post">
				<!--
					Search box:
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="ttnewsadmin-search">
					<tr>
						<td>' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enterSearchString',
                1) . '<input type="text" name="search_field" value="' . htmlspecialchars($this->search_field) . '" style="width:99px;" /></td>
						<td>' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showRecords',
                1) . ':<input type="text" name="SET[showLimit]" value="' . htmlspecialchars($this->showLimit ? $this->showLimit : '') . '" style="width:40px;" /></td>
						<td><input type="submit" name="search" value="' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.search',
                1) . '" /></td>

					</tr>
				</table>
			</form>';
    }

    /**
     * @param NewsRecordlist $listObj
     * @param                $noCatSelMsg
     *
     * @return string
     */
    protected function getNoResultsMsg(&$listObj, $noCatSelMsg)
    {
        $content = '';
        $tRows = array();
        if (!$noCatSelMsg) {
            $tRows[] = '<tr>
					<td valign="top"><p><img' . IconFactory::skinImg('icon_note.gif', 'width="18" height="16"') . ' title="" alt="" />
					' . $this->getLanguageService()->getLL('noNewsFound') . '
					</p></td>
					</tr>';
        }


        if ($this->mayUserEditArticles) {
            $tRows[] = '<tr>
					<td valign="top"><div style="padding:10px 0;">' . $listObj->getNewRecordButton('tt_news', true) . '</div></td>
					</tr>';
        }

        return $content . '<table border="0" cellpadding="1" cellspacing="2" id="typo3-page-stdlist">' . implode('',
                $tRows) . '</table>';
    }


    /**
     * @return string
     */
    protected function renderTreeCheckBoxes()
    {
        $show = array();
        if (is_array($this->TSprop['catmenu.']['show.'])) {
            $show = $this->TSprop['catmenu.']['show.'];
        }
        $allowedCbNames = array('expandAll', 'showHiddenCategories');

        if ($this->mayUserEditCategories) {
            $allowedCbNames[] = 'showEditIcons';
        }

        $params = $this->getLinkParams();
        $out = array();
        foreach ($allowedCbNames as $n) {
            if ((bool)$show['cb_' . $n]) {
                $out[] = BackendUtility::getFuncCheck($params, 'SET[' . $n . ']',
                        $this->MOD_SETTINGS[$n], '', '', 'id="cb-' . $n . '"') .
                    ' <label for="cb-' . $n . '">' . $this->getLanguageService()->getLL($n) . '</label>';
            }
        }

        return '<div>' . implode('<br />', $out) . '</div>';
    }

    /**
     * @param bool $ajax
     *
     * @return string
     */
    protected function renderListCheckBoxes($ajax = false)
    {
        $show = array();
        if (is_array($this->TSprop['list.']['show.'])) {
            $show = $this->TSprop['list.']['show.'];
        }
        $allowedCbNames = array();
        if (GeneralUtility::inList($this->fieldList, 'image')) {
            $allowedCbNames[] = 'showThumbs';
        }
        if (!$this->isAdmin) {
            $allowedCbNames[] = 'showOnlyEditable';
        }

        $params = $this->getLinkParams();
        $out = array();

        $savedRoute = false;
        if ($ajax) {
            $savedRoute = $_GET['route'];
            $_GET['route'] = '/web/txttnewsM1/';
        }
        foreach ($allowedCbNames as $n) {
            if ((bool)$show['cb_' . $n]) {
                $out[] = '<span class="list-cb">' .
                    BackendUtility::getFuncCheck($params, 'SET[' . $n . ']',
                        $this->MOD_SETTINGS[$n], '', '', 'id="cb-' . $n . '"') .
                    ' <label for="cb-' . $n . '">' . $this->getLanguageService()->getLL($n) . '</label></span>';
            }
        }
        if ($savedRoute) {
            $_GET['route'] = $savedRoute;
        }

        return '<div>' . implode('', $out) . '</div>';
    }


    /**
     * @return string
     */
    protected function renderNewCatButton()
    {
        $show = array();
        $button = '';
        if (is_array($this->TSprop['catmenu.']['show.'])) {
            $show = $this->TSprop['catmenu.']['show.'];
        }
        if ($this->mayUserEditCategories && (bool)$show['btn_newCategory']) {
            $params = '&edit[tt_news_cat][' . $this->storagePid . ']=new';
            $onclick = htmlspecialchars(BackendUtility::editOnClick($params,
                $GLOBALS['BACK_PATH'], $this->returnUrl));
            /**
             * @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory
             */
            $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
            $button = '<a href="#" onclick="' . $onclick . '">' .
                $iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render() .
                $this->getLanguageService()->getLL('createCategory') .
                '</a>';
        }

        return '<div style="padding:5px 0;">' . $button . '</div>';
    }


    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @return    array        all available buttons as an assoc. array
     */
    protected function getHeaderButtons()
    {
        $lang = $this->getLanguageService();
        $buttons = array(
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
        );

        $backPath = $GLOBALS['BACK_PATH'];

        if (isset($this->id)) {
            if ($this->getBackendUser()->check('modules', 'web_list')) {
                $href = LegacyBackendUtility::getModuleUrl('web_list', array(
                    'id' => $this->pageinfo['uid'],
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                ));

                $buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '" class="btn btn-default btn-sm" title="'
                    . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showList')) . '">' .
                    $this->iconFactory->getIcon('actions-system-list-open', Icon::SIZE_SMALL)->render() .
                    '</a>';
            }

            // View
            $buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($this->id,
                    $backPath, BackendUtility::BEgetRootLine($this->id))) . '" class="btn btn-default btn-sm" title="'
                    . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage')) . '">' .
                $this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL)->render() .
                '</a>';

            // If edit permissions are set (see class.t3lib_userauthgroup.php)
            if ($this->localCalcPerms & 2 && !empty($this->id)) {
                // Edit
                $params = '&edit[pages][' . $this->pageinfo['uid'] . ']=edit';
                $buttons['edit'] = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params,
                    $backPath, -1)) . '" class="btn btn-default btn-sm" title="'
                    . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:editPage')) . '">' .
                    $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render() .
                    '</a>';
            }

            // Reload
            $buttons['reload'] = '<a href="' . htmlspecialchars(GeneralUtility::linkThisScript()) . '" class="btn btn-default btn-sm" title="'
                . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload')) . '">' .
                $this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL)->render() .
                '</a>';

            // Shortcut
            if ($this->getBackendUser()->mayMakeShortcut()) {
                $buttons['shortcut'] = $this->doc->makeShortcutIcon('id, showThumbs, pointer, table, search_field, searchLevels, showLimit, sortField, sortRev',
                    implode(',', array_keys($this->MOD_MENU)), 'web_txttnewsM1', '', 'btn btn-default btn-sm');
            }

            // Back
            if ($this->returnUrl) {
                $buttons['back'] = '<a href="' . htmlspecialchars(GeneralUtility::linkThisUrl($this->returnUrl,
                        array('id' => $this->id))) . '" class="typo3-goBack btn btn-default btn-sm" title="'
                    . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack')) . '">' .
                    $this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL)->render() .
                    '</a>';
            }
        }

        return $buttons;
    }


    /**
     * @return string
     */
    protected function getLangMenu()
    {
        $menu = '';
        if (count($this->MOD_MENU['language']) > 1) {
            $menu = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
                    1) .
                BackendUtility::getFuncMenu($this->id, 'SET[language]',
                    $this->current_sys_language, $this->MOD_MENU['language']);
        }

        return $menu;
    }

    /**
     * @return string
     */
    protected function getPageSelector()
    {
        $menu = '';
        if (count($this->MOD_MENU['searchLevels']) > 1) {
            $menu = $this->getLanguageService()->getLL('enterSearchLevels') .
                BackendUtility::getFuncMenu($this->id, 'SET[searchLevels]',
                    $this->searchLevels, $this->MOD_MENU['searchLevels']);
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
     * @throws DBALException
     */
    protected function setPidList()
    {
        if ($this->isAdmin) {
            return;
        }

        // get allowed pages
        $webmounts = $this->getBackendUser()->returnWebmounts();
        if (!is_array($webmounts)) {
            return;
        }

        $pidList = '';
        foreach ($webmounts as $mount) {
            $pidList .= ',' . $mount . ',' . $this->getSubPages($mount);
        }

        $pidList = GeneralUtility::uniqueList($pidList);
        $this->pidList = ($pidList ? $pidList : 0);
    }

    /**
     * @param $pidlist
     */
    protected function setEditablePages($pidlist)
    {
        $pids = explode(',', $pidlist);
        $editPids = array();

        foreach ($pids as $pid) {
            if (($this->checkPageAccess($pid))) {
                $editPids[] = $pid;
            }
        }

        $this->editablePagesList = implode(',', $editPids);
    }

    /**
     *
     * @param     $pages
     * @param int $cc
     *
     * @return string
     * @throws DBALException
     */
    protected function getSubPages($pages, $cc = 0)
    {
        $pArr = array();
        $res = Database::getInstance()->exec_SELECTquery(
            'uid',
            'pages',
            'pages.pid IN (' . $pages . ') AND pages.deleted=0 AND ' . $this->perms_clause);

        while (($row = Database::getInstance()->sql_fetch_assoc($res))) {
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
     *
     */
    protected function initGPvars()
    {
        $this->pointer = MathUtility::forceIntegerInRange(GeneralUtility::_GP('pointer'),
            0, 100000);
        $this->category = intval(GeneralUtility::_GP('category'));
        $this->search_field = GeneralUtility::_GP('search_field');

    }

    /**
     * Adds items to the ->MOD_MENU array. Used for the function menu selector.
     *
     * @throws DBALException
     */
    public function menuConfig()
    {
        $this->MOD_MENU = array(
            'function' => array(
                '1' => $this->getLanguageService()->getLL('function1'),
            ),
            'showEditIcons' => 0,
            'expandAll' => 0,
            'showOnlyEditable' => 0,
            'showHiddenCategories' => 0,
            'searchLevels' => array(
                -1 => $this->getLanguageService()->getLL('allPages'),
                0 => $this->getLanguageService()->getLL('thisPage'),
                1 => $this->getLanguageService()->getLL('oneLevel'),
                2 => $this->getLanguageService()->getLL('twoLevels'),
                3 => $this->getLanguageService()->getLL('threeLevels'),
                4 => $this->getLanguageService()->getLL('fourLevels')

            ),
            'showThumbs' => 1,
            'showLimit' => 0,
            'language' => array(
                0 => $this->getLanguageService()->getLL('defaultLangLabel')
            )

        );
        $this->initLanguageMenu();

        $this->MOD_MENU['function'] = $this->mergeExternalItems($this->MCONF['name'], 'function',
            $this->MOD_MENU['function']);

        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU,
            GeneralUtility::_GP('SET'), $this->MCONF['name'], $this->modMenu_type,
            $this->modMenu_dontValidateList, $this->modMenu_setDefaultList);
    }

    /**
     * @throws DBALException
     */
    protected function initLanguageMenu()
    {
        if ($this->isAdmin) {
            $res = Database::getInstance()->exec_SELECTquery(
                'sys_language.*',
                'sys_language',
                'sys_language.hidden=0',
                '',
                'sys_language.title'
            );
        } else {
            $exQ = ' AND pages_language_overlay.deleted = 0';
            $res = Database::getInstance()->exec_SELECTquery(
                'sys_language.*',
                'pages_language_overlay,sys_language',
                'pages_language_overlay.sys_language_uid=sys_language.uid AND pages_language_overlay.pid IN (' . $this->pidList . ')' . $exQ,
                'pages_language_overlay.sys_language_uid,sys_language.uid,sys_language.pid,sys_language.tstamp,sys_language.hidden,sys_language.title,sys_language.static_lang_isocode,sys_language.flag',
                'sys_language.title'
            );
        }

        while (($lrow = Database::getInstance()->sql_fetch_assoc($res))) {
            if ($this->getBackendUser()->checkLanguageAccess($lrow['uid'])) {
                $this->MOD_MENU['language'][$lrow['uid']] = ($lrow['hidden'] ? '(' . $lrow['title'] . ')' : $lrow['title']);
            }
        }

        // add "all" language
        $this->MOD_MENU['language'][-1] = $this->getLanguageService()->getLL('allLanguages');

        // Setting alternative default label:
        $dl = trim($this->TSprop['defaultLanguageLabel']);
        if ($dl && isset($this->MOD_MENU['language'][0])) {
            $this->MOD_MENU['language'][0] = $dl;
        }
    }


    /**
     * @throws DBALException
     */
    protected function initCategories()
    {
        if ($this->isAdmin) {
            return;
        }

        // get include/exclude items
        if (($excludeList = $this->getBackendUser()->getTSConfig()['tt_newsPerms.']['tt_news_cat.']['excludeList'])) {
            $this->excludeCats = $this->posIntExplode($excludeList);
        }

        $this->includeCats = Div::getIncludeCatArray();
        $this->catlistWhere = Div::getCatlistWhere();
    }

    /**
     * @param $list
     *
     * @return array
     */
    protected function posIntExplode($list)
    {
        $arr = GeneralUtility::intExplode(',', $list);
        $out = array();

        foreach ($arr as $v) {
            if ($v > 0) {
                $out[] = $v;
            }
        }

        return $out;
    }

    /**
     *
     * @throws DBALException
     */
    protected function initSubCategories()
    {
        if ($this->useSubCategories && $this->category) {
            $subcats = Div::getSubCategories($this->category);
            $this->selectedCategories = GeneralUtility::uniqueList($this->category . ($subcats ? ',' . $subcats : ''));
        } else {
            $this->selectedCategories = $this->category;
        }
    }

    /**
     * Checks if a PID value is accessible and if so returns the path for the page.
     * Processing is cached so many calls to the function are OK.
     *
     * @param    integer        Page id for check
     *
     * @return    array        Page path of PID if accessible. otherwise zero.
     */
    protected function getPageInfoForOverview($pid)
    {
        $out = array();
        $localPageinfo = BackendUtility::readPageAccess($pid, $this->perms_clause);
        $out['path'] = $localPageinfo['_thePath'];

        $calcPerms = $this->getBackendUser()->calcPerms($localPageinfo);
        if (($calcPerms & 16)) {
            $out['edit'] = true;
        }

        return $out;
    }

    /**
     * @param $pid
     *
     * @return mixed
     */
    public function checkPageAccess($pid)
    {
        if (isset($this->permsCache[$pid])) {
            return $this->permsCache[$pid];
        }

        $calcPerms = $this->getBackendUser()->calcPerms(BackendUtility::readPageAccess($pid,
            $this->perms_clause));
        if (($calcPerms & 16)) {
            $this->permsCache[$pid] = true;
        } else {
            $this->permsCache[$pid] = false;
        }

        return $this->permsCache[$pid];
    }

    /**
     *
     */
    protected function initPermsCache()
    {
        if ($this->isAdmin) {
            return;
        }

        $this->pidChash = md5($this->pidList);
        $pc = $this->getBackendUser()->getSessionData('permsCache');
        if (is_array($pc) && is_array($pc[$this->pidChash])) {
            $this->permsCache = $pc[$this->pidChash];
        }
    }

    /**
     * @return array
     */
    protected function getLinkParams()
    {
        $params = array('id' => $this->id);

        if ($this->category) {
            $params['category'] = $this->category;
        }

        return $params;
    }

    /**
     *
     * @return string
     * @throws DBALException
     */
    protected function getListHeaderMsgForSelectedCategories(): string
    {
        $table = 'tt_news_cat';
        $row = BackendUtility::getRecord($table, $this->category);
        $title = '<strong>' . BackendUtility::getRecordTitle($table, $row) . '</strong>';
        $content = '<div id="newscatsmsg">' . $this->getLanguageService()->getLL('showingOnlyCat') . $title . '</div>';

        if ($this->useSubCategories && ($subCats = GeneralUtility::rmFromList($this->category,
                $this->selectedCategories))) {
            $scRows = Database::getInstance()->exec_SELECTgetRows('uid,title,hidden', $table,
                'uid IN (' . $subCats . ')' . (!$this->mData['showHiddenCategories'] ? ' AND hidden=0' : ''));
            $scTitles = array();
            foreach ($scRows as $scRow) {
                $recTitle = BackendUtility::getRecordTitle($table, $scRow);
                if ($scRow['hidden']) {
                    $recTitle = '<span class="hiddencat">' . $recTitle . '</span>';
                }
                $scTitles[] = $recTitle;
            }

            if (count($scTitles)) {
                $showLbl = $this->getLanguageService()->getLL('showSubcatgories');
                $hideLbl = $this->getLanguageService()->getLL('hideSubcatgories');
                $btnID = 'togglesubcats';
                $elID = 'newssubcats';
                $onclick = htmlspecialchars('if
						($(\'#' . $elID . '\').is(\':visible\')) {
							$(\'#' . $elID . '\').hide();
							$(\'#' . $btnID . '\').html(' . GeneralUtility::quoteJSvalue($showLbl) . ');
						} else {
							$(\'#' . $elID . '\').show();$(\'' . $btnID . '\').html(' . GeneralUtility::quoteJSvalue($hideLbl) . ');}');
                $content .= '<div id="' . $btnID . '" onclick="' . $onclick . '">' . $showLbl . '</div>';
                $content .= '<div id="' . $elID . '" style="display:none;">' . implode(', ', $scTitles) . '</div>';
            }
        }

        return $content;
    }
}


