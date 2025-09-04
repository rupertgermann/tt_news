<?php

namespace RG\TtNews\Plugin;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2004 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  (c) 2004-2024 Rupert Germann (rupi@gmx.li)
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

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use Psr\Http\Message\ResponseFactoryInterface;
use RG\TtNews\Database\Database;
use RG\TtNews\Helper\Helpers;
use RG\TtNews\Menu\Catmenu;
use RG\TtNews\Utility\Div;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

/**
 * Plugin 'news' for the 'tt_news' extension.
 *
 * @author     Rupert Germann <rupi@gmx.li>
 */
class TtNews extends AbstractPlugin
{
    /**
     * @var string
     */
    public $prefixId = 'tx_ttnews'; // Same as class name
    /**
     * @var string
     */
    public $extKey = 'tt_news'; // The extension key.
    /**
     * @var bool
     */
    public $pi_checkCHash = true;

    /**
     * @var Helpers
     */
    public $helpers; // class with helper functions
    /**
     * @var int
     */
    public $tt_news_uid = 0; // the uid of the current news record in SINGLE view
    /**
     * @var int
     */
    public $pid_list = 0;
    /**
     * @var array
     */
    public $config = []; // the processed TypoScript configuration array
    /**
     * @var
     */
    public $confArr; // extension config from extmanager
    /**
     * @var
     */
    public $genericMarkerConf;
    /**
     * @var array
     */
    public $sViewSplitLConf = [];
    /**
     * @var array
     *
     * @todo remove/replace legacy langArr
     */
    public $langArr = []; // the languages found in the tt_news sysfolder
    /**
     * @var string
     */
    public $sys_language_mode = '';
    /**
     * @var int
     */
    public $alternatingLayouts = 0;
    /**
     * @var int
     */
    public $allowCaching = 1;
    /**
     * @var string
     */
    public $catExclusive = '';
    /**
     * @var string
     */
    public $actuallySelectedCategories = '';
    /**
     * @var int
     */
    public $arcExclusive = 0;
    /**
     * @var array
     */
    public $fieldNames = [];
    /**
     * @var string
     */
    public $searchFieldList = 'short,bodytext,author,keywords,links,imagecaption,title';
    /**
     * @var string
     */
    public $theCode = ''; // the current code
    /**
     * @var
     */
    public $codes; // list of all codes
    /**
     * @var string
     */
    public $rdfToc = '';
    /**
     * @var string
     */
    public $templateCode = '';

    /**
     * @var bool
     */
    public $versioningEnabled = false; // is the extension 'version' loaded
    /**
     * @var bool
     */
    public $vPrev = false; // do we display a versioning preview
    /**
     * @var array
     */
    public $categories = [];
    /**
     * @var array
     */
    public $pageArray = []; // internal cache with an array of the pages in the pid-list
    /**
     * @var string
     */
    public $pointerName = 'pointer';
    /**
     * @var int
     */
    public $SIM_ACCESS_TIME = 0;
    //	public $renderFields = array();
    /**
     * @var array
     */
    public $errors = [];

    /**
     * @var string
     */
    public $enableFields = '';
    /**
     * @var string
     */
    public $enableCatFields = '';
    /**
     * @var string
     */
    public $SPaddWhere = '';
    /**
     * @var string
     */
    public $catlistWhere = '';

    /**
     * @var string
     */
    public $token = '';

    /**
     * @var FrontendInterface
     */
    public $cache;
    /**
     * @var bool
     */
    public $cache_amenuPeriods = false;
    /**
     * @var bool
     */
    public $cache_categoryCount = false;
    /**
     * @var bool
     */
    public $cache_categories = false;
    /**
     * @var Database
     */
    public $db;
    /**
     * @var TypoScriptFrontendController
     */
    public $tsfe;

    /**
     * @var
     */
    public $convertToUserIntObject;
    /**
     * @var
     */
    public $splitLConf;
    /**
     * @var
     */
    public $piVars_catSelection;
    /**
     * @var
     */
    public $dontStartFromRootRecord;
    /**
     * @var
     */
    public $cleanedCategoryMounts;
    /**
     * @var
     */
    public $renderMarkers;
    /**
     * @var
     */
    public $addFromTable;
    /**
     * @var
     */
    public $relNewsUid;
    /**
     * @var
     */
    public $externalCategorySelection;

    /**
     * @var
     */
    public $newsCount;
    /**
     * @var ContentObjectRenderer
     */
    public $local_cObj;

    /**
     * @var MarkerBasedTemplateService
     */
    protected $markerBasedTemplateService;
    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    /**
     * @var
     */
    protected $images;
    /**
     * @var
     */
    protected $files;
    /**
     * disables internal rendering. If set to true an external renderer like Fluid can be used
     */
    private bool $useFluidRenderer = false;

    /**
     * @var array
     */
    public $fluidVars = [];

    private ?array $listData = null;

    /**
     * @var int
     */
    protected $sys_language_content;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * TtNews constructor.
     */
    public function __construct()
    {
        //if search => disable cache hash check to avoid pageNotFoundOnCHashError, see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::reqCHash
        if (GeneralUtility::_GPmerged($this->prefixId)['swords'] ?? false) {
            $this->pi_checkCHash = false;
        }

        $this->markerBasedTemplateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        parent::__construct();
    }

    /**
     * Main news function: calls the init_news() function and decides by the given CODEs which of the
     * functions to display news should by called.
     *
     * @param    string $content : function output is added to this
     * @param    array  $conf    : configuration array
     *
     * @return    string        $content: complete content generated by the tt_news plugin
     */
    public function main_news($content, $conf)
    {
        $this->confArr = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tt_news'];

        $this->helpers = new Helpers($this);
        $this->conf = $conf; //store configuration

        if ($this->conf['useFluidRendering']) {
            $this->useFluidRenderer = true;
            $this->initViewObject();
        }

        // leave early if USER_INT
        $this->convertToUserIntObject = ($this->conf['convertToUserIntObject'] ?? false) ? 1 : 0;
        if ($this->convertToUserIntObject
            && $this->cObj->getUserObjectType() == ContentObjectRenderer::OBJECTTYPE_USER) {
            $this->cObj->convertToUserIntObject();

            return '';
        }

        $this->preInit();

        if ($this->conf['enableConfigValidation'] && count($this->errors)) {
            return $this->helpers->displayErrors();
        }

        $this->init();
        foreach ($this->codes as $theCode) {
            $theCode = (string)strtoupper(trim((string)$theCode));
            $this->theCode = $theCode;
            // initialize category vars
            $this->initCategoryVars();
            $this->initGenericMarkers();

            switch ($theCode) {
                case 'SINGLE':
                case 'SINGLE2':
                    $content .= $this->displaySingle();
                    break;
                case 'LATEST':
                case 'LIST':
                case 'LIST2':
                case 'LIST3':
                case 'HEADER_LIST':
                case 'SEARCH':
                case 'XML':
                    $content .= $this->displayList();
                    break;
                case 'AMENU':
                    $content .= $this->displayArchiveMenu();
                    break;
                case 'CATMENU':
                    $content .= $this->displayCatMenu();
                    break;
                default:
                    // hook for processing of extra codes
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraCodesHook'])) {
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraCodesHook'] as $_classRef) {
                            $_procObj = GeneralUtility::makeInstance($_classRef);
                            $content .= $_procObj->extraCodesProcessor($this);
                        }
                    } else { // code not known and no hook found to handle it -> displayerror
                        $this->errors[] = 'CODE "' . $theCode . '" not known';
                    }

                    break;
            }
        }

        // check errors array again
        if ($this->conf['enableConfigValidation'] && count($this->errors)) {
            return $this->helpers->displayErrors();
        }

        return $content;
    }

    protected function initViewObject()
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setRequest($GLOBALS['TYPO3_REQUEST']);
    }

    /**
     * @param $data
     *
     * @return string
     */
    protected function renderFluidContent($data)
    {
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $plainConf = $typoScriptService->convertTypoScriptArrayToPlainArray($this->conf);

        $this->view->setLayoutRootPaths($this->conf['view.']['layoutRootPaths.']);
        $this->view->setPartialRootPaths($this->conf['view.']['partialRootPaths.']);
        $this->view->setTemplateRootPaths($this->conf['view.']['templateRootPaths.']);

        $this->view->setTemplate(ucfirst(strtolower($this->theCode)));
        $this->view->assign('content', $data);
        $this->view->assign('conf', $plainConf);
        $this->view->assign('piVars', $this->piVars);
        $content = $this->view->render();

        return $content;
    }

    /**
     * [Describe function...]
     */
    protected function preInit()
    {
        // Init FlexForm configuration for plugin
        $this->pi_initPIflexForm();

        $flexformTyposcript = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'myTS', 's_misc');
        if ($flexformTyposcript) {
            /** @var TypoScriptParser $tsparser */
            $tsparser = GeneralUtility::makeInstance(TypoScriptParser::class);
            // Copy conf into existing setup
            $tsparser->setup = $this->conf;
            // Parse the new Typoscript
            $tsparser->parse($flexformTyposcript);
            // Copy the resulting setup back into conf
            $this->conf = $tsparser->setup;
        }

        // "CODE" decides what is rendered: codes can be set by TS or FF with priority on FF
        $code = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'what_to_display', 'sDEF');
        $this->config['code'] = ($code ?: $this->cObj->stdWrap($this->conf['code'], $this->conf['code.'] ?? []));

        if (($this->conf['displayCurrentRecord'] ?? false)) {
            $this->config['code'] = ($this->conf['defaultCode'] ?? null) ? trim((string)$this->conf['defaultCode']) : 'SINGLE';
            $this->tt_news_uid = $this->cObj->data['uid'];
        }

        // get codes and decide which function is used to process the content
        $codes = GeneralUtility::trimExplode(
            ',',
            $this->config['code'] ?: $this->conf['defaultCode'],
            1
        );
        if (!(is_countable($codes) ? count($codes) : 0)) { // no code at all
            $codes = [];
            $this->errors[] = 'No code given';
        }

        $this->codes = $codes;
    }

    /**
     * Init Function: here all the needed configuration values are stored in class variables..
     *
     */
    protected function init()
    {
        $this->db = Database::getInstance();
        $this->tsfe = $GLOBALS['TSFE'];

        $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');
        $this->sys_language_content =  $languageAspect->getContentId();

        $this->pi_loadLL('EXT:tt_news/Resources/Private/Language/Plugin/locallang_pi.xlf'); // Loading language-labels
        $this->pi_setPiVarDefaults(); // Set default piVars from TS

        $this->SIM_ACCESS_TIME = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
        // fallback for TYPO3 < 4.2
        if (!$this->SIM_ACCESS_TIME) {
            $simTime = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
            $this->SIM_ACCESS_TIME = $simTime - ($simTime % 60);
        }

        $this->initCaching();

        $this->local_cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class); // Local cObj.
        $this->enableFields = $this->getEnableFields('tt_news');

        if ($this->tt_news_uid === 0) { // no tt_news_uid set by displayCurrentRecord
            $this->tt_news_uid = (int)($this->piVars['tt_news'] ?? 0); // Get the submitted uid of a news (if any)
        }

        $this->token = md5(microtime());

        if (ExtensionManagementUtility::isLoaded('workspaces')) {
            $this->versioningEnabled = true;
        }
        // load available syslanguages
        // @todo: $this->initLanguages();
        // sys_language_mode defines what to do if the requested translation is not found
        $this->sys_language_mode = (($this->conf['sys_language_mode'] ?? false) ?: $languageAspect->getLegacyLanguageMode());

        if (($this->conf['searchFieldList'] ?? false)) {
            // get fieldnames from the tt_news db-table
            $this->fieldNames = array_keys($this->db->admin_get_fields('tt_news'));
            $searchFieldList = $this->helpers->validateFields($this->conf['searchFieldList'], $this->fieldNames);
            if ($searchFieldList) {
                $this->searchFieldList = $searchFieldList;
            }
        }
        // Archive:
        $archiveMode = trim((string)$this->conf['archiveMode']); // month, quarter or year listing in AMENU
        $this->config['archiveMode'] = $archiveMode ?: 'month';

        // arcExclusive : -1=only non-archived; 0=don't care; 1=only archived
        $arcExclusive = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'archive', 'sDEF');
        $this->arcExclusive = $arcExclusive ?: (int)($this->conf['archive'] ?? 0);

        $this->config['datetimeDaysToArchive'] = (int)($this->conf['datetimeDaysToArchive'] ?? 0);
        $this->config['datetimeHoursToArchive'] = (int)($this->conf['datetimeHoursToArchive'] ?? 0);
        $this->config['datetimeMinutesToArchive'] = (int)($this->conf['datetimeMinutesToArchive'] ?? 0);

        if ($this->conf['useHRDates']) {
            $this->helpers->convertDates();
        }

        // list of pages where news records will be taken from
        if (!$this->conf['dontUsePidList']) {
            $this->initPidList();
        }

        // itemLinkTarget is only used for categoryLinkMode 3 (catselector) in framesets
        $this->conf['itemLinkTarget'] = trim($this->conf['itemLinkTarget'] ?? '');
        // id of the page where the search results should be displayed
        $this->config['searchPid'] = (int)($this->conf['searchPid'] ?? 0);

        // pages in Single view will be divided by this token
        $this->config['pageBreakToken'] = trim($this->conf['pageBreakToken'] ?? '') ?: '<---newpage--->';

        $this->config['singleViewPointerName'] = trim($this->conf['singleViewPointerName'] ?? '') ?: 'sViewPointer';

        $maxWordsInSingleView = (int)($this->pi_getFFvalue(
            $this->cObj->data['pi_flexform'] ?? null,
            'maxWordsInSingleView',
            's_misc'
        ));
        $maxWordsInSingleView = $maxWordsInSingleView ?: (int)($this->conf['maxWordsInSingleView']);
        $this->config['maxWordsInSingleView'] = $maxWordsInSingleView ?: 0;
        $this->config['useMultiPageSingleView'] = $this->conf['useMultiPageSingleView'];

        // pid of the page with the single view. the old var PIDitemDisplay is still processed if no other value is found
        $singlePid = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'PIDitemDisplay', 's_misc');
        $this->config['singlePid'] = $singlePid ?: (int)($this->cObj->stdWrap(
            ($this->conf['singlePid'] ?? 0),
            ($this->conf['singlePid.'] ?? false)
        ));
        if (!$this->config['singlePid']) {
            $this->errors[] = 'No singlePid defined';
        }
        // pid to return to when leaving single view
        $backPid = (int)($this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'backPid', 's_misc'));
        $backPid = $backPid ?: (int)($this->conf['backPid'] ?? 0);
        $backPid = $backPid ?: (int)($this->piVars['backPid'] ?? 0);
        $backPid = $backPid ?: $this->tsfe->id;
        $this->config['backPid'] = $backPid;

        // max items per page
        $FFlimit = MathUtility::forceIntegerInRange($this->pi_getFFvalue(
            $this->cObj->data['pi_flexform'] ?? null,
            'listLimit',
            's_template'
        ), 0, 1000);

        $limit = MathUtility::forceIntegerInRange($this->cObj->stdWrap(
            ($this->conf['limit'] ?? 0),
            ($this->conf['limit.'] ?? false)
        ), 0, 1000);
        $limit = $limit ?: 50;
        $this->config['limit'] = $FFlimit ?: $limit;

        $latestLimit = MathUtility::forceIntegerInRange($this->cObj->stdWrap(
            ($this->conf['latestLimit'] ?? 0),
            ($this->conf['latestLimit.'] ?? false)
        ), 0, 1000);
        $latestLimit = $latestLimit ?: 10;
        $this->config['latestLimit'] = $FFlimit ?: $latestLimit;

        // orderBy and groupBy statements for the list Query
        $orderBy = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'listOrderBy', 'sDEF');
        $orderByTS = trim($this->conf['listOrderBy'] ?? '');
        $orderBy = $orderBy ?: $orderByTS;
        $this->config['orderBy'] = $orderBy;

        if ($orderBy) {
            $ascDesc = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'ascDesc', 'sDEF');
            $this->config['ascDesc'] = $ascDesc;
            if ($this->config['ascDesc']) {
                // remove ASC/DESC from 'orderBy' if it is already set from TS
                $this->config['orderBy'] = preg_replace('/( DESC| ASC)\b/i', '', $this->config['orderBy']);
            }
        }
        $this->config['groupBy'] = trim($this->conf['listGroupBy'] ?? '');

        // if this is set, the first image is handled as preview image, which is only shown in list view
        $fImgPreview = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'firstImageIsPreview', 's_misc');
        $this->config['firstImageIsPreview'] = $fImgPreview ?: ($this->conf['firstImageIsPreview'] ?? false);
        $forcefImgPreview = $this->pi_getFFvalue(
            $this->cObj->data['pi_flexform'] ?? null,
            'forceFirstImageIsPreview',
            's_misc'
        );
        $this->config['forceFirstImageIsPreview'] = $forcefImgPreview ? $fImgPreview : ($this->conf['forceFirstImageIsPreview'] ?? false);

        // List start id
        //		$listStartId = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'listStartId', 's_misc'));
        //		$this->config['listStartId'] = /*$listStartId?$listStartId:*/intval($this->conf['listStartId']);
        // supress pagebrowser
        $noPageBrowser = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'noPageBrowser', 's_template');
        $this->config['noPageBrowser'] = $noPageBrowser ?: ($this->conf['noPageBrowser'] ?? false);

        // image sizes/optionSplit given from FlexForms
        $flexformValueHeight = $this->pi_getFFvalue(
            $this->cObj->data['pi_flexform'] ?? null,
            'imageMaxHeight',
            's_template'
        );

        $this->config['FFimgH'] = ($flexformValueHeight !== null) ? trim($flexformValueHeight) : '';

        $flexformValueWidth = $this->pi_getFFvalue(
            $this->cObj->data['pi_flexform'] ?? null,
            'imageMaxWidth',
            's_template'
        );

        $this->config['FFimgW'] = ($flexformValueWidth !== null) ? trim($flexformValueWidth) : '';

        // Get number of alternative Layouts (loop layout in LATEST and LIST view) default is 2:
        $altLayouts = (int)($this->pi_getFFvalue(
            $this->cObj->data['pi_flexform'] ?? null,
            'alternatingLayouts',
            's_template'
        ));
        $altLayouts = $altLayouts ?: (int)($this->conf['alternatingLayouts'] ?? 0);
        $this->alternatingLayouts = $altLayouts ?: 2;

        $altLayoutsOptionSplit = $this->pi_getFFvalue(
            $this->cObj->data['pi_flexform'] ?? null,
            'altLayoutsOptionSplit',
            's_template'
        );
        $this->config['altLayoutsOptionSplit'] = is_string($altLayoutsOptionSplit) ? trim($altLayoutsOptionSplit) : '';

        // Get cropping length
        $croppingLenghtOptionSplit = $this->pi_getFFvalue(
            $this->cObj->data['pi_flexform'] ?? null,
            'croppingLenghtOptionSplit',
            's_template'
        );
        $this->config['croppingLenghtOptionSplit'] = is_string($croppingLenghtOptionSplit) ? trim($croppingLenghtOptionSplit) : '';
        $croppingLenghtValue = $this->pi_getFFvalue(
            $this->cObj->data['pi_flexform'] ?? null,
            'croppingLenght',
            's_template'
        );
        $this->config['croppingLenght'] = trim($croppingLenghtValue ?? '');

        $this->initTemplate();

        // Configure caching
        if (isset($this->conf['allowCaching'])) {
            $this->allowCaching = $this->conf['allowCaching'] ? 1 : 0;
        }

        if (!$this->allowCaching) {
            $this->tsfe->set_no_cache();
        }

        // get siteUrl for links in rss feeds. the 'dontInsert' option seems to be needed in some configurations depending on the baseUrl setting
        if (!($this->conf['displayXML.']['dontInsertSiteUrl'] ?? false)) {
            $this->config['siteUrl'] = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        }
    }

    /**********************************************************************************************
     *
     *              Display Functions
     *
     **********************************************************************************************/

    /**
     * Display LIST,LATEST or SEARCH
     * Things happen: determine the template-part to use, get the query parameters (add where if search was performed),
     * exec count query to get the number of results, check if a browsebox should be displayed,
     * get the general Markers for each item and fill the content array, check if a browsebox should be displayed
     *
     * @param    string $excludeUids : commaseparated list of tt_news uids to exclude from display
     *
     * @return    string        html-code for the plugin content
     */
    public function displayList($excludeUids = '0')
    {
        $markerArray = [];
        $theCode = $this->theCode;
        $prefix_display = false;
        $templateName = false;
        $where = '';
        $content = '';
        switch ($theCode) {
            case 'LATEST' :
                $prefix_display = 'displayLatest';
                $templateName = 'TEMPLATE_LATEST';
                if (!$this->conf['displayArchivedInLatest']) {
                    // if this is set, latest will do the same as list
                    $this->arcExclusive = -1; // Only latest, non archive news
                }
                $this->config['limit'] = $this->config['latestLimit'];
                break;

            case 'LIST' :
            case 'LIST2' :
            case 'LIST3' :
            case 'HEADER_LIST' :
            case 'RELATED' :

                $prefix_display = 'displayList';
                $templateName = 'TEMPLATE_' . strtoupper($theCode);
                break;

            case 'SEARCH' :
                $prefix_display = 'displayList';
                $templateName = 'TEMPLATE_LIST';

                // Make markers for the searchform
                $searchMarkers = [
                    '###FORM_URL###' => $this->pi_linkTP_keepPIvars_url(
                        ['pointer' => null, 'cat' => null],
                        0,
                        1,
                        $this->config['searchPid']
                    ),
                    '###SWORDS###' => htmlspecialchars($this->piVars['swords'] ?? ''),
                    '###SEARCH_BUTTON###' => $this->pi_getLL('searchButtonLabel'),
                ];

                // Hook for any additional form fields
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['additionalFormSearchFields'] ?? null)) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['additionalFormSearchFields'] as $_classRef) {
                        $_procObj = GeneralUtility::makeInstance($_classRef);
                        $searchMarkers = $_procObj->additionalFormSearchFields($this, $searchMarkers);
                    }
                }

                // Add to content
                $searchSub = $this->getNewsSubpart($this->templateCode, $this->spMarker('###TEMPLATE_SEARCH###'));

                $this->renderMarkers = $this->getMarkers($searchSub);

                $content .= $this->markerBasedTemplateService->substituteMarkerArray($searchSub, $searchMarkers);

                unset($searchSub);

                // do the search and add the result to the $where string
                if (isset($this->piVars['swords'])) {
                    $where = $this->searchWhere(trim((string)$this->piVars['swords']));
                    $theCode = 'SEARCH';
                } else {
                    $where = ($this->conf['emptySearchAtStart'] ? 'AND 1=0' : ''); // display an empty list, if 'emptySearchAtStart' is set.
                }
                break;

                // xml news export
            case 'XML' :
                $prefix_display = 'displayXML';
                // $this->arcExclusive = -1; // Only latest, non archive news
                $this->allowCaching = $this->conf['displayXML.']['xmlCaching'] ?? null;
                $this->config['limit'] = $this->conf['displayXML.']['xmlLimit'] ?? $this->config['limit'] ?? null;

                switch ($this->conf['displayXML.']['xmlFormat']) {
                    case 'rss091' :
                        $templateName = 'TEMPLATE_RSS091';
                        $this->templateCode = $this->getFileResource($this->conf['displayXML.']['rss091_tmplFile']);
                        break;

                    case 'rss2' :
                        $templateName = 'TEMPLATE_RSS2';
                        $this->templateCode = $this->getFileResource($this->conf['displayXML.']['rss2_tmplFile'] ?? false);
                        break;

                    case 'rdf' :
                        $templateName = 'TEMPLATE_RDF';
                        $this->templateCode = $this->getFileResource($this->conf['displayXML.']['rdf_tmplFile']);
                        break;

                    case 'atom03' :
                        $templateName = 'TEMPLATE_ATOM03';
                        $this->templateCode = $this->getFileResource($this->conf['displayXML.']['atom03_tmplFile']);
                        break;

                    case 'atom1' :
                        $templateName = 'TEMPLATE_ATOM1';
                        $this->templateCode = $this->getFileResource($this->conf['displayXML.']['atom1_tmplFile']);
                        break;
                    default:
                        break;
                }
                break;
            default:
                break;
        }

        // process extra codes from $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']
        $userCodes = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['what_to_display'] ?? null;

        if (is_array($userCodes) && !$prefix_display && !$templateName) {
            foreach ($userCodes as $ucode) {
                if ($theCode == $ucode[0]) {
                    $prefix_display = 'displayList';
                    $templateName = 'TEMPLATE_' . $ucode[0];
                }
            }
        }

        // used to call getSelectConf without a period length (pL) at the first archive page
        $noPeriod = 0;

        if (!$this->conf['emptyArchListAtStart']) {
            // if this is true, we're listing from the archive for the first time (no pS set), to prevent an empty list page we set the pS value to the archive start
            if (($this->arcExclusive > 0 && !($this->piVars['pS'] ?? false) && $theCode != 'SEARCH')) {
                // set pS to time minus archive startdate
                if ($this->config['datetimeMinutesToArchive']) {
                    $this->piVars['pS'] = ($this->SIM_ACCESS_TIME - ($this->config['datetimeMinutesToArchive'] * 60));
                } elseif ($this->config['datetimeHoursToArchive']) {
                    $this->piVars['pS'] = ($this->SIM_ACCESS_TIME - ($this->config['datetimeHoursToArchive'] * 3600));
                } else {
                    $this->piVars['pS'] = ($this->SIM_ACCESS_TIME - ($this->config['datetimeDaysToArchive'] * 86400));
                }
            }
        }

        if (($this->piVars['pS'] ?? false) && !($this->piVars['pL'] ?? false)) {
            $noPeriod = 1; // override the period length checking in getSelectConf
        }

        if (($this->conf['displayCurrentRecord'] ?? false) && $this->tt_news_uid) {
            $this->pid_list = $this->cObj->data['pid'];
            $where = 'AND tt_news.uid=' . $this->tt_news_uid;
        }

        if ($excludeUids) {
            $where = ' AND tt_news.uid NOT IN (' . $excludeUids . ')';
        }

        // build parameter Array for List query
        $selectConf = $this->getSelectConf($where, $noPeriod);

        // performing query to count all news (we need to know it for browsing):
        if ($selectConf['leftjoin'] ?? false || ($this->theCode == 'RELATED' && $this->relNewsUid)) {
            $selectConf['selectFields'] = 'COUNT(DISTINCT tt_news.uid) as c';
        } else {
            $selectConf['selectFields'] = 'COUNT(tt_news.uid) as c';
        }

        $newsCount = 0;
        $countSelConf = $selectConf;
        unset($countSelConf['orderBy']);

        if (($res = $this->exec_getQuery('tt_news', $countSelConf)->fetchAssociative())) {
            $newsCount = $res['c'];
        }

        $this->newsCount = $newsCount;

        // Only do something if the query result is not empty
        if ($newsCount > 0) {
            // Init Templateparts: $t['total'] is complete template subpart (TEMPLATE_LATEST f.e.)
            // $t['item'] is an array with the alternative subparts (NEWS, NEWS_1, NEWS_2 ...)
            $t = [];
            $t['total'] = $this->getNewsSubpart($this->templateCode, $this->spMarker('###' . $templateName . '###'));

            $t['item'] = $this->getLayouts($t['total'], $this->alternatingLayouts, 'NEWS');

            // Parse out markers in the templates to prevent unnecessary queries and code from executing
            $this->renderMarkers = $this->getMarkers($t['total']);

            // build query for display:
            if ($selectConf['leftjoin'] ?? false || ($this->theCode == 'RELATED' && $this->relNewsUid)) {
                $selectConf['selectFields'] = 'DISTINCT tt_news.uid, tt_news.*';
            } else {
                $selectConf['selectFields'] = 'tt_news.*';
            }

            // exclude the LATEST template from changing its content with the pagebrowser. This can be overridden by setting the conf var latestWithPagebrowser
            if ($this->theCode != 'LATEST' || ($this->conf['latestWithPagebrowser'] ?? null)) {
                $selectConf['begin'] = (int)($this->piVars[$this->pointerName] ?? 0) * $this->config['limit'];
            }

            $selectConf['max'] = $this->config['limit'];

            // Reset:
            $subpartArray = [];
            $wrappedSubpartArray = [];
            $markerArray = [];

            // get the list of news items and fill them in the CONTENT subpart
            $subpartArray['###CONTENT###'] = $this->getListContent($t['item'], $selectConf, $prefix_display);

            if ($this->isRenderMarker('###NEWS_CATEGORY_ROOTLINE###')) {
                $markerArray['###NEWS_CATEGORY_ROOTLINE###'] = '';
                if ($this->conf['catRootline.']['showCatRootline'] && $this->piVars['cat'] && !strpos(
                    (string)$this->piVars['cat'],
                    ','
                )) {
                    $markerArray['###NEWS_CATEGORY_ROOTLINE###'] = $this->getCategoryPath([
                        ['catid' => (int)($this->piVars['cat'])],
                    ]);
                }
            }

            if ($theCode == 'XML') {
                $markerArray = $this->getXmlHeader();
                $subpartArray['###HEADER###'] = $this->markerBasedTemplateService->substituteMarkerArray($this->getNewsSubpart(
                    $t['total'],
                    '###HEADER###'
                ), $markerArray);
                if ($this->conf['displayXML.']['xmlFormat']) {
                    if (!empty($this->rdfToc)) {
                        $markerArray['###NEWS_RDF_TOC###'] = '<rdf:Seq>' . "\n" . $this->rdfToc . "\t\t\t" . '</rdf:Seq>';
                    } else {
                        $markerArray['###NEWS_RDF_TOC###'] = '';
                    }
                }
            }

            $markerArray['###GOTOARCHIVE###'] = $this->pi_getLL('goToArchive');
            $markerArray['###LATEST_HEADER###'] = $this->pi_getLL('latestHeader');
            $archiveTypoLink = $this->local_cObj->typolink('|||', $this->conf['archiveTypoLink.']);
            $wrappedSubpartArray['###LINK_ARCHIVE###'] = explode('|||', $archiveTypoLink);
            // unset pagebrowser markers
            $markerArray['###LINK_PREV###'] = '';
            $markerArray['###LINK_NEXT###'] = '';
            $markerArray['###BROWSE_LINKS###'] = '';

            // get generic markers
            $this->getGenericMarkers($markerArray);

            // render a pagebrowser if needed
            if ($newsCount > $this->config['limit'] && !$this->config['noPageBrowser']) {
                $pbConf = $this->conf['pageBrowser.'];
                // configure pagebrowser vars
                $this->internal['res_count'] = $newsCount;
                $this->internal['results_at_a_time'] = $this->config['limit'];
                $this->internal['maxPages'] = $pbConf['maxPages'];

                if (!$pbConf['showPBrowserText']) {
                    $this->overrideLL('pi_list_browseresults_page', ' ');
                }
                if ($this->conf['userPageBrowserFunc'] ?? false) {
                    $markerArray = $this->userProcess('userPageBrowserFunc', $markerArray);
                } else {
                    $this->pi_alwaysPrev = $pbConf['alwaysPrev'] ?? false;
                    if ($this->conf['usePiBasePagebrowser'] && $this->isRenderMarker('###BROWSE_LINKS###')) {
                        $markerArray = $this->getPagebrowserContent($markerArray, $pbConf, $this->pointerName);
                    } else {
                        $markerArray['###BROWSE_LINKS###'] = $this->makePageBrowser(
                            $pbConf['showResultCount'] ?? null,
                            $pbConf['tableParams'] ?? '',
                            $this->pointerName
                        );
                    }
                }
            }

            // Adds hook for processing of extra global markers
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraGlobalMarkerHook'] ?? null)) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraGlobalMarkerHook'] as $_classRef) {
                    $_procObj = GeneralUtility::makeInstance($_classRef);
                    $markerArray = $_procObj->extraGlobalMarkerProcessor($this, $markerArray);
                }
            }
            if (!$this->useFluidRenderer) {
                $content .= $this->markerBasedTemplateService->substituteMarkerArrayCached(
                    $t['total'],
                    $markerArray,
                    $subpartArray,
                    $wrappedSubpartArray
                );
            }
        } elseif (str_contains($where, '1=0')) {
            // first view of the search page with the parameter 'emptySearchAtStart' set
            $markerArray['###SEARCH_EMPTY_MSG###'] = $this->local_cObj->stdWrap(
                $this->pi_getLL('searchEmptyMsg'),
                $this->conf['searchEmptyMsg_stdWrap.']
            );
            $searchEmptyMsg = $this->getNewsSubpart(
                $this->templateCode,
                $this->spMarker('###TEMPLATE_SEARCH_EMPTY###')
            );

            $content .= $this->markerBasedTemplateService->substituteMarkerArrayCached($searchEmptyMsg, $markerArray);
        } elseif ($this->piVars['swords'] ?? null) {
            // no results
            $markerArray['###SEARCH_EMPTY_MSG###'] = $this->local_cObj->stdWrap(
                $this->pi_getLL('noResultsMsg'),
                $this->conf['searchEmptyMsg_stdWrap.']
            );
            $searchEmptyMsg = $this->getNewsSubpart(
                $this->templateCode,
                $this->spMarker('###TEMPLATE_SEARCH_EMPTY###')
            );
            $content .= $this->markerBasedTemplateService->substituteMarkerArrayCached($searchEmptyMsg, $markerArray);
        } elseif ($theCode == 'XML') {
            // fill at least the template header
            // Init Templateparts: $t['total'] is complete template subpart (TEMPLATE_LATEST f.e.)
            $t = [];
            $t['total'] = $this->getNewsSubpart($this->templateCode, $this->spMarker('###' . $templateName . '###'));

            $this->renderMarkers = $this->getMarkers($t['total']);

            // Reset:
            $subpartArray = [];
            // header data
            $markerArray = $this->getXmlHeader();
            $subpartArray['###HEADER###'] = $this->markerBasedTemplateService->substituteMarkerArray($this->getNewsSubpart(
                $t['total'],
                '###HEADER###'
            ), $markerArray);
            // substitute the xml declaration (it's not included in the subpart ###HEADER###)
            $t['total'] = $this->markerBasedTemplateService->substituteMarkerArray($t['total'], [
                '###XML_DECLARATION###' => $markerArray['###XML_DECLARATION###'],
            ]);
            $t['total'] = $this->markerBasedTemplateService->substituteMarkerArray(
                $t['total'],
                ['###SITE_LANG###' => $markerArray['###SITE_LANG###']]
            );
            $t['total'] = $this->markerBasedTemplateService->substituteSubpart(
                $t['total'],
                '###HEADER###',
                $subpartArray['###HEADER###'],
                0
            );
            $t['total'] = $this->markerBasedTemplateService->substituteSubpart($t['total'], '###CONTENT###', '', 0);

            $content .= $t['total'];
        } elseif ($this->arcExclusive && ($this->piVars['pS'] ?? null) && $this->sys_language_content) {
            $markerArray = [];
            // this matches if a user has switched languages within a archive period that contains no items in the desired language
            $content .= $this->local_cObj->stdWrap(
                $this->pi_getLL('noNewsForArchPeriod'),
                $this->conf['noNewsToListMsg_stdWrap.']
            );
        } else {
            $markerArray = [];
            $content .= $this->local_cObj->stdWrap(
                $this->pi_getLL('noNewsToListMsg'),
                $this->conf['noNewsToListMsg_stdWrap.'] ?? []
            );
        }

        if ($this->conf['useFluidRendering']) {
            if (!isset($markerArray)) {
                $markerArray = [];
            }

            $globalMarkerArray = array_merge_recursive($markerArray, ($searchMarkers ?? []));

            $content = $this->renderFluidContent([
                'news' => $this->listData,
                'globalMarkerArray' => $this->getFluidMarkerArray($globalMarkerArray),
            ]);
        }

        return $content;
    }

    /**
     * @param $markerArray
     * @param $pbConf
     * @param $pointerName
     *
     * @return mixed
     */
    public function getPagebrowserContent($markerArray, $pbConf, $pointerName)
    {
        $this->internal['pagefloat'] = $pbConf['pagefloat'];
        $this->internal['showFirstLast'] = $pbConf['showFirstLast'];
        $this->internal['showRange'] = $pbConf['showRange'];
        $this->internal['dontLinkActivePage'] = $pbConf['dontLinkActivePage'];

        $wrapArrFields = explode(
            ',',
            'disabledLinkWrap,inactiveLinkWrap,activeLinkWrap,browseLinksWrap,showResultsWrap,showResultsNumbersWrap,browseBoxWrap'
        );
        $wrapArr = [];
        foreach ($wrapArrFields as $key) {
            if ($pbConf[$key]) {
                $wrapArr[$key] = $pbConf[$key];
            }
        }

        $tmpPS = false;
        $tmpPL = false;
        if ($this->conf['useHRDates']) {
            // prevent adding pS & pL to pagebrowser links if useHRDates is enabled
            $tmpPS = $this->piVars['pS'] ?? null;
            unset($this->piVars['pS']);
            $tmpPL = $this->piVars['pL'] ?? null;
            unset($this->piVars['pL']);
        }

        if ($this->allowCaching) {
            // if there is a GETvar in the URL that is not in this list, caching will be disabled for the pagebrowser links
            $this->pi_isOnlyFields = $pointerName . ',tt_news,year,month,day,pS,pL,arc,cat';

            $pi_isOnlyFieldsArr = explode(',', $this->pi_isOnlyFields);
            $highestVal = 0;
            foreach ($pi_isOnlyFieldsArr as $v) {
                if (($this->piVars[$v] ?? 0) > $highestVal) {
                    $highestVal = $this->piVars[$v];
                }
            }
            $this->pi_lowerThan = $highestVal + 1;
        }

        // render pagebrowser
        $markerArray['###BROWSE_LINKS###'] = $this->pi_list_browseresults(
            $pbConf['showResultCount'],
            $pbConf['tableParams'] ?? null,
            $wrapArr,
            $pointerName,
            $pbConf['hscText']
        );

        if ($this->conf['useHRDates']) {
            // restore pS & pL
            if ($tmpPS) {
                $this->piVars['pS'] = $tmpPS;
            }
            if ($tmpPL) {
                $this->piVars['pL'] = $tmpPL;
            }
        }

        return $markerArray;
    }

    /**
     * get the content for a news item NOT displayed as single item (List & Latest)
     *
     * @param    array  $itemparts      : parts of the html template
     * @param    array  $selectConf     : quety parameters in an array
     * @param    string $prefix_display : the part of the TS-setup
     *
     * @return    string        $itemsOut: itemlist as htmlcode
     */
    protected function getListContent($itemparts, $selectConf, $prefix_display)
    {
        $limit = $this->config['limit'];

        $lConf = $this->conf[$prefix_display . '.'];
        $res = $this->exec_getQuery('tt_news', $selectConf); //get query for list contents

        // make some final config manipulations
        // overwrite image sizes from TS with the values from content-element if they exist.
        if ($this->config['FFimgH'] || $this->config['FFimgW']) {
            $lConf['image.']['file.']['maxW'] = $this->config['FFimgW'];
            $lConf['image.']['file.']['maxH'] = $this->config['FFimgH'];
        }
        if ($this->config['croppingLenght']) {
            $lConf['subheader_stdWrap.']['crop'] = $this->config['croppingLenght'];
        }

        $this->splitLConf = [];
        $cropSuffix = false;
        if ($this->conf['enableOptionSplit']) {
            if ($this->config['croppingLenghtOptionSplit']) {
                $crop = $lConf['subheader_stdWrap.']['crop'];
                if ($this->config['croppingLenght']) {
                    $crop = $this->config['croppingLenght'];
                }
                $cparts = explode('|', (string)$crop);
                if (is_array($cparts)) {
                    $cropSuffix = '|' . $cparts[1] . ($cparts[2] ? '|' . $cparts[2] : '');
                }
                $lConf['subheader_stdWrap.']['crop'] = $this->config['croppingLenghtOptionSplit'];
            }
            $resCount = $this->db->count($res);
            $this->splitLConf = $this->processOptionSplit($lConf, $limit, $resCount);
        }

        $itemsOut = '';
        $itempartsCount = count($itemparts);
        $pTmp = $GLOBALS['TSFE']->config['config']['ATagParams'] ?? '';
        $cc = 0;

        $piVarsArray = [
            'backPid' => ($this->conf['dontUseBackPid'] ? null : $this->config['backPid']),
            'year' => ($this->conf['dontUseBackPid'] ? null : ($this->piVars['year'] ?: null)),
            'month' => ($this->conf['dontUseBackPid'] ? null : ($this->piVars['month'] ?: null)),
        ];

        // needed for external renderer
        $this->listData = [];

        // Getting elements
        while (($row = $this->db->sql_fetch_assoc($res))) {
            // gets the option-splitted config for this record
            if ($this->conf['enableOptionSplit'] && !empty($this->splitLConf[$cc])) {
                $lConf = $this->splitLConf[$cc];
                $lConf['subheader_stdWrap.']['crop'] = ($lConf['subheader_stdWrap.']['crop'] ?? '') . $cropSuffix;
            }

            $wrappedSubpartArray = [];
            $titleField = $lConf['linkTitleField'] ?? '';

            // First get workspace/version overlay:
            if ($this->versioningEnabled) {
                $this->tsfe->sys_page->versionOL('tt_news', $row);
            }
            // Then get localization of record:
            if ($this->sys_language_content) {
                $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');
                $row = $this->tsfe->sys_page->getRecordOverlay(
                    'tt_news',
                    $row,
                    $this->sys_language_content,
                    $languageAspect->getLegacyOverlayType()
                );
            }

            // Register displayed news item globally:
            $GLOBALS['T3_VAR']['displayedNews'][] = $row['uid'];

            $this->tsfe->config['config']['ATagParams'] = $pTmp . ' title="' . $this->local_cObj->stdWrap(
                trim(htmlspecialchars($row[$titleField] ?? '')),
                $lConf['linkTitleField.'] ?? []
            ) . '"';

            if ($this->conf[$prefix_display . '.']['catOrderBy'] ?? false) {
                $this->config['catOrderBy'] = $this->conf[$prefix_display . '.']['catOrderBy'];
            }

            $this->categories = [];
            $this->categories[$row['uid']] = $this->getCategories($row['uid']);

            $catSPid = false;
            if ($row['type'] == 1 || $row['type'] == 2) {
                // News type article or external url
                $this->local_cObj->setCurrentVal($row['type'] == 1 ? $row['page'] : $row['ext_url']);
                $pageTypoLink = $this->local_cObj->typolink('|||', $this->conf['pageTypoLink.']);
                $wrappedSubpartArray['###LINK_ITEM###'] = explode('|||', $pageTypoLink);

                // fill the link string in a register to access it from TS
                $this->local_cObj->cObjGetSingle('LOAD_REGISTER', [
                    'newsMoreLink' => $this->local_cObj->typolink($this->pi_getLL('more'), $this->conf['pageTypoLink.']),
                ]);
            } else {
                //  Overwrite the singlePid from config-array with a singlePid given from the first entry in $this->categories
                if ($this->conf['useSPidFromCategory'] && is_array($this->categories)) {
                    $tmpcats = $this->categories;
                    if (is_array($tmpcats[$row['uid']])) {
                        $catSPid = array_shift($tmpcats[$row['uid']]);
                    }
                }
                $singlePid = ($catSPid['single_pid'] ?? false) ?: $this->config['singlePid'];
                $wrappedSubpartArray['###LINK_ITEM###'] = $this->getSingleViewLink($singlePid, $row, $piVarsArray);
            }

            // reset ATagParams
            $this->tsfe->config['config']['ATagParams'] = $pTmp;
            $markerArray = $this->getItemMarkerArray($row, $lConf, $prefix_display);

            // XML
            if ($this->theCode == 'XML') {
                if ($row['type'] == 2) {
                    // external URL
                    $exturl = trim(str_contains(
                        (string)$row['ext_url'],
                        'http://'
                    ) ? $row['ext_url'] : 'http://' . $row['ext_url']);
                    $exturl = (strpos($exturl, ' ') ? substr($exturl, 0, strpos($exturl, ' ')) : $exturl);
                    $rssUrl = $exturl;
                } elseif ($row['type'] == 1) {
                    // internal URL
                    $rssUrl = $this->pi_getPageLink($row['page'], '');
                    if (!str_contains($rssUrl, '://')) {
                        $rssUrl = $this->config['siteUrl'] . $rssUrl;
                    }
                } else {
                    // News detail link
                    $link = $this->getSingleViewLink($singlePid, $row, $piVarsArray, true);
                    $rssUrl = trim(!str_contains($link, '://') ? $this->config['siteUrl'] : '') . $link;
                }
                // replace square brackets [] in links with their URLcodes and replace the &-sign with its ASCII code
                $rssUrl = preg_replace(['/\[/', '/\]/', '/&/'], ['%5B', '%5D', '&#38;'], (string)$rssUrl);
                $markerArray['###NEWS_LINK###'] = $rssUrl;

                if ($this->conf['displayXML.']['xmlFormat'] == 'rdf') {
                    $this->rdfToc .= "\t\t\t\t" . '<rdf:li resource="' . $rssUrl . '" />' . "\n";
                }
            }

            $layoutNum = ($itempartsCount == 0 ? 0 : ($cc % $itempartsCount));
            if (!$this->useFluidRenderer) {
                // Store the result of template parsing in the Var $itemsOut, use the alternating layouts
                $itemsOut .= $this->markerBasedTemplateService->substituteMarkerArrayCached(
                    $itemparts[$layoutNum],
                    $markerArray,
                    [],
                    $wrappedSubpartArray
                );
            }

            $this->listData[] = [
                'row' => $row,
                'markerArray' => $this->getFluidMarkerArray($markerArray),
                'categories' => $this->categories[$row['uid']],
                'images' => $this->images,
                'files' => $this->files,
            ];

            $cc++;
            if ($cc == $limit) {
                break;
            }
        }

        return $itemsOut;
    }

    protected function getFluidMarkerArray($markerArray): array
    {
        $fluidMarkerArray = [];
        if (!empty($markerArray)) {
            foreach ($markerArray as $key => $value) {
                $markerName = str_replace('###', '', (string)$key);
                $fluidMarkerArray[$markerName] = $value;
            }
        }

        return $fluidMarkerArray;
    }

    /**
     * Displays the "single view" of a news article. Is also used when displaying single news records with the "insert
     * records" content element.
     *
     * @return    string        html-code for the "single view"
     */
    public function displaySingle()
    {
        $lConf = $this->conf['displaySingle.'];
        $content = '';
        $selectConf = [];
        $selectConf['selectFields'] = '*';
        $selectConf['fromTable'] = 'tt_news';
        $selectConf['where'] = 'tt_news.uid=' . $this->tt_news_uid;
        $selectConf['where'] .= $this->enableFields;

        // function Hook for processing the selectConf array
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['sViewSelectConfHook'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['sViewSelectConfHook'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $selectConf = $_procObj->processSViewSelectConfHook($this, $selectConf);
            }
        }

        $res = $this->db->exec_SELECTquery(
            $selectConf['selectFields'] ?? '',
            $selectConf['fromTable'] ?? '',
            $selectConf['where'] ?? '',
            $selectConf['groupBy'] ?? '',
            $selectConf['orderBy'] ?? '',
            $selectConf['limit'] ?? ''
        );

        $row = $this->db->sql_fetch_assoc($res);

        // First get workspace/version overlay:
        if ($this->versioningEnabled) {
            $this->tsfe->sys_page->versionOL('tt_news', $row);
        }
        // Then get localization of record:
        // (if the content language is not the default language)
        if (!empty($row) && $this->sys_language_content) {
            $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');
            $row = $this->tsfe->sys_page->getRecordOverlay(
                'tt_news',
                $row,
                $this->sys_language_content,
                $languageAspect->getLegacyOverlayType()
            );
        }

        // Adds hook for processing of extra item array
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraItemArrayHook'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraItemArrayHook'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $row = $_procObj->extraItemArrayProcessor($row, $lConf, $this);
            }
        }

        // Register displayed news item globally:
        $GLOBALS['T3_VAR']['displayedNews'][] = $row['uid'] ?? '';
        $markerArray = [];

        if (is_array($row) && ($row['pid'] > 0 || $this->vPrev)) { // never display versions of a news record (having pid=-1) for normal website users
            $this->fluidVars['mode'] = 'display';

            // If type is 1 or 2 (internal/external link), redirect to accordant page:
            if (is_array($row) && GeneralUtility::inList('1,2', $row['type'])) {
                $redirectUrl = $this->local_cObj->getTypoLink_URL(
                    $row['type'] == 1 ? $row['page'] : $row['ext_url']
                );
                $responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
                $response = $responseFactory
                    ->createResponse()
                    ->withAddedHeader('location', $redirectUrl);
                throw new PropagateResponseException($response);
            }
            $item = false;
            // reset marker array
            $wrappedSubpartArray = [];

            if (!$this->useFluidRenderer) {
                // Get the subpart code
                if ($this->conf['displayCurrentRecord'] ?? false) {
                    $item = trim($this->getNewsSubpart(
                        $this->templateCode,
                        $this->spMarker('###TEMPLATE_SINGLE_RECORDINSERT###'),
                        $row
                    ));
                }

                if (!$item) {
                    $item = $this->getNewsSubpart(
                        $this->templateCode,
                        $this->spMarker('###TEMPLATE_' . $this->theCode . '###'),
                        $row
                    );
                }

                // build the backToList link
                if ($this->conf['useHRDates']) {
                    $wrappedSubpartArray['###LINK_ITEM###'] = explode('|', $this->pi_linkTP_keepPIvars('|', [
                        'tt_news' => null,
                        'backPid' => null,
                        $this->config['singleViewPointerName'] => null,
                        'pS' => null,
                        'pL' => null,
                    ], $this->allowCaching, ($this->conf['dontUseBackPid'] ? 1 : 0), $this->config['backPid']));
                } else {
                    $wrappedSubpartArray['###LINK_ITEM###'] = explode('|', $this->pi_linkTP_keepPIvars('|', [
                        'tt_news' => null,
                        'backPid' => null,
                        $this->config['singleViewPointerName'] => null,
                    ], $this->allowCaching, ($this->conf['dontUseBackPid'] ? 1 : 0), $this->config['backPid']));
                }
            }

            $this->renderMarkers = $this->getMarkers($item);

            // set the title of the single view page to the title of the news record
            if ($this->conf['substitutePagetitle']) {

                $this->tsfe->page['title'] = $row['title'];
                // set pagetitle for indexed search to news title

                // fixme: still needed ?
//                $this->tsfe->indexedDocTitle = $row['title'];
            }
            if ($lConf['catOrderBy'] ?? false) {
                $this->config['catOrderBy'] = $lConf['catOrderBy'];
            }
            $this->categories = [];
            $this->categories[$row['uid']] = $this->getCategories($row['uid']);

            $markerArray = $this->getItemMarkerArray($row, $lConf, 'displaySingle');
            if (!$this->useFluidRenderer) {
                // Substitute
                $content = $this->markerBasedTemplateService->substituteMarkerArrayCached(
                    $item,
                    $markerArray,
                    [],
                    $wrappedSubpartArray
                );
            }
        } elseif ($this->sys_language_mode == 'strict' && $this->tt_news_uid && $this->sys_language_content) {
            // not existing translation
            if ($this->conf['redirectNoTranslToList']) {
                // redirect to list page
                $this->pi_linkToPage(' ', $this->conf['backPid']);

                $redirectUrl = $this->cObj->lastTypoLinkResult->getUrl();
                $responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
                $response = $responseFactory
                    ->createResponse()
                    ->withAddedHeader('location', $redirectUrl);
                throw new PropagateResponseException($response);
            }

            $this->fluidVars['mode'] = 'noTranslation';
            $noTranslMsg = $this->local_cObj->stdWrap(
                $this->pi_getLL('noTranslMsg'),
                $this->conf['noNewsIdMsg_stdWrap.']
            );
            $content = $noTranslMsg;
        } elseif ($row['pid'] ?? 0 < 0) {
            // a non-public version of a record was requested
            $this->fluidVars['mode'] = 'nonPlublicVersion';
            $nonPlublicVersion = $this->local_cObj->stdWrap(
                $this->pi_getLL('nonPlublicVersionMsg'),
                $this->conf['nonPlublicVersionMsg_stdWrap.'] ?? []
            );
            $content = $nonPlublicVersion;
        } else {
            // if singleview is shown with no tt_news uid given from GETvars (&tx_ttnews[tt_news]=) an error message is displayed.
            $this->fluidVars['mode'] = 'noNewsId';
            $noNewsIdMsg = $this->local_cObj->stdWrap(
                $this->pi_getLL('noNewsIdMsg'),
                $this->conf['noNewsIdMsg_stdWrap.']
            );
            $content = $noNewsIdMsg;
        }

        if ($this->conf['useFluidRendering'] && is_array($row)) {
            $content = $this->renderFluidContent([
                'row' => $row,
                'markerArray' => $this->getFluidMarkerArray($markerArray),
                'vars' => $this->fluidVars,
                'categories' => $this->categories[$row['uid'] ?? null],
                'images' => $this->images,
                'files' => $this->files,
            ]);
        }

        return $content;
    }

    /**
     * generates the News archive menu
     *
     * @return    string        html code of the archive menu
     */
    public function displayArchiveMenu()
    {
        $t = [];
        $markerArray = [];
        $this->arcExclusive = 1;
        $selectConf = $this->getSelectConf('', 1);
        $selectConf['where'] .= $this->enableFields;

        // Finding maximum and minimum values:
        $row = $this->getArchiveMenuRange($selectConf);

        if ($row['minval'] || $row['maxval']) {
            $dateArr = [];
            $arcMode = $this->config['archiveMode'];
            $c = 0;
            $theDate = 0;
            while ($theDate < $row['maxval']) {
                switch ($arcMode) {
                    case 'month' :
                        $theDate = mktime(0, 0, 0, date('m', $row['minval']) + $c, 1, date('Y', $row['minval']));
                        break;
                    case 'quarter' :
                        $theDate = mktime(
                            0,
                            0,
                            0,
                            floor(date('m', $row['minval']) / 3) + 1 + (3 * $c),
                            1,
                            date('Y', $row['minval'])
                        );
                        break;
                    case 'year' :
                        $theDate = mktime(0, 0, 0, 1, 1, date('Y', $row['minval']) + $c);
                        break;
                    default:
                        break;
                }
                $dateArr[] = $theDate;
                $c++;

                //TODO: Put this limit into configuration value or class constant!
                if ($c > 1000) {
                    break;
                }
            }

            if ($selectConf['pidInList']) {
                $selectConf['where'] .= ' AND tt_news.pid IN (' . $selectConf['pidInList'] . ')';
            }
            $tmpWhere = $selectConf['where'];
            $cachedPeriodAccum = false;
            $storeKey = false;
            if ($this->cache_amenuPeriods) {
                $storeKey = md5(serialize([
                    $this->catExclusive,
                    $this->config['catSelection'] ?? '',
                    $this->sys_language_content,
                    $selectConf['pidInList'],
                    $arcMode,
                ]));
                $cachedPeriodAccum = $this->cache->get($storeKey);
            }

            if (0 && !empty($cachedPeriodAccum)) {
                $periodAccum = $cachedPeriodAccum;
            } else {
                $periodAccum = [];
                foreach ($dateArr as $k => $v) {
                    $periodInfo = [];
                    $periodInfo['start'] = $v;
                    $periodInfo['active'] = (($this->piVars['pS'] ?? 0) == $v ? 1 : 0);
                    $periodInfo['stop'] = ($dateArr[$k + 1] ?? 0) - 1;
                    $periodInfo['HRstart'] = date('d-m-Y', $periodInfo['start']);
                    $periodInfo['HRstop'] = date('d-m-Y', $periodInfo['stop']);
                    $periodInfo['quarter'] = floor(date('m', $v) / 3) + 1;

                    $select_fields = 'COUNT(DISTINCT tt_news.uid) AS c';
                    $from_table = 'tt_news';
                    $join = (($selectConf['leftjoin'] ?? false) ? ' LEFT JOIN ' . $selectConf['leftjoin'] : '');
                    $where_clause = $tmpWhere . ' AND tt_news.datetime>=' . $periodInfo['start'] . ' AND tt_news.datetime<' . $periodInfo['stop'];

                    $res = $this->db->exec_SELECTquery($select_fields, $from_table . $join, $where_clause);

                    $row = $this->db->sql_fetch_assoc($res);

                    $periodInfo['count'] = $row['c'];

                    if (!$this->conf['archiveMenuNoEmpty'] || $periodInfo['count']) {
                        $periodAccum[] = $periodInfo;
                    }
                }
                if ($this->cache_amenuPeriods && count($periodAccum)) {
                    $this->cache->set($storeKey, $periodAccum, [__FUNCTION__]);
                }
            }

            // get template subpart
            $t['total'] = $this->getNewsSubpart($this->templateCode, $this->spMarker('###TEMPLATE_ARCHIVE###'));
            $t['item'] = $this->getLayouts($t['total'], $this->alternatingLayouts, 'MENUITEM');

            $this->renderMarkers = $this->getMarkers($t['total']);

            $tCount = count($t['item']);
            $cc = 0;

            $veryLocal_cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            // reverse amenu order if 'reverseAMenu' is given
            if ($this->conf['reverseAMenu']) {
                arsort($periodAccum);
            }

            $archiveLink = $this->conf['archiveTypoLink.']['parameter'];
            $archiveLink = ($archiveLink ?: $this->tsfe->id);

            $this->conf['parent.']['addParams'] = ($this->conf['archiveTypoLink.']['addParams'] ?? null);
            $amenuLinkCat = null;

            if (!$this->conf['disableCategoriesInAmenuLinks']) {
                if ($this->piVars_catSelection && ($this->config['amenuWithCatSelector'] ?? false)) {
                    // use the catSelection from piVars only if 'amenuWithCatSelector' is given.
                    $amenuLinkCat = $this->piVars_catSelection;
                } else {
                    $amenuLinkCat = $this->actuallySelectedCategories;
                }
            }

            $itemsOutArr = [];
            $oldyear = 0;
            $itemsOut = '';
            foreach ($periodAccum as $pArr) {
                $wrappedSubpartArray = [];
                $markerArray = [];

                $year = date('Y', $pArr['start']);
                if ($this->conf['useHRDates']) {
                    $month = date('m', $pArr['start']);
                    if ($arcMode == 'year') {
                        $archLinkArr = $this->pi_linkTP_keepPIvars(
                            '|',
                            ['cat' => $amenuLinkCat, 'year' => $year],
                            $this->allowCaching,
                            1,
                            $archiveLink
                        );
                    } else {
                        $archLinkArr = $this->pi_linkTP_keepPIvars(
                            '|',
                            ['cat' => $amenuLinkCat, 'year' => $year, 'month' => $month],
                            $this->allowCaching,
                            1,
                            $archiveLink
                        );
                    }
                    $wrappedSubpartArray['###LINK_ITEM###'] = explode('|', $archLinkArr);
                } else {
                    $wrappedSubpartArray['###LINK_ITEM###'] = explode('|', $this->pi_linkTP_keepPIvars('|', [
                        'cat' => $amenuLinkCat,
                        'pS' => $pArr['start'],
                        'pL' => ($pArr['stop'] - $pArr['start']),
                        'arc' => 1,
                    ], $this->allowCaching, 1, $archiveLink));
                }

                $yearTitle = '';
                if ($this->conf['showYearHeadersInAmenu'] && $arcMode != 'year' && $year != $oldyear) {
                    if ($pArr['start'] < 20000) {
                        $yearTitle = 'no date';
                    } else {
                        $yearTitle = $year;
                    }
                    $oldyear = $year;
                }

                $veryLocal_cObj->start($pArr, 'tt_news');

                $markerArray['###ARCHIVE_YEAR###'] = '';
                if ($yearTitle) {
                    $markerArray['###ARCHIVE_YEAR###'] = $veryLocal_cObj->stdWrap(
                        $yearTitle,
                        $this->conf['archiveYear_stdWrap.']
                    );
                }

                $markerArray['###ARCHIVE_TITLE###'] = $veryLocal_cObj->cObjGetSingle(
                    $this->conf['archiveTitleCObject'],
                    $this->conf['archiveTitleCObject.'],
                    'archiveTitleCObject'
                );
                $markerArray['###ARCHIVE_COUNT###'] = $pArr['count'];
                $markerArray['###ARCHIVE_ITEMS###'] = ($pArr['count'] == 1 ? $this->pi_getLL('archiveItem') : $this->pi_getLL('archiveItems'));
                $markerArray['###ARCHIVE_ACTIVE###'] = (($this->piVars['pS'] ?? 0) == $pArr['start'] ? $this->conf['archiveActiveMarkerContent'] : '');

                $layoutNum = ($tCount == 0 ? 0 : ($cc % $tCount));
                $amenuitem = $this->markerBasedTemplateService->substituteMarkerArrayCached(
                    $t['item'][$layoutNum],
                    $markerArray,
                    [],
                    $wrappedSubpartArray
                );

                if (($this->conf['newsAmenuUserFunc'] ?? false) || ($this->conf['useFluidRendering'] ?? false)) {
                    // fill the generated data to an array to pass it to a userfuction as a single variable
                    $itemsOutArr[] = [
                        'html' => $amenuitem,
                        'data' => $pArr,
                        'markerArray' => $this->getFluidMarkerArray($markerArray),
                    ];
                } else {
                    $itemsOut .= $amenuitem;
                }
                $cc++;
            }

            // Pass to user defined function
            if ($this->conf['newsAmenuUserFunc'] ?? false) {
                $tmpItemsArr = false;
                $itemsOutArr = $this->userProcess('newsAmenuUserFunc', $itemsOutArr);
                foreach ($itemsOutArr as $itemHtml) {
                    $tmpItemsArr[] = $itemHtml['html'];
                }
                if (is_array($tmpItemsArr)) {
                    $itemsOut = implode('', $tmpItemsArr);
                }
            }

            // Reset:
            $subpartArray = [];
            $wrappedSubpartArray = [];
            $markerArray = [];
            $markerArray['###ARCHIVE_HEADER###'] = $this->local_cObj->stdWrap(
                $this->pi_getLL('archiveHeader'),
                $this->conf['archiveHeader_stdWrap.'] ?? false
            );
            // Set content
            $subpartArray['###CONTENT###'] = $itemsOut;
            $content = $this->markerBasedTemplateService->substituteMarkerArrayCached(
                $t['total'],
                $markerArray,
                $subpartArray,
                $wrappedSubpartArray
            );
        } else {
            // if nothing is found in the archive display the TEMPLATE_ARCHIVE_NOITEMS message
            $markerArray['###ARCHIVE_HEADER###'] = $this->local_cObj->stdWrap(
                $this->pi_getLL('archiveHeader'),
                $this->conf['archiveHeader_stdWrap.'] ?? false
            );
            $markerArray['###ARCHIVE_EMPTY_MSG###'] = $this->local_cObj->stdWrap(
                $this->pi_getLL('archiveEmptyMsg'),
                $this->conf['archiveEmptyMsg_stdWrap.']
            );
            $noItemsMsg = $this->getNewsSubpart($this->templateCode, $this->spMarker('###TEMPLATE_ARCHIVE_NOITEMS###'));
            $content = $this->markerBasedTemplateService->substituteMarkerArrayCached($noItemsMsg, $markerArray);
        }

        if ($this->conf['useFluidRendering'] ?? false) {
            $content = $this->renderFluidContent([
                'itemsOutArr' => $itemsOutArr,
                'globalMarkerArray' => $this->getFluidMarkerArray($markerArray),
                'categories' => $this->categories[$row['uid']],
            ]);
        }

        return $content;
    }

    /**
     * Displays a hirarchical menu from tt_news categories
     *
     * @return    string        html for the category menu
     */
    public function displayCatMenu()
    {
        $content = '';
        $lConf = $this->conf['displayCatMenu.'];
        $mode = $lConf['mode'] ?: 'tree';
        $this->dontStartFromRootRecord = false;

        $this->initCatmenuEnv($lConf);
        switch ($mode) {
            case 'nestedWraps':
                $fields = '*';
                $lConf = $this->conf['displayCatMenu.'];
                $addCatlistWhere = '';
                if ($this->dontStartFromRootRecord) {
                    $addCatlistWhere = 'tt_news_cat.uid IN (' . implode(',', $this->cleanedCategoryMounts) . ')';
                }
                $res = $this->db->exec_SELECTquery(
                    $fields,
                    'tt_news_cat',
                    ($this->dontStartFromRootRecord ? $addCatlistWhere : 'tt_news_cat.parent_category=0') . $this->SPaddWhere . $this->enableCatFields . $this->catlistWhere,
                    '',
                    'tt_news_cat.' . $this->config['catOrderBy']
                );

                $cArr = [];
                if (!$lConf['hideCatmenuHeader']) {
                    $cArr[] = $this->local_cObj->stdWrap(
                        $this->pi_getLL('catmenuHeader', 'Select a category:'),
                        $lConf['catmenuHeader_stdWrap.']
                    );
                }
                while (($row = $this->db->sql_fetch_assoc($res))) {
                    $cArr[] = $row;
                    $subcats = $this->helpers->getSubCategoriesForMenu($row['uid'], $fields, $this->catlistWhere);
                    if (count($subcats)) {
                        $cArr[] = $subcats;
                    }
                }
                $content = $this->getCatMenuContent($cArr, $lConf);

                if ($this->conf['useFluidRendering']) {
                    $content = $this->renderFluidContent([
                        'mode' => $mode,
                        'content' => $content,
                        'categories' => $cArr,
                    ]);
                }

                break;
            case 'tree':
            case 'ajaxtree':
                /** @var Catmenu $catTreeObj */
                $catTreeObj = GeneralUtility::makeInstance(Catmenu::class);
                if ($mode == 'ajaxtree') {
                    $this->getPageRenderer()->addJsFooterFile('EXT:tt_news/Resources/Public/JavaScript/NewsCatmenu.js');
                }
                $catTreeObj->init($this);
                $catTreeObj->treeObj->FE_USER = &$this->tsfe->fe_user;

                $content = '<div id="ttnews-cat-tree">' . $catTreeObj->treeObj->getBrowsableTree() . '</div>';

                if ($this->conf['useFluidRendering']) {
                    $content = $this->renderFluidContent([
                        'mode' => $mode,
                        'content' => $content,
                        'categories' => $catTreeObj->treeObj->tree,
                    ]);
                }

                break;
            default:
                // hook for user catmenu
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['userDisplayCatmenuHook'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['userDisplayCatmenuHook'] as $_classRef) {
                        $_procObj = GeneralUtility::makeInstance($_classRef);
                        $content .= $_procObj->userDisplayCatmenu($lConf, $this);
                    }
                }

                if ($this->conf['useFluidRendering']) {
                    $content = $this->renderFluidContent([
                        'mode' => $mode,
                        'content' => $content,
                    ]);
                }

                break;
        }

        return $this->local_cObj->stdWrap($content, $lConf['catmenu_stdWrap.']);
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        if ($this->pageRenderer === null) {
            $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        }

        return $this->pageRenderer;
    }

    /**********************************************************************************************
     *
     *              Process Markers
     *
     **********************************************************************************************/

    /**
     * Fills in the markerArray with data for a news item
     *
     * @param array  $row   result row for a news item
     * @param array  $lConf conf vars for the current template
     * @param string $textRenderObj
     *
     * @return array|mixed filled marker array
     */
    protected function getItemMarkerArray($row, $lConf, $textRenderObj = 'displaySingle')
    {
        $this->local_cObj->start($row, 'tt_news');

        $markerArray = array_flip($this->renderMarkers);

        foreach ($markerArray as &$marker) {
            $marker = '';
        }

        // get image markers
        if ($this->isRenderMarker('###NEWS_IMAGE###') || $textRenderObj == 'displaySingle') {
            $markerArray = $this->getImageMarkers($markerArray, $row, $lConf, $textRenderObj);
        }

        if ($textRenderObj == 'displaySingle' && ($this->isRenderMarker('###NEXT_ARTICLE###') || $this->isRenderMarker('###PREV_ARTICLE###'))) {
            $markerArray = $this->getPrevNextLinkMarkers($row, $lConf, $markerArray);
        }

        // get category markers
        if ($this->categories[$row['uid']] && ($this->isRenderMarker('###NEWS_CATEGORY_ROOTLINE###') || $this->isRenderMarker('###NEWS_CATEGORY_IMAGE###') || $this->isRenderMarker('###NEWS_CATEGORY###') || $this->isRenderMarker('###TEXT_CAT###') || $this->isRenderMarker('###TEXT_CAT_LATEST###'))) {
            if ($this->conf['catRootline.']['showCatRootline'] && $this->isRenderMarker('###NEWS_CATEGORY_ROOTLINE###')) {
                $markerArray['###NEWS_CATEGORY_ROOTLINE###'] = $this->getCategoryPath($this->categories[$row['uid']]);
            }
            // get markers and links for categories
            $markerArray = $this->getCatMarkerArray($markerArray, $row, $lConf);
        }

        $markerArray['###NEWS_UID###'] = $row['uid'];

        // show language label and/or flag
        // @todo: remove/replace legacy langArr
        // if ($this->isRenderMarker('###NEWS_LANGUAGE###')) {
        //     if ($this->conf['showLangLabels']) {
        //         $markerArray['###NEWS_LANGUAGE###'] = $this->langArr[$row['sys_language_uid']]['title'];
        //     }

        //     if ($this->langArr[$row['sys_language_uid']]['flag'] && $this->conf['showFlags']) {
        //         $fImgFile = ($this->conf['flagPath'] ?: 'media/flags/flag_') . $this->langArr[$row['sys_language_uid']]['flag'];
        //         $fImgConf = $this->conf['flagImage.'];
        //         $fImgConf['file'] = $fImgFile;
        //         $flagImg = $this->local_cObj->cObjGetSingle('IMAGE', $fImgConf);
        //         $markerArray['###NEWS_LANGUAGE###'] .= $flagImg;
        //     }
        // }

        if ($row['title'] && $this->isRenderMarker('###NEWS_TITLE###')) {
            $markerArray['###NEWS_TITLE###'] = $this->local_cObj->stdWrap($row['title'], $lConf['title_stdWrap.'] ?? []);
        }
        if ($row['author'] && $this->isRenderMarker('###NEWS_AUTHOR###')) {
            $newsAuthor = $this->local_cObj->stdWrap($row['author'] ? $this->local_cObj->stdWrap(
                $this->pi_getLL('preAuthor'),
                $lConf['preAuthor_stdWrap.'] ?? []
            ) . $row['author'] : '', $lConf['author_stdWrap.'] ?? []);
            $markerArray['###NEWS_AUTHOR###'] = $this->formatStr($newsAuthor);
        }
        if ($row['author_email'] && $this->isRenderMarker('###NEWS_EMAIL###')) {
            $markerArray['###NEWS_EMAIL###'] = $this->local_cObj->stdWrap(
                $row['author_email'],
                $lConf['email_stdWrap.'] ?? []
            );
        }

        if ($row['datetime']) {
            if ($this->isRenderMarker('###NEWS_DATE###')) {
                $markerArray['###NEWS_DATE###'] = $this->local_cObj->stdWrap($row['datetime'], $lConf['date_stdWrap.'] ?? []);
            }
            if ($this->isRenderMarker('###NEWS_TIME###')) {
                $markerArray['###NEWS_TIME###'] = $this->local_cObj->stdWrap($row['datetime'], $lConf['time_stdWrap.'] ?? []);
            }
            if ($this->isRenderMarker('###NEWS_AGE###')) {
                $markerArray['###NEWS_AGE###'] = $this->local_cObj->stdWrap($row['datetime'], $lConf['age_stdWrap.'] ?? []);
            }
            if ($this->isRenderMarker('###TEXT_NEWS_AGE###')) {
                $markerArray['###TEXT_NEWS_AGE###'] = $this->local_cObj->stdWrap(
                    $this->pi_getLL('textNewsAge'),
                    $lConf['textNewsAge_stdWrap.'] ?? []
                );
            }
        }

        if ($this->isRenderMarker('###NEWS_SUBHEADER###') && (!($this->piVars[$this->config['singleViewPointerName']] ?? false) || ($this->conf['subheaderOnAllSViewPages'] ?? false))) {
            $markerArray['###NEWS_SUBHEADER###'] = $this->formatStr($this->local_cObj->stdWrap(
                $row['short'],
                $lConf['subheader_stdWrap.']
            ));
        }
        if ($row['keywords'] && $this->isRenderMarker('###NEWS_KEYWORDS###')) {
            $markerArray['###NEWS_KEYWORDS###'] = $this->local_cObj->stdWrap($row['keywords'], $lConf['keywords_stdWrap.'] ?? []);
        }

        if (!($this->piVars[$this->config['singleViewPointerName']] ?? false) && $textRenderObj == 'displaySingle') {
            // load the keywords in the register 'newsKeywords' to access it from TS
            $this->local_cObj->cObjGetSingle(
                'LOAD_REGISTER',
                ['newsKeywords' => $row['keywords'], 'newsSubheader' => $row['short']]
            );
        }

        $sViewPagebrowser = false;
        $newscontent = false;
        if ($this->isRenderMarker('###NEWS_CONTENT###')) {
            if ($textRenderObj == 'displaySingle' && !$row['no_auto_pb'] && $this->config['maxWordsInSingleView'] > 1 && $this->config['useMultiPageSingleView']) {
                $row['bodytext'] = $this->helpers->insertPagebreaks(
                    $row['bodytext'],
                    is_countable(GeneralUtility::trimExplode(' ', $row['short'], 1)) ? count(GeneralUtility::trimExplode(' ', $row['short'], 1)) : 0
                );
            }

            if ($this->config['pageBreakToken'] != '' && strpos((string)$row['bodytext'], (string)$this->config['pageBreakToken'])) {
                if ($this->config['useMultiPageSingleView'] && $textRenderObj == 'displaySingle') {
                    $tmp = $this->helpers->makeMultiPageSView($row['bodytext'], $lConf);
                    $newscontent = $tmp[0];
                    $sViewPagebrowser = $tmp[1];
                } else {
                    $newscontent = $this->formatStr($this->local_cObj->stdWrap(preg_replace(
                        '/' . $this->config['pageBreakToken'] . '/',
                        '',
                        (string)$row['bodytext']
                    ), $lConf['content_stdWrap.']));
                }
            } else {
                $newscontent = $this->formatStr($this->local_cObj->stdWrap($row['bodytext'], $lConf['content_stdWrap.'] ?? []));
            }
            if ($this->conf['appendSViewPBtoContent']) {
                $newscontent = $newscontent . $sViewPagebrowser;
                $sViewPagebrowser = '';
            }
        }

        $markerArray['###NEWS_CONTENT###'] = $newscontent;
        $markerArray['###NEWS_SINGLE_PAGEBROWSER###'] = $sViewPagebrowser;

        if ($this->isRenderMarker('###MORE###')) {
            $markerArray['###MORE###'] = $this->pi_getLL('more');
        }
        // get title (or its language overlay) of the page where the backLink points to (this is done only in single view)
        if ($this->config['backPid'] && $textRenderObj == 'displaySingle' && $this->isRenderMarker('###BACK_TO_LIST###')) {
            $backPtitle = $this->getPageArrayEntry($this->config['backPid'], 'title');

            $markerArray['###BACK_TO_LIST###'] = sprintf(
                $this->pi_getLL('backToList', ''),
                $backPtitle
            );
        }

        // get related news
        $relatedNews = false;
        if ($this->isRenderMarker('###TEXT_RELATED###') || $this->isRenderMarker('###NEWS_RELATED###')) {
            if ($this->conf['renderRelatedNewsAsList'] ?? false) {
                $relatedNews = $this->getRelatedNewsAsList($row['uid']);
            } else {
                $relatedNews = $this->getRelated($row['uid']);
            }

            if ($relatedNews) {
                $rel_stdWrap = GeneralUtility::trimExplode(
                    '|',
                    $this->conf['related_stdWrap.']['wrap']
                );
                $markerArray['###TEXT_RELATED###'] = $rel_stdWrap[0] . $this->local_cObj->stdWrap(
                    $this->pi_getLL('textRelated'),
                    $this->conf['relatedHeader_stdWrap.']
                );
                $markerArray['###NEWS_RELATED###'] = $relatedNews . $rel_stdWrap[1];
            }
        }

        // Links
        $newsLinks = false;
        $links = trim((string)$row['links']);
        if ($links && ($this->isRenderMarker('###TEXT_LINKS###') || $this->isRenderMarker('###NEWS_LINKS###'))) {
            $links_stdWrap = GeneralUtility::trimExplode('|', $lConf['links_stdWrap.']['wrap'] ?? '');
            $links_stdWrap_begin = $links_stdWrap[0] ?? '';
            $links_stdWrap_end = $links_stdWrap[1] ?? '';
            $newsLinks = $this->local_cObj->stdWrap($this->formatStr($row['links']), $lConf['linksItem_stdWrap.'] ?? []);
            $markerArray['###TEXT_LINKS###'] = $links_stdWrap_begin . $this->local_cObj->stdWrap($this->pi_getLL('textLinks'), $lConf['linksHeader_stdWrap.'] ?? []);
            $markerArray['###NEWS_LINKS###'] = $newsLinks . $links_stdWrap_end;
        }
        // filelinks
        if ($row['news_files'] && ($this->isRenderMarker('###TEXT_FILES###') || $this->isRenderMarker('###FILE_LINK###') || $this->theCode == 'XML')) {
            $this->getFileLinks($markerArray, $row);
        }

        // show news with the same categories in SINGLE view
        if ($textRenderObj == 'displaySingle' && $this->conf['showRelatedNewsByCategory'] && (is_countable($this->categories[$row['uid']]) ? count($this->categories[$row['uid']]) : 0)
            && ($this->isRenderMarker('###NEWS_RELATEDBYCATEGORY###') || $this->isRenderMarker('###TEXT_RELATEDBYCATEGORY###'))) {
            $this->getRelatedNewsByCategory($markerArray, $row);
        }

        // the markers: ###ADDINFO_WRAP_B### and ###ADDINFO_WRAP_E### are only inserted, if there are any files, related news or links
        if ($relatedNews || $newsLinks || ($markerArray['###FILE_LINK###'] ?? false) || ($markerArray['###NEWS_RELATEDBYCATEGORY###'] ?? false)) {
            $addInfo_stdWrap = GeneralUtility::trimExplode('|', $lConf['addInfo_stdWrap.']['wrap'] ?? '');
            $markerArray['###ADDINFO_WRAP_B###'] = $addInfo_stdWrap[0] ?? '';
            $markerArray['###ADDINFO_WRAP_E###'] = $addInfo_stdWrap[1] ?? '';
        }

        // Page fields:
        if ($this->isRenderMarker('###PAGE_UID###')) {
            $markerArray['###PAGE_UID###'] = $row['pid'];
        }

        if ($this->isRenderMarker('###PAGE_TITLE###')) {
            $markerArray['###PAGE_TITLE###'] = $this->getPageArrayEntry($row['pid'], 'title');
        }
        if ($this->isRenderMarker('###PAGE_AUTHOR###')) {
            $markerArray['###PAGE_AUTHOR###'] = $this->local_cObj->stdWrap($this->getPageArrayEntry($row['pid'], 'author'), $lConf['author_stdWrap.'] ?? []);
        }
        if ($this->isRenderMarker('###PAGE_AUTHOR_EMAIL###')) {
            $markerArray['###PAGE_AUTHOR_EMAIL###'] = $this->local_cObj->stdWrap($this->getPageArrayEntry($row['pid'], 'author_email'), $lConf['email_stdWrap.'] ?? []);
        }

        // XML
        if ($this->theCode == 'XML') {
            $this->getXmlMarkers($markerArray, $row, $lConf);
        }

        $this->getGenericMarkers($markerArray);
        //		debug($markerArray, ' ('.__CLASS__.'::'.__FUNCTION__.')', __LINE__, __FILE__, 3);

        // Adds hook for processing of extra item markers
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraItemMarkerHook'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraItemMarkerHook'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $markerArray = $_procObj->extraItemMarkerProcessor($markerArray, $row, $lConf, $this);
            }
        }
        // Pass to userdefined function
        if ($this->conf['itemMarkerArrayFunc'] ?? '') {
            $markerArray = $this->userProcess('itemMarkerArrayFunc', $markerArray);
        }

        return $markerArray;
    }

    /**
     * @param $markerArray
     * @param $row
     * @param $lConf
     */
    protected function getXmlMarkers(&$markerArray, $row, $lConf)
    {
        $markerArray['###NEWS_TITLE###'] = $this->helpers->cleanXML($this->local_cObj->stdWrap(
            $row['title'],
            $lConf['title_stdWrap.']
        ));
        $markerArray['###NEWS_AUTHOR###'] = $row['author_email'] ? '<author>' . $row['author_email'] . '</author>' : '';
        if ($this->conf['displayXML.']['xmlFormat'] == 'atom03' || $this->conf['displayXML.']['xmlFormat'] == 'atom1') {
            $markerArray['###NEWS_AUTHOR###'] = $row['author'];
        }

        if ($this->conf['displayXML.']['xmlFormat'] == 'rss2' || $this->conf['displayXML.']['xmlFormat'] == 'rss091') {
            $markerArray['###NEWS_SUBHEADER###'] = $this->helpers->cleanXML($this->local_cObj->stdWrap(
                $row['short'],
                $lConf['subheader_stdWrap.']
            ));
        } elseif ($this->conf['displayXML.']['xmlFormat'] == 'atom03' || $this->conf['displayXML.']['xmlFormat'] == 'atom1') {
            //html doesn't need to be striped off in atom feeds
            $lConf['subheader_stdWrap.']['stripHtml'] = 0;
            $markerArray['###NEWS_SUBHEADER###'] = $this->local_cObj->stdWrap(
                $row['short'],
                $lConf['subheader_stdWrap.']
            );
            //just removing some whitespace to ease atom feed building
            $markerArray['###NEWS_SUBHEADER###'] = str_replace('\n', '', (string)$markerArray['###NEWS_SUBHEADER###']);
            $markerArray['###NEWS_SUBHEADER###'] = str_replace('\r', '', $markerArray['###NEWS_SUBHEADER###']);
        }

        if ($this->conf['displayXML.']['xmlFormat'] == 'rss2' || $this->conf['displayXML.']['xmlFormat'] == 'rss091') {
            $markerArray['###NEWS_DATE###'] = date('D, d M Y H:i:s O', $row['datetime']);
        } elseif ($this->conf['displayXML.']['xmlFormat'] == 'atom03' || $this->conf['displayXML.']['xmlFormat'] == 'atom1') {
            $markerArray['###NEWS_DATE###'] = $this->helpers->getW3cDate($row['datetime']);
        }
        //dates for atom03
        $markerArray['###NEWS_CREATED###'] = $this->helpers->getW3cDate($row['crdate']);
        $markerArray['###NEWS_MODIFIED###'] = $this->helpers->getW3cDate($row['tstamp']);

        if ($this->conf['displayXML.']['xmlFormat'] == 'atom03' && !empty($this->conf['displayXML.']['xmlLang'])) {
            $markerArray['###SITE_LANG###'] = ' xml:lang="' . $this->conf['displayXML.']['xmlLang'] . '"';
        }

        $markerArray['###NEWS_ATOM_ENTRY_ID###'] = 'tag:' . substr((string)$this->config['siteUrl'], 11, -1) . ',' . date(
            'Y',
            $row['crdate']
        ) . ':article' . $row['uid'];
        $markerArray['###SITE_LINK###'] = $this->config['siteUrl'];
    }

    /**
     * @param $markerArray
     * @param $row
     */
    protected function getFileLinks(&$markerArray, $row)
    {
        $files_stdWrap = GeneralUtility::trimExplode(
            '|',
            $this->conf['newsFiles_stdWrap.']['wrap']
        );
        $markerArray['###TEXT_FILES###'] = $files_stdWrap[0] . $this->local_cObj->stdWrap(
            $this->pi_getLL('textFiles'),
            $this->conf['newsFilesHeader_stdWrap.']
        );
        $this->files = null;

        $filesPath = trim((string)$this->conf['newsFiles.']['path']);

        if (MathUtility::canBeInterpretedAsInteger($row['news_files'])) {
            // seems that tt_news files have been migrated to FAL
            $filesPath = '';
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            $fileObjects = $fileRepository->findByRelation('tt_news', 'news_files', $row['uid']);
            $this->files = $fileObjects;

            if (!empty($fileObjects)) {
                $falFiles = [];
                $falFilesTitles = [];
                foreach ($fileObjects as $fileObject) {
                    /** @var FileInterface $fileObject */
                    $publicUrl = $fileObject->getPublicUrl();
                    $falFiles[] = $fileObject->getPublicUrl();
                    $falFilesTitles[$publicUrl] = $fileObject->getProperty('title');
                }
                if (!empty($falFiles)) {
                    $row['news_files'] = implode(',', $falFiles);
                }
            }
        }

        $fileArr = explode(',', (string)$row['news_files']);
        $filelinks = '';
        $rss2Enclousres = '';
        foreach ($fileArr as $val) {
            // fills the marker ###FILE_LINK### with the links to the atached files
            $fileName = ($falFilesTitles[$val] != '' ? $falFilesTitles[$val] : basename($val));
            $filelinks .= $this->local_cObj->stdWrap(
                $this->local_cObj->typoLink($fileName, ['parameter' => $filesPath . $val]),
                $this->conf['newsFiles.']['stdWrap.']
            );

            // <enclosure> support for RSS 2.0
            if ($this->theCode == 'XML') {
                $theFile = $filesPath . $val;

                if (@is_file(Environment::getPublicPath().$theFile)) {
                    $fileURL = $this->config['siteUrl'] . $theFile;
                    $fileSize = filesize(Environment::getPublicPath().$theFile);
                    $fileMimeType = $this->getMimeTypeByHttpRequest($fileURL);

                    $rss2Enclousres .= '<enclosure url="' . $fileURL . '" ';
                    $rss2Enclousres .= 'length ="' . $fileSize . '" ';
                    $rss2Enclousres .= 'type="' . $fileMimeType . '" />' . "\n\t\t\t";
                }
            }
        }
        $markerArray['###FILE_LINK###'] = $filelinks . $files_stdWrap[1];
        $markerArray['###NEWS_RSS2_ENCLOSURES###'] = trim($rss2Enclousres);
    }

    /**
     * @param $markerArray
     * @param $row
     *
     */
    protected function getRelatedNewsByCategory(&$markerArray, $row)
    {
        // save some variables which are used to build the backLink to the list view
        $tmpcatExclusive = $this->catExclusive;
        $tmparcExclusive = $this->arcExclusive;
        $tmpcode = $this->theCode;
        $tmpBrowsePage = (int)($this->piVars['pointer'] ?? 0);
        unset($this->piVars['pointer']);
        $tmpPS = (int)($this->piVars['pS'] ?? 0);
        unset($this->piVars['pS']);
        $tmpPL = (int)($this->piVars['pL'] ?? 0);
        unset($this->piVars['pL']);

        $confSave = $this->conf;
        $configSave = $this->config;
        $tmp_renderMarkers = $this->renderMarkers;
        $local_cObjSave = clone $this->local_cObj;

        ArrayUtility::mergeRecursiveWithOverrule(
            $this->conf,
            $this->conf['relNewsByCategory.'] ?: []
        );
        $this->config = $this->conf;
        $this->config['catOrderBy'] = $configSave['catOrderBy'];

        $this->arcExclusive = $this->conf['archive'];
        $this->LOCAL_LANG_loaded = false;
        $this->pi_loadLL(); // Loading language-labels

        if ($this->conf['code']) {
            $this->theCode = strtoupper((string)$this->conf['code']);
        }

        if (is_array($this->categories[$row['uid']])) {
            $this->catExclusive = implode(',', array_keys($this->categories[$row['uid']]));
        }

        $relNewsByCat = trim($this->displayList($row['uid']));

        if ($relNewsByCat) {
            $cat_rel_stdWrap = GeneralUtility::trimExplode(
                '|',
                $this->conf['relatedByCategory_stdWrap.']['wrap']
            );
            $lbl = $this->pi_getLL('textRelatedByCategory');
            $markerArray['###TEXT_RELATEDBYCATEGORY###'] = $cat_rel_stdWrap[0] . $this->local_cObj->stdWrap(
                $lbl,
                $this->conf['relatedByCategoryHeader_stdWrap.']
            );
            $markerArray['###NEWS_RELATEDBYCATEGORY###'] = $relNewsByCat . $cat_rel_stdWrap[1];
        }

        // restore variables
        $this->conf = $confSave;
        $this->config = $configSave;
        $this->theCode = $tmpcode;
        $this->catExclusive = $tmpcatExclusive;
        $this->arcExclusive = $tmparcExclusive;
        $this->piVars['pointer'] = $tmpBrowsePage;
        $this->piVars['pS'] = $tmpPS;
        $this->piVars['pL'] = $tmpPL;
        $this->local_cObj = $local_cObjSave;
        $this->renderMarkers = $tmp_renderMarkers;

        unset($confSave, $configSave, $local_cObjSave);
    }

    /**
     * @param $markerArray
     */
    protected function getGenericMarkers(&$markerArray)
    {
        $lConf = $this->genericMarkerConf;

        if (!is_array($lConf)) {
            return;
        }
        foreach ($lConf as $mName => $renderObj) {
            $genericMarker = '###GENERIC_' . strtoupper($mName) . '###';

            if (!is_array($lConf[$mName . '.'] ?? null) || !$this->isRenderMarker($genericMarker)) {
                continue;
            }

            $markerArray[$genericMarker] = $this->local_cObj->cObjGetSingle(
                $renderObj,
                $lConf[$mName . '.'],
                'tt_news generic marker: ' . $mName
            );
        }
    }

    protected function initGenericMarkers()
    {
        if (is_array($this->conf['genericmarkers.'] ?? false)) {
            $this->genericMarkerConf = $this->conf['genericmarkers.'];

            // merge with special configuration (based on current CODE [SINGLE, LIST, LATEST]) if this is available
            if (is_array($this->genericMarkerConf[$this->theCode . '.'] ?? null)) {
                ArrayUtility::mergeRecursiveWithOverrule(
                    $this->genericMarkerConf,
                    $this->genericMarkerConf[$this->theCode . '.']
                );
            }
        }
    }

    /**
     * @param $row
     * @param $lConf
     * @param $markerArray
     *
     * @return mixed
     */
    protected function getPrevNextLinkMarkers($row, $lConf, $markerArray)
    {
        $tmpA = $this->arcExclusive;
        $this->arcExclusive = 0;
        $tmpExclItems = $this->conf['excludeAlreadyDisplayedNews'];
        $this->conf['excludeAlreadyDisplayedNews'] = 0;

        $selectConf = $this->getSelectConf('');

        $selectConf['where'] .= $this->enableFields;
        if ($selectConf['pidInList']) {
            $selectConf['where'] .= ' AND tt_news.pid IN (' . $selectConf['pidInList'] . ')';
        }

        $fN = ($lConf['nextPrevRecSortingField'] ?: 'datetime');
        $fV = $row[$fN];

        $swap = (bool)$lConf['reversePrevNextOrder'];
        $prev = $this->getPrevNextRec(!$swap, $selectConf, $fN, $fV);
        if (is_array($prev)) {
            $markerArray['###PREV_ARTICLE###'] = $this->getPrevNextLink($prev, $lConf);
        }

        $next = $this->getPrevNextRec($swap, $selectConf, $fN, $fV);
        if (is_array($next)) {
            $markerArray['###NEXT_ARTICLE###'] = $this->getPrevNextLink($next, $lConf, 'next');
        }

        // reset
        $this->arcExclusive = $tmpA;
        $this->conf['excludeAlreadyDisplayedNews'] = $tmpExclItems;

        return $markerArray;
    }

    /**
     * @param        $rec
     * @param        $lConf
     * @param string $p
     *
     * @return string
     */
    protected function getPrevNextLink($rec, $lConf, $p = 'prev')
    {
        $title = $rec['title'];
        $pTmp = $GLOBALS['TSFE']->config['config']['ATagParams'] ?? '';
        $this->tsfe->config['config']['ATagParams'] = $pTmp . ' title="' . $this->local_cObj->stdWrap(
            trim(htmlspecialchars((string)$title)),
            $lConf[$p . 'LinkTitle_stdWrap.']
        ) . '"';
        $link = $this->getSingleViewLink($this->tsfe->id, $rec, []);

        if ($lConf['showTitleAsPrevNextLink']) {
            $lbl = $title;
        } else {
            $lbl = $this->pi_getLL($p . 'Article');
        }

        $lbl = $this->local_cObj->stdWrap($lbl, $lConf[$p . 'LinkLabel_stdWrap.']);

        $this->tsfe->config['config']['ATagParams'] = $pTmp;

        return $this->local_cObj->stdWrap($link[0] . $lbl . $link[1], $lConf[$p . 'Link_stdWrap.']);
    }

    /**
     * @param        $getPrev
     * @param array  $selectConf
     * @param string $fN
     *
     * @return mixed
     */
    protected function getPrevNextRec($getPrev, $selectConf, $fN, mixed $fV)
    {
        $row = $this->db->exec_SELECTgetSingleRow(
            'tt_news.uid, tt_news.title, tt_news.' . $fN . ($fN == 'datetime' ? '' : ', tt_news.datetime'),
            'tt_news' . ($selectConf['leftjoin'] ?? [] ? ' LEFT JOIN ' . $selectConf['leftjoin'] : ''),
            $selectConf['where'] . ' AND tt_news.' . $fN . ($getPrev ? '<' : '>') . '"' . $fV . '"',
            '',
            'tt_news.' . $fN . ($getPrev ? ' DESC' : ' ASC')
        );

        /**
         * TODO: 05.05.2009
         * lang overlay
         */

        return $row;
    }

    /**
     * Fills in the Category markerArray with data
     *
     * @param    array $markerArray : partly filled marker array
     * @param    array $row         : result row for a news item
     * @param    array $lConf       : configuration for the current templatepart
     *
     * @return    array        $markerArray: filled markerarray
     */
    protected function getCatMarkerArray($markerArray, $row, $lConf)
    {
        $pTmp = $GLOBALS['TSFE']->config['config']['ATagParams'] ?? '';
        if ((is_countable($this->categories[$row['uid']]) ? count($this->categories[$row['uid']]) : 0) && ($this->config['catImageMode'] || $this->config['catTextMode'])) {
            // wrap for all categories
            $cat_stdWrap = GeneralUtility::trimExplode(
                '|',
                $lConf['category_stdWrap.']['wrap'] ?? ''
            );
            $markerArray['###CATWRAP_B###'] = $cat_stdWrap[0] ?? '';
            $markerArray['###CATWRAP_E###'] = $cat_stdWrap[1] ?? '';
            $markerArray['###TEXT_CAT###'] = $this->pi_getLL('textCat');
            $markerArray['###TEXT_CAT_LATEST###'] = $this->pi_getLL('textCatLatest');

            $news_category = [];
            $theCatImgCodeArray = [];
            $catTextLenght = 0;
            $wroteRegister = false;

            $catSelLinkParams = ($this->conf['catSelectorTargetPid'] ? ($this->conf['itemLinkTarget'] ? $this->conf['catSelectorTargetPid'] . ' ' . $this->conf['itemLinkTarget'] : $this->conf['catSelectorTargetPid']) : $this->tsfe->id);

            foreach ($this->categories[$row['uid']] as $val) {
                // find categories, wrap them with links and collect them in the array $news_category.
                $catTitle = htmlspecialchars((string)$val['title']);
                $this->tsfe->config['config']['ATagParams'] = $pTmp . ' title="' . $catTitle . '"';
                $titleWrap = ($val['parent_category'] > 0 ? 'subCategoryTitleItem_stdWrap.' : 'categoryTitleItem_stdWrap.');
                if ($this->config['catTextMode'] == 0) {
                    $markerArray['###NEWS_CATEGORY###'] = '';
                } elseif ($this->config['catTextMode'] == 1) {
                    // display but don't link
                    $news_category[] = $this->local_cObj->stdWrap($catTitle, $lConf[$titleWrap] ?? false);
                } elseif ($this->config['catTextMode'] == 2) {
                    // link to category shortcut
                    $news_category[] = $this->local_cObj->stdWrap($this->pi_linkToPage(
                        $catTitle,
                        $val['shortcut'],
                        $val['shortcut_target']
                    ), $lConf[$titleWrap] ?? false);
                } elseif ($this->config['catTextMode'] == 3) {
                    // act as category selector

                    if ($this->conf['useHRDates']) {
                        $news_category[] = $this->local_cObj->stdWrap($this->pi_linkTP_keepPIvars($catTitle, [
                            'cat' => $val['catid'],
                            'year' => ($this->piVars['year'] ?: null),
                            'month' => ($this->piVars['month'] ?: null),
                            'backPid' => null,
                            $this->pointerName => null,
                        ], $this->allowCaching, 0, $catSelLinkParams), $lConf[$titleWrap]);
                    } else {
                        $news_category[] = $this->local_cObj->stdWrap($this->pi_linkTP_keepPIvars($catTitle, [
                            'cat' => $val['catid'],
                            'backPid' => null,
                            $this->pointerName => null,
                        ], $this->allowCaching, 0, $catSelLinkParams), $lConf[$titleWrap]);
                    }
                }

                $catTextLenght += strlen($catTitle);
                if ($this->config['catImageMode'] == 0 || empty($val['image'])) {
                    $markerArray['###NEWS_CATEGORY_IMAGE###'] = '';
                } else {
                    $catPicConf = [];
                    $imgPath = 'uploads/pics/';
                    if (MathUtility::canBeInterpretedAsInteger($val['image'])) {
                        // seems that tt_news_cat images have been migrated to FAL
                        $imgPath = '';
                        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                        $fileObjects = $fileRepository->findByRelation('tt_news_cat', 'image', $val['uid']);
                        if (!empty($fileObjects)) {
                            $falImages = [];
                            foreach ($fileObjects as $fileObject) {
                                /** @var FileInterface $fileObject */
                                $falImages[] = $fileObject->getPublicUrl();
                            }
                            if (!empty($falImages)) {
                                $val['image'] = implode(',', $falImages);
                            }
                        }
                    }

                    $catPicConf['image.']['file'] = $imgPath . $val['image'];
                    $catPicConf['image.']['file.']['maxW'] = (int)($this->config['catImageMaxWidth']);
                    $catPicConf['image.']['file.']['maxH'] = (int)($this->config['catImageMaxHeight']);
                    $catPicConf['image.']['stdWrap.']['spaceAfter'] = 0;
                    // clear the imagewrap to prevent category image from beeing wrapped in a table
                    $lConf['imageWrapIfAny'] = '';
                    if ($this->config['catImageMode'] != 1) {
                        if ($this->config['catImageMode'] == 2) {
                            // link to category shortcut
                            $sCpageId = $val['shortcut'];
                            // get the title of the shortcut page
                            $sCpage = $this->pi_getRecord('pages', $sCpageId);
                            $catPicConf['image.']['altText'] = $sCpage['title'] ? $this->pi_getLL('altTextCatShortcut') . $sCpage['title'] : '';
                            $catPicConf['image.']['stdWrap.']['innerWrap'] = $this->pi_linkToPage(
                                '|',
                                $val['shortcut'],
                                $this->conf['itemLinkTarget']
                            );
                        }

                        if ($this->config['catImageMode'] == 3) {
                            // act as category selector
                            $catPicConf['image.']['altText'] = $this->pi_getLL('altTextCatSelector') . $catTitle;
                            if ($this->conf['useHRDates']) {
                                $catPicConf['image.']['stdWrap.']['innerWrap'] = $this->pi_linkTP_keepPIvars('|', [
                                    'cat' => $val['catid'],
                                    'year' => ($this->piVars['year'] ?: null),
                                    'month' => ($this->piVars['month'] ?: null),
                                    'backPid' => null,
                                    $this->pointerName => null,
                                ], $this->allowCaching, 0, $catSelLinkParams);
                            } else {
                                $catPicConf['image.']['stdWrap.']['innerWrap'] = $this->pi_linkTP_keepPIvars('|', [
                                    'cat' => $val['catid'],
                                    'backPid' => null,
                                    $this->pointerName => null,
                                ], $this->allowCaching, 0, $catSelLinkParams);
                            }
                        }
                    } else {
                        $catPicConf['image.']['altText'] = $val['title'];
                    }

                    // add linked category image to output array
                    $img = $this->local_cObj->cObjGetSingle('IMAGE', $catPicConf['image.']);
                    $swrap = ($val['parent_category'] > 0 ? 'subCategoryImgItem_stdWrap.' : 'categoryImgItem_stdWrap.');
                    $theCatImgCodeArray[] = $this->local_cObj->stdWrap($img, $lConf[$swrap] ?? []);
                }
                if (!$wroteRegister) {
                    // Load the uid of the first assigned category to the register 'newsCategoryUid'
                    $this->local_cObj->cObjGetSingle('LOAD_REGISTER', ['newsCategoryUid' => $val['catid']]);
                    $wroteRegister = true;
                }
            }
            if ($this->config['catTextMode'] != 0) {
                $categoryDivider = $this->local_cObj->stdWrap(
                    $this->conf['categoryDivider'],
                    $this->conf['categoryDivider_stdWrap.']
                );
                $news_category = implode(
                    $categoryDivider,
                    array_slice($news_category, 0, (int)($this->config['maxCatTexts']))
                );
                if ($this->config['catTextLength']) {
                    // crop the complete category titles if 'catTextLength' value is given
                    $markerArray['###NEWS_CATEGORY###'] = (strlen($news_category) < (int)($this->config['catTextLength']) ? $news_category : substr(
                        $news_category,
                        0,
                        (int)($this->config['catTextLength'])
                    ) . '...');
                } else {
                    $markerArray['###NEWS_CATEGORY###'] = $this->local_cObj->stdWrap(
                        $news_category,
                        $lConf['categoryTitles_stdWrap.'] ?? false
                    );
                }
            }
            if ($this->config['catImageMode'] != 0) {
                $theCatImgCode = implode('', array_slice(
                    $theCatImgCodeArray,
                    0,
                    (int)($this->config['maxCatImages'])
                )); // downsize the image array to the 'maxCatImages' value
                $markerArray['###NEWS_CATEGORY_IMAGE###'] = $this->local_cObj->stdWrap(
                    $theCatImgCode,
                    $lConf['categoryImages_stdWrap.'] ?? false
                );
            }
            // XML
            if ($this->theCode == 'XML') {
                $newsCategories = explode(', ', $news_category);

                $xmlCategories = '';
                foreach ($newsCategories as $xmlCategory) {
                    $xmlCategories .= '<category>' . $this->local_cObj->stdWrap(
                        $xmlCategory,
                        $lConf['categoryTitles_stdWrap.'] ?? []
                    ) . '</category>' . "\n\t\t\t";
                }

                $markerArray['###NEWS_CATEGORY###'] = $xmlCategories;
            }
        }
        $this->tsfe->config['config']['ATagParams'] = $pTmp;

        return $markerArray;
    }

    /**
     * Fills the image markers with data. if a userfunction is given in "imageMarkerFunc",
     * the marker Array is processed by this function.
     *
     * @param    array  $markerArray   : partly filled marker array
     * @param    array  $row           : result row for a news item
     * @param    array  $lConf         : configuration for the current templatepart
     * @param    string $textRenderObj : name of the template subpart
     *
     * @return    array        $markerArray: filled markerarray
     */
    protected function getImageMarkers($markerArray, $row, $lConf, $textRenderObj)
    {
        $this->images = null;
        if ($this->conf['imageMarkerFunc'] ?? false) {
            $markerArray = $this->userProcess('imageMarkerFunc', [$markerArray, $lConf]);
        } else {
            $imgPath = 'uploads/pics/';
            if (MathUtility::canBeInterpretedAsInteger($row['image'])) {
                // seems that tt_news images have been migrated to FAL
                $imgPath = '';
                $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                $fileObjects = $fileRepository->findByRelation('tt_news', 'image', $row['uid']);
                $this->images = $fileObjects;
                if (!empty($fileObjects)) {
                    $falImages = [];
                    $falTitles = [];
                    $falAltTexts = [];
                    $falCaptions = [];
                    foreach ($fileObjects as $fileObject) {
                        /** @var FileInterface $fileObject */
                        $falImages[] = $fileObject->getPublicUrl();
                        $falTitles[] = $fileObject->getProperty('title');
                        $falAltTexts[] = $fileObject->getProperty('alternative');
                        $falCaptions[] = $fileObject->getProperty('description');
                    }
                    if (!empty($falImages)) {
                        $row['image'] = implode(',', $falImages);
                        $row['imagetitletext'] = implode(chr(10), $falTitles);
                        $row['imagealttext'] = implode(chr(10), $falAltTexts);
                        $row['imagecaption'] = implode(chr(10), $falCaptions);
                    }
                }
            }

            $imageNum = $lConf['imageCount'] ?? 1;
            $imageNum = MathUtility::forceIntegerInRange($imageNum, 0, 100);
            $theImgCode = '';
            $imgs = GeneralUtility::trimExplode(',', $row['image'], 1);
            $imgsCaptions = explode(chr(10), (string)$row['imagecaption']);
            $imgsAltTexts = explode(chr(10), (string)$row['imagealttext']);
            $imgsTitleTexts = explode(chr(10), (string)$row['imagetitletext']);

            reset($imgs);

            if ($textRenderObj == 'displaySingle') {
                $markerArray = $this->getSingleViewImages(
                    $lConf,
                    $imgs,
                    $imgsCaptions,
                    $imgsAltTexts,
                    $imgsTitleTexts,
                    $imageNum,
                    $markerArray,
                    $imgPath
                );
            } else {
                $imageMode = $textRenderObj == 'displayLatest' ? $lConf['latestImageMode'] : $lConf['listImageMode'] ?? false;

                $suf = '';
                if (!empty($lConf['image.']['file.']['maxW']) && is_numeric(substr((string)$lConf['image.']['file.']['maxW'], -1)) && $imageMode) {
                    // 'm' or 'c' not set by TS
                    switch ($imageMode) {
                        case 'resize2max' :
                            $suf = 'm';
                            break;
                        case 'crop' :
                            $suf = 'c';
                            break;
                        case 'resize' :
                            $suf = '';
                            break;
                        default:
                            break;
                    }
                }

                // only insert width/height if it is not given by TS and width/height is empty
                if ($suf && ($lConf['image.']['file.']['maxW'] ?? false) && !($lConf['image.']['file.']['width'] ?? false)) {
                    $lConf['image.']['file.']['width'] = $lConf['image.']['file.']['maxW'] . $suf;
                    unset($lConf['image.']['file.']['maxW']);
                }
                if ($suf && ($lConf['image.']['file.']['maxH'] ?? false) && !($lConf['image.']['file.']['height'] ?? false)) {
                    $lConf['image.']['file.']['height'] = $lConf['image.']['file.']['maxH'] . $suf;
                    unset($lConf['image.']['file.']['maxH']);
                }

                $cc = 0;
                foreach ($imgs as $val) {
                    if ($cc == $imageNum) {
                        break;
                    }

                    if ($val) {
                        $lConf['image.']['altText'] = $imgsAltTexts[$cc];
                        $lConf['image.']['titleText'] = $imgsTitleTexts[$cc];
                        $lConf['image.']['file'] = $imgPath . $val;

                        $theImgCode .= $this->local_cObj->cObjGetSingle(
                            'IMAGE',
                            $lConf['image.']
                        ) . $this->local_cObj->stdWrap(
                            $imgsCaptions[$cc],
                            $lConf['caption_stdWrap.'] ?? null
                        );
                    }

                    $cc++;
                }

                if ($cc) {
                    $markerArray['###NEWS_IMAGE###'] = $this->local_cObj->wrap($theImgCode, $lConf['imageWrapIfAny'] ?? false);
                } else {
                    $markerArray['###NEWS_IMAGE###'] = $this->local_cObj->stdWrap(
                        $markerArray['###NEWS_IMAGE###'] ?? null,
                        $lConf['image.']['noImage_stdWrap.'] ?? null
                    );
                }
            }
        }

        return $markerArray;
    }

    /**
     * Fills the image markers for the SINGLE view with data. Supports Optionssplit for some parameters
     *
     * @param $lConf
     * @param $imgs
     * @param $imgsCaptions
     * @param $imgsAltTexts
     * @param $imgsTitleTexts
     * @param $imageNum
     * @param $markerArray
     * @param $imgPath
     *
     * @return mixed
     */
    protected function getSingleViewImages($lConf, $imgs, $imgsCaptions, $imgsAltTexts, $imgsTitleTexts, $imageNum, $markerArray, $imgPath)
    {
        $marker = 'NEWS_IMAGE';
        $sViewSplitLConf = [];
        $tmpMarkers = [];
        $iC = is_countable($imgs) ? count($imgs) : 0;

        // remove first img from image array in single view if the TSvar firstImageIsPreview is set
        if (($iC > 1 && $this->config['firstImageIsPreview']) || ($iC >= 1 && $this->config['forceFirstImageIsPreview'])) {
            array_shift($imgs);
            array_shift($imgsCaptions);
            array_shift($imgsAltTexts);
            array_shift($imgsTitleTexts);
            $iC--;
        }

        if ($iC > $imageNum) {
            $iC = $imageNum;
        }

        // get img array parts for single view pages
        if ($this->piVars[$this->config['singleViewPointerName']] ?? false) {
            /**
             * TODO
             * does this work with optionsplit ?
             */
            $spage = $this->piVars[$this->config['singleViewPointerName']];
            $astart = $imageNum * $spage;
            $imgs = array_slice($imgs, $astart, $imageNum);
            $imgsCaptions = array_slice($imgsCaptions, $astart, $imageNum);
            $imgsAltTexts = array_slice($imgsAltTexts, $astart, $imageNum);
            $imgsTitleTexts = array_slice($imgsTitleTexts, $astart, $imageNum);
        }
        $osCount = 0;
        if ($this->conf['enableOptionSplit'] ?? false) {
            if ($lConf['imageMarkerOptionSplit']) {
                $ostmp = explode('|*|', (string)$lConf['imageMarkerOptionSplit']);
                $osCount = count($ostmp);
            }
            $sViewSplitLConf = $this->processOptionSplit($lConf, $iC);
        }
        // reset markers for optionSplitted images
        for ($m = 1; $m <= $imageNum; $m++) {
            $markerArray['###' . $marker . '_' . $m . '###'] = '';
        }

        $cc = 0;
        $theImgCode = '';
        foreach ($imgs as $val) {
            if ($cc == $imageNum) {
                break;
            }
            if ($val) {
                if (!empty($sViewSplitLConf[$cc])) {
                    $lConf = $sViewSplitLConf[$cc];
                }

                $lConf['image.']['altText'] = $imgsAltTexts[$cc];
                $lConf['image.']['titleText'] = $imgsTitleTexts[$cc];
                $lConf['image.']['file'] = $imgPath . $val;

                $imgHtml = $this->local_cObj->cObjGetSingle(
                    'IMAGE',
                    $lConf['image.']
                ) . $this->local_cObj->stdWrap($imgsCaptions[$cc], $lConf['caption_stdWrap.']);

                if ($osCount) {
                    if ($iC > 1) {
                        $mName = '###' . $marker . '_' . $lConf['imageMarkerOptionSplit'] . '###';
                    } else {
                        // fall back to the first image marker if only one image has been found
                        $mName = '###' . $marker . '_1###';
                    }
                    $tmpMarkers[$mName]['html'] .= $imgHtml;
                    $tmpMarkers[$mName]['wrap'] = $lConf['imageWrapIfAny'];
                } else {
                    $theImgCode .= $imgHtml;
                }
            }
            $GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] = $cc + 1;
            $cc++;
        }

        if ($cc) {
            if ($osCount) {
                foreach ($tmpMarkers as $mName => $res) {
                    $markerArray[$mName] = $this->local_cObj->wrap($res['html'], $res['wrap']);
                }
            } else {
                $markerArray['###' . $marker . '###'] = $this->local_cObj->wrap($theImgCode, $lConf['imageWrapIfAny']);
            }
        } else {
            if ($lConf['imageMarkerOptionSplit']) {
                $m = '_1';
            } else {
                $m = '';
            }
            $markerArray['###' . $marker . $m . '###'] = $this->local_cObj->stdWrap(
                $markerArray['###' . $marker . $m . '###'] ?? '',
                $lConf['image.']['noImage_stdWrap.'] ?? []
            );
        }

        return $markerArray;
    }

    /**
     * gets categories and subcategories for a news record
     *
     * @param    int $uid    : uid of the current news record
     * @param    bool    $getAll : ...
     *
     * @return    array        $categories: array of found categories
     */
    public function getCategories($uid, $getAll = false)
    {
        $hash = false;
        $tmpcat = false;
        if ($this->cache_categories) {
            $hash = sha1(serialize([
                $uid,
                $this->config['catOrderBy'],
                $this->enableCatFields,
                $this->SPaddWhere,
                $getAll,
                $this->sys_language_content,
                $this->conf['useSPidFromCategory'],
                $this->conf['useSPidFromCategoryRecusive'],
                $this->conf['displaySubCategories'],
                $this->config['useSubCategories'],
            ]));
            $tmpcat = $this->cache->get($hash);
        }

        if ($tmpcat !== false) {
            $categories = $tmpcat;
        } else {
            if (!$this->config['catOrderBy'] || $this->config['catOrderBy'] == 'sorting') {
                $mmCatOrderBy = 'mmsorting';
            } else {
                $mmCatOrderBy = $this->config['catOrderBy'];
            }

            $addWhere = $this->SPaddWhere . ($getAll ? ' AND tt_news_cat.deleted=0' : $this->enableCatFields);

            $select_fields = 'tt_news_cat.*,tt_news_cat_mm.sorting AS mmsorting';
            $from_table = 'tt_news_cat_mm, tt_news_cat ';
            $where_clause = 'tt_news_cat_mm.uid_local=' . (int)$uid . ' AND tt_news_cat_mm.uid_foreign=tt_news_cat.uid';
            $where_clause .= $addWhere;

            $groupBy = '';
            $orderBy = $mmCatOrderBy;
            $limit = '';

            $res = $this->db->exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);

            $categories = [];
            $maincat = 0;

            while (($row = $this->db->sql_fetch_assoc($res))) {
                $maincat .= ',' . $row['uid'];
                $rows = [$row];
                if ($this->conf['displaySubCategories'] && $this->config['useSubCategories']) {
                    $subCategories = [];
                    $subcats = implode(
                        ',',
                        array_unique(explode(',', (string)Div::getSubCategories($rows[0]['uid'], $addWhere)))
                    );

                    $subres = $this->db->exec_SELECTquery(
                        'tt_news_cat.*',
                        'tt_news_cat',
                        'tt_news_cat.uid IN (' . ($subcats ?: 0) . ')' . $addWhere,
                        '',
                        'tt_news_cat.' . $this->config['catOrderBy']
                    );

                    while (($subrow = $this->db->sql_fetch_assoc($subres))) {
                        $subCategories[] = $subrow;
                    }
                    $rows = [...$rows, ...$subCategories];
                }

                foreach ($rows as $val) {
                    $parentSP = false;
                    $catTitle = '';
                    if ($this->sys_language_content) {
                        // find translations of category titles
                        $catTitleArr = GeneralUtility::trimExplode('|', $val['title_lang_ol']);
                        $catTitle = $catTitleArr[($this->sys_language_content - 1)];
                    }
                    $catTitle = $catTitle ?: $val['title'];

                    if ($this->conf['useSPidFromCategory'] && $this->conf['useSPidFromCategoryRecusive']) {
                        $parentSP = $this->helpers->getRecursiveCategorySinglePid($val['uid']);
                    }
                    $singlePid = ($parentSP ?: $val['single_pid']);

                    $categories[$val['uid']] = [
                        'uid' => $val['uid'],
                        'title' => $catTitle,
                        'image' => $val['image'],
                        'shortcut' => $val['shortcut'],
                        'shortcut_target' => $val['shortcut_target'],
                        'single_pid' => $singlePid,
                        'catid' => $val['uid'],
                        'parent_category' => (!GeneralUtility::inList(
                            $maincat,
                            $val['uid']
                        ) && $this->conf['displaySubCategories'] ? $val['parent_category'] : ''),
                        'sorting' => $val['sorting'],
                        'mmsorting' => $val['mmsorting'] ?? null,
                    ];
                }
            }
            if ($this->cache_categories && is_array($categories)) {
                $this->cache->set($hash, $categories, [__FUNCTION__]);
            }
        }

        return $categories;
    }

    /**
     * displays a category rootline by extending either the first category of a record or the category
     * which is selected by piVars by their parent categories until a category with parent 0 is reached.
     *
     * @param    array $categoryArray : list of categories which will be extended by subcategories
     *
     * @return    string        the category rootline
     */
    protected function getCategoryPath($categoryArray)
    {
        $catRootline = '';
        if (is_array($categoryArray)) {
            $pTmp = $GLOBALS['TSFE']->config['config']['ATagParams'] ?? '';
            $lConf = $this->conf['catRootline.'];
            if ($this->conf['catSelectorTargetPid']) {
                $catSelLinkParams = $this->conf['catSelectorTargetPid'];
                if ($this->conf['itemLinkTarget']) {
                    $catSelLinkParams .= ' ' . $this->conf['itemLinkTarget'];
                }
            } else {
                $catSelLinkParams = $this->tsfe->id;
            }

            $mainCategory = array_shift($categoryArray);
            $uid = $mainCategory['catid'];

            $loopCheck = 100;
            $theRowArray = [];
            $output = [];
            while ($uid != 0 && $loopCheck > 0) {
                $loopCheck--;
                $res = $this->db->exec_SELECTquery(
                    '*',
                    'tt_news_cat',
                    'uid=' . (int)$uid . $this->SPaddWhere . $this->enableCatFields
                );

                if (($row = $this->db->sql_fetch_assoc($res))) {
                    $uid = $row['parent_category'];
                    $theRowArray[] = $row;
                } else {
                    break;
                }
            }

            if (is_array($theRowArray)) {
                krsort($theRowArray);
                foreach ($theRowArray as $val) {
                    $catTitle = '';
                    if ($this->sys_language_content) {
                        // find translations of category titles
                        $catTitleArr = GeneralUtility::trimExplode('|', $val['title_lang_ol']);
                        $catTitle = $catTitleArr[($this->sys_language_content - 1)];
                    }
                    $catTitle = $catTitle ?: $val['title'];
                    if ($lConf['linkTitles'] && GeneralUtility::inList('2,3', $this->config['catTextMode'])) {
                        $this->tsfe->config['config']['ATagParams'] = ($pTmp ? $pTmp . ' ' : '') . 'title="' . $catTitle . '"';
                        $output = $this->handleCatTextMode($val, $catSelLinkParams, $catTitle, $lConf, $output);
                    } else {
                        $output[] = $this->local_cObj->stdWrap($catTitle, $lConf['title_stdWrap.']);
                    }
                }
            }

            $catRootline = implode($lConf['divider'], $output);
            if ($catRootline) {
                $catRootline = $this->local_cObj->stdWrap($catRootline, $lConf['catRootline_stdWrap.']);
            }

            $this->tsfe->config['config']['ATagParams'] = $pTmp;
        }

        return $catRootline;
    }

    /**
     * This function calls itself recursively to convert the nested category array to HTML
     *
     * @param    array   $array_in : the nested categories
     * @param    array   $lConf    : TS configuration
     * @param    int $l        : level counter
     *
     * @return    string        HTML for the category menu
     */
    protected function getCatMenuContent($array_in, $lConf, $l = 0)
    {
        $titlefield = 'title';
        $result = '';
        if (is_array($array_in)) {
            foreach ($array_in as $key => $val) {
                if ($key == $titlefield || is_array($array_in[$key])) {
                    if ($l) {
                        $catmenuLevel_stdWrap = explode(
                            '|||',
                            (string)$this->local_cObj->stdWrap('|||', $lConf['catmenuLevel' . $l . '_stdWrap.'])
                        );
                        $result .= $catmenuLevel_stdWrap[0];
                    } else {
                        $catmenuLevel_stdWrap = '';
                    }
                    if (is_array($array_in[$key])) {
                        $result .= $this->getCatMenuContent($array_in[$key], $lConf, $l + 1);
                    } elseif ($key == $titlefield) {
                        if ($this->sys_language_content && $array_in['uid']) {
                            // get translations of category titles
                            $catTitleArr = GeneralUtility::trimExplode(
                                '|',
                                $array_in['title_lang_ol']
                            );
                            $syslang = $this->sys_language_content - 1;
                            $val = $catTitleArr[$syslang] ?: $val;
                        }
                        // if (!$title) $title = $val;
                        $catSelLinkParams = ($this->conf['catSelectorTargetPid'] ? ($this->conf['itemLinkTarget'] ? $this->conf['catSelectorTargetPid'] . ' ' . $this->conf['itemLinkTarget'] : $this->conf['catSelectorTargetPid']) : $this->tsfe->id);
                        $pTmp = $GLOBALS['TSFE']->config['config']['ATagParams'] ?? '';
                        if ($this->conf['displayCatMenu.']['insertDescrAsTitle']) {
                            $this->tsfe->config['config']['ATagParams'] = ($pTmp ? $pTmp . ' ' : '') . 'title="' . $array_in['description'] . '"';
                        }
                        if ($array_in['uid']) {
                            $piVarsCat = $this->piVars['cat'] ?? false;
                            if ($piVarsCat == $array_in['uid']) {
                                $result .= $this->local_cObj->stdWrap(
                                    $this->pi_linkTP_keepPIvars(
                                        $val,
                                        ['cat' => $array_in['uid']],
                                        $this->allowCaching,
                                        1,
                                        $catSelLinkParams
                                    ),
                                    $lConf['catmenuItem_ACT_stdWrap.']
                                );
                            } else {
                                $result .= $this->local_cObj->stdWrap(
                                    $this->pi_linkTP_keepPIvars(
                                        $val,
                                        ['cat' => $array_in['uid']],
                                        $this->allowCaching,
                                        1,
                                        $catSelLinkParams
                                    ),
                                    $lConf['catmenuItem_NO_stdWrap.']
                                );
                            }
                        } else {
                            $result .= $this->pi_linkTP_keepPIvars(
                                $val,
                                [],
                                $this->allowCaching,
                                1,
                                $catSelLinkParams
                            );
                        }
                        $this->tsfe->config['config']['ATagParams'] = $pTmp;
                    }
                    if ($l) {
                        $result .= $catmenuLevel_stdWrap[1];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param $uid
     *
     * @return string
     */
    protected function getRelatedNewsAsList($uid)
    {
        // save some variables which are used to build the backLink to the list view
        $tmpcatExclusive = $this->catExclusive;
        $tmparcExclusive = $this->arcExclusive;
        $tmpCategories = $this->categories;
        $tmpcode = $this->theCode;
        $tmpBrowsePage = (int)($this->piVars['pointer']);
        unset($this->piVars['pointer']);
        $tmpPS = (int)($this->piVars['pS']);
        unset($this->piVars['pS']);
        $tmpPL = (int)($this->piVars['pL']);
        unset($this->piVars['pL']);

        $confSave = $this->conf;
        $configSave = $this->config;
        $tmplocal_cObj = clone $this->local_cObj;
        $tmp_renderMarkers = $this->renderMarkers;

        if (!is_array($this->conf['displayRelated.'])) {
            $this->conf['displayRelated.'] = [];
        }

        ArrayUtility::mergeRecursiveWithOverrule($this->conf, $this->conf['displayRelated.']);
        $this->config = $this->conf;
        $this->arcExclusive = $this->conf['archive'];
        $this->LOCAL_LANG_loaded = false;
        // reload language-labels
        $this->pi_loadLL();

        $this->theCode = 'RELATED';
        $this->relNewsUid = $uid;
        $this->addFromTable = 'tt_news_related_mm';

        $relatedNews = trim($this->displayList());

        // restore variables
        $this->conf = $confSave;
        $this->config = $configSave;
        $this->theCode = $tmpcode;
        $this->catExclusive = $tmpcatExclusive;
        $this->arcExclusive = $tmparcExclusive;
        $this->categories = $tmpCategories;
        $this->piVars['pointer'] = $tmpBrowsePage;
        $this->piVars['pS'] = $tmpPS;
        $this->piVars['pL'] = $tmpPL;
        $this->local_cObj = $tmplocal_cObj;
        $this->renderMarkers = $tmp_renderMarkers;

        unset($confSave, $configSave, $tmpCategories, $this->addFromTable, $tmplocal_cObj);

        return $relatedNews;
    }

    /**
     * Find related news records and pages, add links to them and wrap them with stdWraps from TS.
     *
     * @param    int $uid of the current news record
     *
     * @return    string        html code for the related news list
     */
    protected function getRelated($uid)
    {
        $lConf = $this->conf['getRelatedCObject.'];
        $visibleCategories = '';
        $sPidByCat = [];
        if (($this->conf['checkCategoriesOfRelatedNews'] ?? false) || ($this->conf['useSPidFromCategory'] ?? false)) {
            // get visible categories and their singlePids
            $catres = $this->db->exec_SELECTquery(
                'tt_news_cat.uid,tt_news_cat.single_pid',
                'tt_news_cat',
                '1=1' . $this->SPaddWhere . $this->enableCatFields
            );

            $catTemp = [];
            while (($catrow = $this->db->sql_fetch_assoc($catres))) {
                $sPidByCat[$catrow['uid']] = $catrow['single_pid'];
                $catTemp[] = $catrow['uid'];
            }
            if ($this->conf['checkCategoriesOfRelatedNews'] ?? false) {
                $visibleCategories = implode(',', $catTemp);
            }
        }
        $relPages = false;
        if ($this->conf['usePagesRelations']) {
            $relPages = $this->getRelatedPages($uid);
        }
        $select_fields = ' uid, pid, title, short, datetime, archivedate, type, page, ext_url, sys_language_uid, l18n_parent, tt_news_related_mm.tablenames, image, bodytext';
        $where = 'tt_news_related_mm.uid_local=' . $uid . '
					AND tt_news.uid=tt_news_related_mm.uid_foreign
					AND tt_news_related_mm.tablenames!=' . $this->db->fullQuoteStr('pages', 'tt_news_related_mm');

        $groupBy = '';
        if ($lConf['groupBy']) {
            $groupBy = trim((string)$lConf['groupBy']);
        }
        $orderBy = '';
        if ($lConf['orderBy']) {
            $orderBy = trim((string)$lConf['orderBy']);
        }

        if ($this->conf['useBidirectionalRelations']) {
            $where = '((' . $where . ')
					OR (tt_news_related_mm.uid_foreign=' . $uid . '
						AND tt_news.uid=tt_news_related_mm.uid_local
						AND tt_news_related_mm.tablenames!=' . $this->db->fullQuoteStr(
                'pages',
                'tt_news_related_mm'
            ) . ')) AND tt_news.sys_language_uid = 0';
        }

        $from_table = 'tt_news_related_mm, tt_news';

        $res = $this->db->exec_SELECTquery(
            $select_fields,
            $from_table,
            $where . $this->enableFields,
            $groupBy,
            $orderBy
        );

        if ($res) {
            $relrows = [];
            while (($relrow = $this->db->sql_fetch_assoc($res))) {
                $currentCats = [];
                if ($this->conf['checkCategoriesOfRelatedNews'] ?? false || $this->conf['useSPidFromCategory']) {
                    $currentCats = $this->getCategories($relrow['uid'], true);
                }
                if ($this->conf['checkCategoriesOfRelatedNews'] ?? false) {
                    if (count($currentCats)) {
                        // record has categories
                        foreach ($currentCats as $cUid) {
                            if (GeneralUtility::inList($visibleCategories, $cUid['catid'])) {
                                // if the record has at least one visible category assigned it will be shown
                                $relrows[$relrow['uid']] = $relrow;
                                break;
                            }
                        }
                    } else {
                        // record has NO categories
                        $relrows[$relrow['uid']] = $relrow;
                    }
                } else {
                    $relrows[$relrow['uid']] = $relrow;
                }

                // check if there's a single pid for the first category of a news record and add 'sPidByCat' to the $relrows array.
                if ($this->conf['useSPidFromCategory'] && count($currentCats) && $relrows[$relrow['uid']]) {
                    $firstcat = array_shift($currentCats);
                    if ($firstcat['catid'] && $sPidByCat[$firstcat['catid']]) {
                        $relrows[$relrow['uid']]['sPidByCat'] = $sPidByCat[$firstcat['catid']];
                    }
                }
            }

            if (is_array($relPages[0] ?? null) && $this->conf['usePagesRelations']) {
                $relrows = array_merge_recursive($relPages, $relrows);
            }

            $piVarsArray = [
                'backPid' => ($this->conf['dontUseBackPid'] ? null : $this->config['backPid']),
                'year' => ($this->conf['dontUseBackPid'] ? null : ($this->piVars['year'] ?: null)),
                'month' => ($this->conf['dontUseBackPid'] ? null : ($this->piVars['month'] ?: null)),
            ];
            $tmpAddParams = '';
            foreach ($piVarsArray as $key => $value) {
                if ($value !== null) {
                    $tmpAddParams .= '&tx_ttnews[' . $key . ']=' . $value;
                }
            }

            /** @var ContentObjectRenderer $veryLocal_cObj */
            $veryLocal_cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class); // Local cObj.
            $lines = [];

            foreach ($relrows as $row) {
                if ($this->sys_language_content && $row['tablenames'] != 'pages') {
                    $OLmode = ($this->sys_language_mode == 'strict' ? 'hideNonTranslated' : '');
                    $row = $this->tsfe->sys_page->getRecordOverlay(
                        'tt_news',
                        $row,
                        $this->sys_language_content,
                        $OLmode
                    );
                    if (!is_array($row)) {
                        continue;
                    }
                }
                $veryLocal_cObj->start($row, 'tt_news');

                if ($row['type'] != 1 && $row['type'] != 2) {
                    // only normal news
                    $catSPid = false;
                    if ($row['sPidByCat'] ?? '' && $this->conf['useSPidFromCategory']) {
                        $catSPid = $row['sPidByCat'];
                    }
                    $sPid = ($catSPid ?: $this->config['singlePid']);
                    $newsAddParams = '&tx_ttnews[tt_news]=' . (int)$row['uid'] . $tmpAddParams;

                    // load the parameter string into the register 'newsAddParams' to access it from TS
                    $veryLocal_cObj->cObjGetSingle(
                        'LOAD_REGISTER',
                        ['newsAddParams' => $newsAddParams, 'newsSinglePid' => $sPid]
                    );

                    if (!$this->conf['getRelatedCObject.']['10.']['default.']['10.']['typolink.']['parameter'] || $catSPid) {
                        $this->conf['getRelatedCObject.']['10.']['default.']['10.']['typolink.']['parameter'] = $sPid;
                    }
                }

                $lines[] = $veryLocal_cObj->cObjGetSingle(
                    $this->conf['getRelatedCObject'],
                    $this->conf['getRelatedCObject.'],
                    'getRelatedCObject'
                );
            }
            return implode('', $lines);
        }
        return '';
    }

    /**
     * @param $uid
     *
     * @return array
     */
    protected function getRelatedPages($uid)
    {
        $relPages = [];

        $select_fields = 'uid,title,tstamp,description,subtitle,tt_news_related_mm.tablenames';
        $from_table = 'pages,tt_news_related_mm';
        $where = 'tt_news_related_mm.uid_local=' . $uid . '
					AND pages.uid=tt_news_related_mm.uid_foreign
					AND tt_news_related_mm.tablenames=' . $this->db->fullQuoteStr(
            'pages',
            'tt_news_related_mm'
        ) . $this->getEnableFields('pages');

        $pres = $this->db->exec_SELECTquery($select_fields, $from_table, $where, '', 'title');

        while (($prow = $this->db->sql_fetch_assoc($pres))) {
            if ($this->sys_language_content) {
                $prow = $this->tsfe->sys_page->getPageOverlay($prow, $this->sys_language_content);
            }

            $relPages[] = [
                'title' => $prow['title'],
                'datetime' => $prow['tstamp'],
                'archivedate' => 0,
                'type' => 1,
                'page' => $prow['uid'],
                'short' => $prow['subtitle'] ?: $prow['description'],
                'tablenames' => $prow['tablenames'],
            ];
        }

        return $relPages;
    }

    /**
     * this is a copy of the function pi_list_browseresults from class.tslib_piBase.php
     * Returns a results browser. This means a bar of page numbers plus a "previous" and "next" link. For each entry in
     * the bar the piVars "$pointerName" will be pointing to the "result page" to show. Using
     * $this->piVars['$pointerName'] as pointer to the page to display Using $this->internal['res_count'],
     * $this->internal['results_at_a_time'] and $this->internal['maxPages'] for count number, how many results to show
     * and the max number of pages to include in the browse bar.
     *
     * @param    int    $showResultCount If set (default) the text "Displaying results..." will be shown, otherwise
     *                                   not.
     * @param    string $tableParams     Attributes for the table tag which is wrapped around the table cells
     *                                   containing the browse links
     * @param    string $pointerName     varname for the pointer
     *
     * @return    string        Output HTML, wrapped in <div>-tags with a class attribute
     */
    protected function makePageBrowser($showResultCount = 1, $tableParams = '', $pointerName = 'pointer')
    {
        $tmpPS = false;
        $tmpPL = false;
        if ($this->conf['useHRDates']) {
            $tmpPS = $this->piVars['pS'] ?? null;
            unset($this->piVars['pS']);
            $tmpPL = $this->piVars['pL'] ?? null;
            unset($this->piVars['pL']);
        }

        // Initializing variables:
        $pointer = $this->piVars[$pointerName] ?? 0;
        $count = $this->internal['res_count'];
        $results_at_a_time = MathUtility::forceIntegerInRange(
            $this->internal['results_at_a_time'],
            1,
            1000
        );
        $maxPages = MathUtility::forceIntegerInRange($this->internal['maxPages'], 1, 100);
        $max = MathUtility::forceIntegerInRange(
            ceil($count / $results_at_a_time),
            1,
            $maxPages
        );
        $pointer = (int)$pointer;
        $links = [];

        // Make browse-table/links:
        if ($this->pi_alwaysPrev >= 0) {
            if ($pointer > 0) {
                $links[] = '
					<td nowrap="nowrap"><p>' . $this->pi_linkTP_keepPIvars($this->pi_getLL(
                    'pi_list_browseresults_prev',
                    '< Previous'
                ), [
                    $pointerName => ($pointer - 1 ?: ''),
                ], $this->allowCaching) . '</p></td>';
            } elseif ($this->pi_alwaysPrev) {
                $links[] = '
					<td nowrap="nowrap"><p>' . $this->pi_getLL(
                    'pi_list_browseresults_prev',
                    '< Previous'
                ) . '</p></td>';
            }
        }

        for ($a = 0; $a < $max; $a++) {
            $links[] = '
					<td' . ($pointer == $a ? $this->pi_classParam('browsebox-SCell') : '') . ' nowrap="nowrap"><p>' . $this->pi_linkTP_keepPIvars(trim($this->pi_getLL(
                'pi_list_browseresults_page',
                'Page'
            ) . ' ' . ($a + 1)), [
                $pointerName => ($a ?: ''),
            ], $this->allowCaching) . '</p></td>';
        }
        if ($pointer < ceil($count / $results_at_a_time) - 1) {
            $links[] = '
					<td nowrap="nowrap"><p>' . $this->pi_linkTP_keepPIvars($this->pi_getLL(
                'pi_list_browseresults_next',
                'Next >'
            ), [
                $pointerName => $pointer + 1,
            ], $this->allowCaching) . '</p></td>';
        }

        $pR1 = $pointer * $results_at_a_time + 1;
        $pR2 = $pointer * $results_at_a_time + $results_at_a_time;
        $sTables = '

		<!--
			List browsing box:
		-->
		<div' . $this->pi_classParam('browsebox') . '>' . ($showResultCount ? '
			<p>' . ($this->internal['res_count'] ? sprintf(
            str_replace(
                '###SPAN_BEGIN###',
                '<span' . $this->pi_classParam('browsebox-strong') . '>',
                $this->pi_getLL(
                    'pi_list_browseresults_displays',
                    'Displaying results ###FROM### to ###TO### out of ###OUT_OF###'
                )
            ),
            $this->internal['res_count'] > 0 ? $pR1 : 0,
            min([
                $this->internal['res_count'],
                $pR2,
            ]),
            $this->internal['res_count']
        ) : $this->pi_getLL(
            'pi_list_browseresults_noResults',
            'Sorry, no items were found.'
        )) . '</p>' : '') . '

			<' . trim('table ' . $tableParams) . '>
				<tr>
					' . implode('', $links) . '
				</tr>
			</table>
		</div>';
        if ($this->conf['useHRDates']) {
            if ($tmpPS) {
                $this->piVars['pS'] = $tmpPS;
            }
            if ($tmpPL) {
                $this->piVars['pL'] = $tmpPL;
            }
        }

        return $sTables;
    }

    /**
     * builds the XML header (array of markers to substitute)
     *
     * @return    array        the filled XML header markers
     */
    protected function getXmlHeader()
    {
        $lConf = $this->conf['displayXML.'];
        $markerArray = [];

        $markerArray['###SITE_TITLE###'] = $lConf['xmlTitle'];
        $markerArray['###SITE_LINK###'] = $this->config['siteUrl'];
        $markerArray['###SITE_DESCRIPTION###'] = $lConf['xmlDesc'];
        if (!empty($markerArray['###SITE_DESCRIPTION###'])) {
            if ($lConf['xmlFormat'] == 'atom03') {
                $markerArray['###SITE_DESCRIPTION###'] = '<tagline>' . $markerArray['###SITE_DESCRIPTION###'] . '</tagline>';
            } elseif ($lConf['xmlFormat'] == 'atom1') {
                $markerArray['###SITE_DESCRIPTION###'] = '<subtitle>' . $markerArray['###SITE_DESCRIPTION###'] . '</subtitle>';
            }
        }

        $markerArray['###SITE_LANG###'] = $lConf['xmlLang'];
        if ($lConf['xmlFormat'] == 'rss2') {
            $markerArray['###SITE_LANG###'] = '<language>' . $markerArray['###SITE_LANG###'] . '</language>';
        } elseif ($lConf['xmlFormat'] == 'atom03') {
            $markerArray['###SITE_LANG###'] = ' xml:lang="' . $markerArray['###SITE_LANG###'] . '"';
        }
        if (empty($lConf['xmlLang'])) {
            $markerArray['###SITE_LANG###'] = '';
        }

        if (isset($lConf['xmlIcon'])) {
            $imgFile = GeneralUtility::getFileAbsFileName($this->cObj->stdWrap($lConf['xmlIcon'], $lConf['xmlIcon.'] ?? false));
        } else {
            $imgFile = null;
        }
        $imgSize = is_file($imgFile) ? getimagesize($imgFile) : '';
        $markerArray['###IMG_W###'] = $imgSize[0] ?? null;
        $markerArray['###IMG_H###'] = $imgSize[1] ?? null;

        $relImgFile = str_replace(Environment::getPublicPath() . '/', '', (string)$imgFile);
        $markerArray['###IMG###'] = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $relImgFile;

        $selectConf = [];
        $selectConf['pidInList'] = $this->pid_list;
        // select only normal news (type=0) for the RSS feed. You can override this with other types with the TS-var 'xmlNewsTypes'
        $selectConf['selectFields'] = 'max(datetime) as maxval';

        $res = $this->exec_getQuery('tt_news', $selectConf);

        $row = $this->db->sql_fetch_assoc($res);
        // optional tags
        if ($lConf['xmlLastBuildDate']) {
            $markerArray['###NEWS_LASTBUILD###'] = '<lastBuildDate>' . date(
                'D, d M Y H:i:s O',
                $row['maxval']
            ) . '</lastBuildDate>';
        } else {
            $markerArray['###NEWS_LASTBUILD###'] = '';
        }

        if ($lConf['xmlFormat'] == 'atom03' || $lConf['xmlFormat'] == 'atom1') {
            $markerArray['###NEWS_LASTBUILD###'] = $this->helpers->getW3cDate($row['maxval']);
        }

        if ($lConf['xmlWebMaster'] ?? null) {
            $markerArray['###NEWS_WEBMASTER###'] = '<webMaster>' . $lConf['xmlWebMaster'] . '</webMaster>';
        } else {
            $markerArray['###NEWS_WEBMASTER###'] = '';
        }

        if ($lConf['xmlManagingEditor'] ?? null) {
            $markerArray['###NEWS_MANAGINGEDITOR###'] = '<managingEditor>' . $lConf['xmlManagingEditor'] . '</managingEditor>';
        } else {
            $markerArray['###NEWS_MANAGINGEDITOR###'] = '';
        }

        if ($lConf['xmlCopyright'] ?? null) {
            if ($lConf['xmlFormat'] == 'atom1') {
                $markerArray['###NEWS_COPYRIGHT###'] = '<rights>' . $lConf['xmlCopyright'] . '</rights>';
            } else {
                $markerArray['###NEWS_COPYRIGHT###'] = '<copyright>' . $lConf['xmlCopyright'] . '</copyright>';
            }
        } else {
            $markerArray['###NEWS_COPYRIGHT###'] = '';
        }

        $charset = ('utf-8' ?: 'iso-8859-1');
        if ($lConf['xmlDeclaration'] ?? null) {
            $markerArray['###XML_DECLARATION###'] = trim((string)$lConf['xmlDeclaration']);
        } else {
            $markerArray['###XML_DECLARATION###'] = '<?xml version="1.0" encoding="' . $charset . '"?>';
        }

        // promoting TYPO3 in atom feeds, supress the subversion
        $version = explode('.', ((string)VersionNumberUtility::getCurrentTypo3Version()));
        unset($version[2]);
        $markerArray['###TYPO3_VERSION###'] = implode('.', $version);

        return $markerArray;
    }

    /**********************************************************************************************
     *
     *              DB Functions
     *
     **********************************************************************************************/

    /**
     * build the selectconf (array of query-parameters) to get the news items from the db
     *
     * @param    string $addwhere : where-part of the query
     * @param    int    $noPeriod : if this value exists the listing starts with the given 'period start' (pS). If not
     *                            the value period start needs also a value for 'period length' (pL) to display
     *                            something.
     *
     * @return    array        the selectconf for the display of a news item
     */
    public function getSelectConf($addwhere, $noPeriod = 0)
    {
        // Get news
        $selectConf = [];
        $selectConf['pidInList'] = $this->pid_list;

        $selectConf['where'] = '';

        $selectConf['where'] .= ' 1=1 ';

        if ($this->arcExclusive) {
            if ($this->conf['enableArchiveDate'] && $this->config['datetimeDaysToArchive'] && $this->arcExclusive > 0) {
                $theTime = $this->SIM_ACCESS_TIME - (int)($this->config['datetimeDaysToArchive']) * 3600 * 24;
                if (version_compare($this->conf['compatVersion'], '2.5.0') <= 0) {
                    $selectConf['where'] .= ' AND (tt_news.archivedate<' . $this->SIM_ACCESS_TIME . ' OR tt_news.datetime<' . $theTime . ')';
                } else {
                    $selectConf['where'] .= ' AND ((tt_news.archivedate > 0 AND tt_news.archivedate<' . $this->SIM_ACCESS_TIME . ') OR tt_news.datetime<' . $theTime . ')';
                }
            } else {
                if ($this->conf['enableArchiveDate']) {
                    if ($this->arcExclusive < 0) {
                        // show archived
                        $selectConf['where'] .= ' AND (tt_news.archivedate=0 OR tt_news.archivedate>' . $this->SIM_ACCESS_TIME . ')';
                    } elseif ($this->arcExclusive > 0) {
                        if (version_compare($this->conf['compatVersion'], '2.5.0') <= 0) {
                            $selectConf['where'] .= ' AND tt_news.archivedate<' . $this->SIM_ACCESS_TIME;
                        } else {
                            $selectConf['where'] .= ' AND tt_news.archivedate>0 AND tt_news.archivedate<' . $this->SIM_ACCESS_TIME;
                        }
                    }
                }
                if ($this->config['datetimeMinutesToArchive'] || $this->config['datetimeHoursToArchive'] || $this->config['datetimeDaysToArchive']) {
                    if ($this->config['datetimeMinutesToArchive']) {
                        $theTime = $this->SIM_ACCESS_TIME - (int)($this->config['datetimeMinutesToArchive']) * 60;
                    } elseif ($this->config['datetimeHoursToArchive']) {
                        $theTime = $this->SIM_ACCESS_TIME - (int)($this->config['datetimeHoursToArchive']) * 3600;
                    } else {
                        $theTime = $this->SIM_ACCESS_TIME - (int)($this->config['datetimeDaysToArchive']) * 86400;
                    }
                    if ($this->arcExclusive < 0) {
                        $selectConf['where'] .= ' AND (tt_news.datetime=0 OR tt_news.datetime>' . $theTime . ')';
                    } elseif ($this->arcExclusive > 0) {
                        $selectConf['where'] .= ' AND tt_news.datetime<' . $theTime;
                    }
                }
            }
        }

        if (!$this->externalCategorySelection) {
            // exclude LATEST and AMENU from changing their contents with the catmenu. This can be overridden by setting the TSvars 'latestWithCatSelector' or 'amenuWithCatSelector'
            if (($this->config['catSelection'] ?? false) && (($this->theCode == 'LATEST' && $this->conf['latestWithCatSelector']) || ($this->theCode == 'AMENU' && $this->conf['amenuWithCatSelector']) || (GeneralUtility::inList(
                'LIST,LIST2,LIST3,HEADER_LIST,SEARCH,XML',
                $this->theCode
            )))) {
                // force 'select categories' mode if cat is given in GPvars
                $this->config['categoryMode'] = 1;
                // override category selection from other news content-elements with selection from catmenu (GPvars)
                $this->catExclusive = $this->config['catSelection'];
                $this->actuallySelectedCategories = $this->piVars_catSelection;
            }

            if ($this->catExclusive) {
                // select newsitems by their categories
                if ($this->config['categoryMode'] == 1 || $this->config['categoryMode'] == 2) {
                    // show items with selected categories
                    $tmpCatExclusive = (($this->config['categoryMode'] == 2 && !$this->conf['ignoreUseSubcategoriesForAndSelection']) ?
                        $this->actuallySelectedCategories : $this->catExclusive);
                    $selectConf['leftjoin'] = 'tt_news_cat_mm ON tt_news.uid = tt_news_cat_mm.uid_local';
                    $selectConf['where'] .= ' AND (tt_news_cat_mm.uid_foreign IN (' . ($tmpCatExclusive ?: 0) . '))';
                }

                // de-select newsitems by their categories
                if (($this->config['categoryMode'] == -1 || $this->config['categoryMode'] == -2)) {
                    // do not show items with selected categories
                    $selectConf['leftjoin'] = 'tt_news_cat_mm ON tt_news.uid = tt_news_cat_mm.uid_local';
                    $selectConf['where'] .= ' AND (tt_news_cat_mm.uid_foreign NOT IN (' . ($this->catExclusive ?: 0) . '))';
                    // filter out not categorized records
                    $selectConf['where'] .= ' AND (tt_news_cat_mm.uid_foreign)';
                }
            } elseif ($this->config['categoryMode']) {
                // special case: if $this->catExclusive is not set but $this->config['categoryMode'] -> show only non-categorized records
                $selectConf['leftjoin'] = 'tt_news_cat_mm ON tt_news.uid = tt_news_cat_mm.uid_local';
                $selectConf['where'] .= ' AND tt_news_cat_mm.uid_foreign IS' .
                    ($this->config['categoryMode'] > 0 ? '' : ' NOT') . ' NULL';
            }

            // if categoryMode is 'show items AND' it's required to check if the records in the result do actually have the same number of categories as in $this->catExclusive
            if ($this->catExclusive && $this->config['categoryMode'] == 2) {
                $selectConf['where'] .= ' AND tt_news.category = ' . count(explode(',', $this->catExclusive));
            }

            // if categoryMode is 'don't show items OR' we check if each found record does not have any of the deselected categories assigned
            if ($this->catExclusive && $this->config['categoryMode'] == -2) {
                $selectConf['where'] .= ' AND tt_news.uid NOT IN (SELECT uid from tt_news LEFT JOIN tt_news_cat_mm ON tt_news.uid = tt_news_cat_mm.uid_local WHERE tt_news_cat_mm.uid_foreign IN (' . $this->catExclusive . '))';
            }
        }

        if ($this->arcExclusive > 0) {
            if ($this->piVars['arc'] ?? false) {
                // allow overriding of the arcExclusive parameter from GET vars
                $this->arcExclusive = (int)($this->piVars['arc']);
            }
            // select news from a certain period
            if (!$noPeriod && (int)($this->piVars['pS'] ?? 0)) {
                $selectConf['where'] .= ' AND tt_news.datetime>=' . (int)($this->piVars['pS'] ?? 0);

                if ((int)($this->piVars['pL'])) {
                    $pL = (int)($this->piVars['pL']);
                    //selecting news for a certain day only
                    if ((int)($this->piVars['day'] ?? 0)) {
                        // = 24h, as pS always starts at the beginning of a day (00:00:00)
                        $pL = 86400;
                    }
                    $selectConf['where'] .= ' AND tt_news.datetime<' . ((int)($this->piVars['pS']) + $pL);
                }
            }
        }

        // filter Workspaces preview.
        // Since "enablefields" is ignored in workspace previews it's required to filter out news manually which are not visible in the live version AND the selected workspace.
        if ($this->conf['excludeAlreadyDisplayedNews'] && $this->theCode != 'SEARCH' && $this->theCode != 'CATMENU' && $this->theCode != 'AMENU') {
            if (!is_array($GLOBALS['T3_VAR']['displayedNews'] ?? null)) {
                $GLOBALS['T3_VAR']['displayedNews'] = [];
            } else {
                $excludeUids = implode(',', $GLOBALS['T3_VAR']['displayedNews']);
                if ($excludeUids) {
                    $selectConf['where'] .= ' AND tt_news.uid NOT IN (' . $this->db->cleanIntList($excludeUids) . ')';
                }
            }
        }

        if ($this->theCode != 'AMENU') {
            if ($this->config['groupBy'] ?? false) {
                $selectConf['groupBy'] = $this->config['groupBy'];
            }

            if ($this->config['orderBy'] ?? false) {
                if (strtoupper((string)$this->config['orderBy']) == 'RANDOM') {
                    $selectConf['orderBy'] = 'RAND()';
                } else {
                    $selectConf['orderBy'] = $this->config['orderBy'] . ($this->config['ascDesc'] ? ' ' . $this->config['ascDesc'] : '');
                }
            } else {
                $selectConf['orderBy'] = 'datetime DESC';
            }

            // overwrite the groupBy value for categories
            if (!$this->catExclusive && ($selectConf['groupBy'] ?? false) == 'category') {
                $selectConf['leftjoin'] = 'tt_news_cat_mm ON tt_news.uid = tt_news_cat_mm.uid_local';
                $selectConf['groupBy'] = 'tt_news_cat_mm.uid_foreign';
            }
        }

        $selectConf['where'] .= $this->getLanguageWhere();
        // only online versions
        $selectConf['where'] .= ' AND tt_news.pid > 0 ';
        if ($this->theCode != 'LATEST') {
            // latest ignores search query
            if ($addwhere != '') {
                $addwhere = QueryHelper::stripLogicalOperatorPrefix($addwhere);
                $selectConf['where'] .= ' AND (' . $addwhere . ')';
            }
        }

        // listing related news
        if ($this->theCode == 'RELATED' && $this->relNewsUid) {
            $where = $this->addFromTable . '.uid_local=' . $this->relNewsUid . '
						AND tt_news.uid=' . $this->addFromTable . '.uid_foreign
						AND ' . $this->addFromTable . '.tablenames!=' . $this->db->fullQuoteStr(
                'pages',
                $this->addFromTable
            );

            if ($this->conf['useBidirectionalRelations']) {
                $where = '((' . $where . ')
						OR (' . $this->addFromTable . '.uid_foreign=' . $this->relNewsUid . '
							AND tt_news.uid=' . $this->addFromTable . '.uid_local
							AND ' . $this->addFromTable . '.tablenames!=' . $this->db->fullQuoteStr(
                    'pages',
                    $this->addFromTable
                ) . '))';
            }

            $selectConf['where'] .= ' AND ' . $where;
        }

        // function Hook for processing the selectConf array
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['selectConfHook'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['selectConfHook'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $selectConf = $_procObj->processSelectConfHook($this, $selectConf);
            }
        }

        return $selectConf;
    }

    /**
     * @return string
     */
    protected function getLanguageWhere()
    {
        $where = '';
        $sys_language_content = $this->sys_language_content;
        if ($this->sys_language_mode == 'strict' && $sys_language_content) {
            // sys_language_mode == 'strict': If a certain language is requested, select only news-records from the default
            // language which have a translation. The translated articles will be overlayed later in the list or single function.
            $tmpres = $this->exec_getQuery('tt_news', [
                'selectFields' => 'tt_news.l18n_parent',
                'where' => 'tt_news.sys_language_uid = ' . $sys_language_content . $this->enableFields,
                'pidInList' => $this->pid_list,
            ]);

            $strictUids = [];
            while (($tmprow = $this->db->sql_fetch_assoc($tmpres))) {
                $strictUids[] = $tmprow['l18n_parent'];
            }
            $strStrictUids = implode(',', $strictUids);
            // sys_language_uid=-1 = [all languages]
            $where .= '(tt_news.uid IN (' . ($strStrictUids ?: 0) . ') OR tt_news.sys_language_uid=-1)';
        } else {
            // sys_language_mode NOT 'strict': If a certain language is requested, select only news-records in the default language.
            // The translated articles (if they exist) will be overlayed later in the displayList or displaySingle function.
            $where .= 'tt_news.sys_language_uid IN (0,-1)';
        }

        if ($this->conf['showNewsWithoutDefaultTranslation'] ?? false) {
            $where = '(' . $where . ' OR (tt_news.sys_language_uid=' . $sys_language_content . ' AND NOT tt_news.l18n_parent))';
        }

        return ' AND ' . $where;
    }

    /**
     * Generates a search where clause.
     *
     * @param    string $sw : searchword(s)
     *
     * @return    string        querypart
     */
    protected function searchWhere($sw)
    {
        $where = $this->cObj->searchWhere($sw, $this->searchFieldList, 'tt_news');
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['searchWhere'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['searchWhere'] as $_classRef) {
                /** @var object $_procObj */
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $where = $_procObj->searchWhere($this, $sw, $where);
            }
        }

        return $where;
    }

    /**
     * @param $table
     *
     * @return string
     * @throws AspectNotFoundException
     */
    public function getEnableFields($table)
    {
        if (is_array($this->conf['ignoreEnableFields.'])) {
            $ignore_array = $this->conf['ignoreEnableFields.'];
        } else {
            $ignore_array = [];
        }
        if (!is_object($this->tsfe)) {
            $this->tsfe = $GLOBALS['TSFE'];
        }
        /** @var Context $context */
        $context = GeneralUtility::makeInstance(Context::class);
        $show_hidden = ($table == 'pages'
            ? $context->getPropertyFromAspect('visibility', 'includeHiddenPages')
            : $context->getPropertyFromAspect('visibility', 'includeHiddenContent'));

        return $this->tsfe->sys_page->enableFields($table, $show_hidden, $ignore_array);
    }

    /**
     * Creates and executes a SELECT query for records from $table and with conditions based on the configuration in
     * the $conf array Implements the "select" function in TypoScript
     *
     * @param $table
     * @param $conf
     *
     * @return Result|false
     * @throws Exception
     */
    protected function exec_getQuery($table, $conf)
    {
        $error = 0;
        // Construct WHERE clause:
        if (!$this->conf['dontUsePidList'] && !strcmp((string)$conf['pidInList'], '')) {
            $conf['pidInList'] = 'this';
        }

        $queryParts = $this->getWhere($table, $conf);

        // Fields:
        $queryParts['SELECT'] = $conf['selectFields'] ?: '*';

        // Setting LIMIT:
        if ((($conf['max'] ?? false) || ($conf['begin'] ?? false)) && !$error) {
            $conf['begin'] = isset($conf['begin']) ? MathUtility::forceIntegerInRange(
                ceil($this->cObj->calc((string)$conf['begin'] ?? '0')),
                0
            ) : 0;
            if ($conf['begin'] && !$conf['max']) {
                $conf['max'] = 100000;
            }

            if ($conf['begin'] && $conf['max']) {
                $queryParts['LIMIT'] = $conf['begin'] . ',' . $conf['max'];
            } elseif (!$conf['begin'] && $conf['max']) {
                $queryParts['LIMIT'] = $conf['max'];
            }
        }

        if (!$error) {
            // Setting up tablejoins:
            $joinPart = '';
            if ($conf['join'] ?? false) {
                $joinPart = 'JOIN ' . trim((string)$conf['join']);
            } elseif ($conf['leftjoin'] ?? false) {
                $joinPart = 'LEFT OUTER JOIN ' . trim((string)$conf['leftjoin']);
            } elseif ($conf['rightjoin'] ?? false) {
                $joinPart = 'RIGHT OUTER JOIN ' . trim((string)$conf['rightjoin']);
            }

            // Compile and return query:
            $queryParts['FROM'] = trim(($this->addFromTable ? $this->addFromTable . ',' : '') . $table . ' ' . $joinPart);

            return $this->db->exec_SELECT_queryArray($queryParts);
        }
        return false;
    }

    /**
     * Helper function for getQuery(), creating the WHERE clause of the SELECT query
     *
     * @param    string $table The table name
     * @param    array  $conf  The TypoScript configuration properties
     *
     * @return    mixed        A WHERE clause based on the relevant parts of the TypoScript properties for a "select"
     *                         function in TypoScript, see link. If $returnQueryArray is false the where clause is
     *                         returned as a string with WHERE, GROUP BY and ORDER BY parts, otherwise as an array with
     *                         these parts.
     * @see    getQuery()
     */
    protected function getWhere($table, $conf)
    {
        global $TCA;

        // Init:
        $query = '';
        $queryParts = [
            'SELECT' => '',
            'FROM' => '',
            'WHERE' => '',
            'GROUPBY' => '',
            'ORDERBY' => '',
            'LIMIT' => '',
        ];

        if (($where = trim($conf['where'] ?? ''))) {
            $query .= ' AND ' . $where;
        }

        if (trim((string)$conf['pidInList'])) {
            // str_replace instead of ereg_replace 020800
            $listArr = GeneralUtility::intExplode(',', $conf['pidInList']);
            if (is_countable($listArr) ? count($listArr) : 0) {
                $query .= ' AND ' . $table . '.pid IN (' . implode(',', $listArr) . ')';
            }
        }

        if ($conf['languageField'] ?? false) {
            $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');
            if ($languageAspect->getLegacyOverlayType() && $TCA[$table] && $TCA[$table]['ctrl']['languageField'] && $TCA[$table]['ctrl']['transOrigPointerField']) {
                // Sys language content is set to zero/-1 - and it is expected that whatever routine processes the output will OVERLAY the records with localized versions!
                $sys_language_content = '0,-1';
            } else {
                $sys_language_content = (int)($this->sys_language_content);
            }
            $query .= ' AND ' . $conf['languageField'] . ' IN (' . $sys_language_content . ')';
        }

        $query .= $this->enableFields;

        // MAKE WHERE:
        if ($query) {
            $queryParts['WHERE'] = trim(substr($query, 4)); // Stripping of " AND"...
        }

        // GROUP BY
        if (trim($conf['groupBy'] ?? '')) {
            $queryParts['GROUPBY'] = trim($conf['groupBy'] ?? '');
        }

        // ORDER BY
        if (trim($conf['orderBy'] ?? '')) {
            $queryParts['ORDERBY'] = trim($conf['orderBy'] ?? '');
        }

        // Return result:
        return $queryParts;
    }

    /**********************************************************************************************
     *
     *              Init Helpers
     *
     **********************************************************************************************/

    /**
     * fills the internal array '$this->langArr' with the available syslanguages
     *
     * @todo remove/replace legacy langArr
     *
     */
    protected function initLanguages()
    {
        $lres = $this->db->exec_SELECTquery('*', 'sys_language', '1=1' . $this->getEnableFields('sys_language'));

        $this->langArr = [];
        $this->langArr[0] = ['title' => $this->conf['defLangLabel'], 'flag' => $this->conf['defLangImage']];

        while (($row = $this->db->sql_fetch_assoc($lres))) {
            $this->langArr[$row['uid']] = $row;
        }
    }

    protected function initCaching()
    {
        $this->cache_amenuPeriods = true;
        $this->cache_categoryCount = true;
        $this->cache_categories = true;

        $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('tt_news_cache');
    }

    /**
     * initialize category related vars and add subcategories to the category selection
     *
     */
    public function initCategoryVars(): void
    {
        $storagePid = false;

        $lc = $this->conf['displayCatMenu.'];

        if ($this->theCode == 'CATMENU') {
            // init catPidList
            $catPl = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'pages', 's_misc');
            $catPl = ($catPl ?: $this->cObj->stdWrap($lc['catPidList'], $lc['catPidList.'] ?? false));
            $catPl = implode(',', GeneralUtility::intExplode(',', $catPl));

            $recursive = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'recursive', 's_misc');
            if (!strcmp((string)$recursive, '') || $recursive === null) {
                $recursive = $this->cObj->stdWrap($lc['recursive'], $lc['recursive.'] ?? false);
            }

            if ($catPl) {
                $storagePid = $this->pi_getPidList($catPl, $recursive);
            }
        }

        if ($storagePid) {
            $this->SPaddWhere = ' AND tt_news_cat.pid IN (' . $storagePid . ')';
        }

        if ($this->conf['catExcludeList'] ?? false) {
            $this->SPaddWhere .= ' AND tt_news_cat.uid NOT IN (' . $this->conf['catExcludeList'] . ')';
        }

        $this->enableCatFields = $this->getEnableFields('tt_news_cat');

        $addWhere = $this->SPaddWhere . $this->enableCatFields;

        $useSubCategories = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'useSubCategories', 'sDEF');
        $this->config['useSubCategories'] = (is_string($useSubCategories) && strcmp($useSubCategories, '') !== 0) ? $useSubCategories : $this->conf['useSubCategories'];

        // global ordering for categories, Can be overwritten later by catOrderBy for a certain content element
        $catOrderBy = trim($this->conf['catOrderBy'] ?? '');
        $this->config['catOrderBy'] = $catOrderBy ?: 'sorting';

        // categoryModes are: 0=display all categories, 1=display selected categories, -1=display deselected categories
        $categoryMode = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'categoryMode', 'sDEF');

        $this->config['categoryMode'] = $categoryMode ?: (int)($this->conf['categoryMode']);
        // catselection holds only the uids of the categories selected by GETvars
        if ($this->piVars['cat'] ?? false) {
            // catselection holds only the uids of the categories selected by GETvars
            $this->config['catSelection'] = $this->helpers->checkRecords($this->piVars['cat']);
            $this->piVars_catSelection = $this->config['catSelection'];

            if ($this->config['useSubCategories'] && $this->config['catSelection']) {
                // get subcategories for selection from getVars
                $subcats = Div::getSubCategories($this->config['catSelection'], $addWhere);
                $this->config['catSelection'] = implode(
                    ',',
                    array_unique(explode(',', $this->config['catSelection'] . ($subcats ? ',' . $subcats : '')))
                );
            }
        }
        $catExclusive = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'categorySelection', 'sDEF');
        $catExclusive = $catExclusive ?: trim((string)$this->cObj->stdWrap(
            $this->conf['categorySelection'],
            $this->conf['categorySelection.'] ?? false
        ));
        // ignore cat selection if categoryMode isn't set
        $this->catExclusive = $this->config['categoryMode'] ? $catExclusive : 0;

        $this->catExclusive = $this->helpers->checkRecords($this->catExclusive);
        // store the actually selected categories because we need them for the comparison in categoryMode 2 and -2
        $this->actuallySelectedCategories = $this->catExclusive;

        // get subcategories
        if ($this->config['useSubCategories'] && $this->catExclusive) {
            $subcats = Div::getSubCategories($this->catExclusive, $addWhere);
            $this->catExclusive = implode(
                ',',
                array_unique(explode(',', $this->catExclusive . ($subcats ? ',' . $subcats : '')))
            );
        }

        // get more category fields from FF or TS
        $fields = explode(
            ',',
            'catImageMode,catTextMode,catImageMaxWidth,catImageMaxHeight,maxCatImages,catTextLength,maxCatTexts'
        );
        foreach ($fields as $key) {
            $value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, $key, 's_category');
            $this->config[$key] = (is_numeric($value) ? $value : ($this->conf[$key] ?? 0));
        }
    }

    /**
     * @param $lConf
     *
     */
    public function initCatmenuEnv(&$lConf): void
    {
        if ($lConf['catOrderBy'] ?? false) {
            $this->config['catOrderBy'] = $lConf['catOrderBy'];
        }

        if ($this->catExclusive) {
            $this->catlistWhere = ' AND tt_news_cat.uid' . ($this->config['categoryMode'] < 0 ? ' NOT' : '') . ' IN (' . $this->catExclusive . ')';
        } else {
            if ($lConf['excludeList'] ?? false) {
                $this->catlistWhere = ' AND tt_news_cat.uid NOT IN (' . implode(',', GeneralUtility::intExplode(
                    ',',
                    $lConf['excludeList']
                )) . ')';
            }
            if ($lConf['includeList'] ?? false) {
                $this->catlistWhere .= ' AND tt_news_cat.uid IN (' . implode(',', GeneralUtility::intExplode(
                    ',',
                    $lConf['includeList']
                )) . ')';
            }
        }

        if (($lConf['includeList'] ?? false) || ($lConf['excludeList'] ?? false) || $this->catExclusive) {
            // MOUNTS (in tree mode) must only contain the main/parent categories. Therefore it is required to filter out the subcategories from $this->catExclusive or $lConf['includeList']
            $categoryMounts = ($this->catExclusive ?: $lConf['includeList']);
            $tmpres = $this->db->exec_SELECTquery(
                'uid,parent_category',
                'tt_news_cat',
                'tt_news_cat.uid IN (' . $categoryMounts . ')' . $this->SPaddWhere . $this->enableCatFields,
                '',
                'tt_news_cat.' . $this->config['catOrderBy']
            );

            $this->cleanedCategoryMounts = [];

            if ($tmpres) {
                while (($tmprow = $this->db->sql_fetch_assoc($tmpres))) {
                    if (!GeneralUtility::inList($categoryMounts, $tmprow['parent_category'])) {
                        $this->dontStartFromRootRecord = true;
                        $this->cleanedCategoryMounts[] = $tmprow['uid'];
                    }
                }
            }
        }
    }

    /**
     * @param $fileName
     *
     * @return bool|string
     */
    protected function getFileResource($fileName)
    {
        if (str_starts_with((string)$fileName, 't3://')) {
            /** @var LinkService $linkService */
            $linkService = GeneralUtility::makeInstance(LinkService::class);
            $tmp = $linkService->resolve($fileName);
            if (isset($tmp['type']) && $tmp['type'] == $linkService::TYPE_FILE) {
                $fileObj = $tmp['file'];
                if ($fileObj instanceof File) {
                    $fileName = $fileObj->getPublicUrl();
                }
            }
        }

        $fileContent = '';

        $file = GeneralUtility::getFileAbsFileName($fileName);
        if ($file != '') {
            $fileContent = file_get_contents($file);
        }

        return $fileContent;
    }

    /**
     * read the template file, fill in global wraps and markers and write the result
     * to '$this->templateCode'
     */
    protected function initTemplate()
    {
        // read template-file and fill and substitute the Global Markers
        $templateflex_file = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'template_file', 's_template');
        if ($templateflex_file) {
            if (!str_contains($templateflex_file, '/')) {
                $templateflex_file = 'uploads/tx_ttnews/' . $templateflex_file;
            }
            $this->templateCode = $this->getFileResource($templateflex_file);
        } else {
            $this->templateCode = $this->getFileResource($this->conf['templateFile']);
        }

        $splitMark = md5(microtime(true));
        $globalMarkerArray = [];
        [$globalMarkerArray['###GW1B###'], $globalMarkerArray['###GW1E###']] = explode(
            $splitMark,
            (string)$this->cObj->stdWrap($splitMark, $this->conf['wrap1.'] ?? false)
        );
        [$globalMarkerArray['###GW2B###'], $globalMarkerArray['###GW2E###']] = explode(
            $splitMark,
            (string)$this->cObj->stdWrap($splitMark, $this->conf['wrap2.'] ?? false)
        );
        [$globalMarkerArray['###GW3B###'], $globalMarkerArray['###GW3E###']] = explode(
            $splitMark,
            (string)$this->cObj->stdWrap($splitMark, $this->conf['wrap3.'] ?? false)
        );
        $globalMarkerArray['###GC1###'] = $this->cObj->stdWrap($this->conf['color1'] ?? '', $this->conf['color1.'] ?? false);
        $globalMarkerArray['###GC2###'] = $this->cObj->stdWrap($this->conf['color2'] ?? '', $this->conf['color2.'] ?? false);
        $globalMarkerArray['###GC3###'] = $this->cObj->stdWrap($this->conf['color3'] ?? '', $this->conf['color3.'] ?? false);
        $globalMarkerArray['###GC4###'] = $this->cObj->stdWrap($this->conf['color4'] ?? '', $this->conf['color4.'] ?? false);

        if (!($this->templateCode = $this->markerBasedTemplateService->substituteMarkerArray(
            $this->templateCode,
            $globalMarkerArray
        ))) {
            $this->errors[] = 'No HTML template found';
        }
    }

    /**
     * extends the pid_list given from $conf or FF recursively by the pids of the subpages
     * generates an array from the pagetitles of those pages
     */
    public function initPidList(): void
    {
        $pid_list = '';
        // pid_list is the pid/list of pids from where to fetch the news items.
        $pid_list = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'pages', 's_misc');
        $pid_list = $pid_list ?: trim((string)$this->cObj->stdWrap(
            $this->conf['pid_list'],
            $this->conf['pid_list.'] ?? []
        ));
        $pid_list = $pid_list ? implode(',', GeneralUtility::intExplode(',', (string)$pid_list)) : (string)$this->tsfe->id;

        $recursive = $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, 'recursive', 's_misc');
        if ($recursive === null || $recursive === '') {
            $recursive = $this->cObj->stdWrap($this->conf['recursive'], $this->conf['recursive.'] ?? []);
        }

        // extend the pid_list by recursive levels
        $this->pid_list = $this->pi_getPidList($pid_list, $recursive);
        $this->pid_list = $this->pid_list ?: 0;
        $this->errors = [];
        if (!$this->pid_list) {
            $this->errors[] = 'No pid_list defined';
        }
    }

    /**
     * returns fieldvalue from pagerecord. Pageresords are stored internally in $this->pageArray
     *
     * @param $uid
     * @param $fN
     *
     * @return string
     */
    protected function getPageArrayEntry($uid, $fN)
    {
        // Get pages
        $val = '';

        $L = (int)($this->sys_language_content);

        if ($uid && $fN) {
            $key = $uid . '_' . $L;
            if (is_array($this->pageArray[$key] ?? null)) {
                $val = $this->pageArray[$key][$fN] ?? '';
            } else {
                $rows = $this->db->exec_SELECTgetRows('*', 'pages', 'uid=' . $uid);
                $row = $rows[0];
                // get the translated record if the content language is not the default language
                if ($L) {
                    $row = $this->tsfe->sys_page->getPageOverlay($uid, $L);
                }
                $this->pageArray[$key] = $row;
                $val = $this->pageArray[$key][$fN] ?? '';
            }
        }

        return $val;
    }

    /**********************************************************************************************
     *
     *              Helper Functions
     *
     **********************************************************************************************/

    /**
     * @param      $lConf
     * @param      $limit
     * @param bool $resCount
     *
     * @return array
     */
    protected function processOptionSplit($lConf, $limit, $resCount = false)
    {
        // splits the configuration for optionSplit support
        if ($limit <= $resCount || $resCount === false) {
            $splitCount = $limit;
        } else {
            $splitCount = $resCount;
        }

        return GeneralUtility::makeInstance(TypoScriptService::class)->explodeConfigurationForOptionSplit(
            $lConf,
            $splitCount
        );
    }

    /**
     * @param $selectConf
     *
     * @return array
     */
    protected function getArchiveMenuRange($selectConf)
    {
        $range = ['minval' => 0, 'maxval' => 0];

        if ($this->conf['amenuStart']) {
            $range['minval'] = strtotime((string)$this->conf['amenuStart']);
        }
        if ($this->conf['amenuEnd']) {
            $eTime = strtotime((string)$this->conf['amenuEnd']);
            if ($eTime > $range['minval']) {
                $range['maxval'] = $eTime;
            }
        }

        if (!($range['minval'] && $range['maxval'])) {
            // find minval and/or maxval automatically
            $selectConf['selectFields'] = '';
            if (!$range['minval']) {
                $selectConf['selectFields'] .= 'MIN(tt_news.datetime) AS minval';
                if ($this->conf['ignoreNewsWithoutDatetimeInAmenu']) {
                    $selectConf['where'] .= ' AND tt_news.datetime > 0';
                }
            }
            if (!$range['maxval']) {
                $selectConf['selectFields'] .= ($selectConf['selectFields'] ? ', ' : '') . 'MAX(tt_news.datetime) AS maxval';
            }

            $res = $this->exec_getQuery('tt_news', $selectConf);
            $range = $this->db->sql_fetch_assoc($res);
        }

        return $range;
    }

    /**
     * Returns a subpart from the input content stream.
     * Enables pre-/post-processing of templates/templatefiles
     *
     * @param    string $myTemplate Content stream, typically HTML template content.
     * @param    string $myKey      Marker string, typically on the form "###...###"
     * @param    array  $row        Optional: the active row of data - if available
     *
     * @return    string        The subpart found, if found.
     */
    protected function getNewsSubpart($myTemplate, $myKey, $row = [])
    {
        return $this->markerBasedTemplateService->getSubpart($myTemplate, $myKey);
    }

    /**
     * @param $marker
     *
     * @return bool
     */
    protected function isRenderMarker($marker)
    {
        if ($this->useFluidRenderer || in_array($marker, $this->renderMarkers)) {
            return true;
        }

        return false;
    }

    /**
     * @param $template
     *
     * @return mixed
     */
    protected function getMarkers($template)
    {
        $matches = [];
        preg_match_all('/###(.+)###/Us', (string)$template, $matches);

        return array_unique($matches[0]);
    }

    /**
     * converts the datetime of a record into variables you can use in realurl
     *
     * @param    int $tstamp the timestamp to convert into a HR date
     */
    protected function getHrDateSingle($tstamp)
    {
        $this->piVars['year'] = date('Y', $tstamp);
        $this->piVars['month'] = date('m', $tstamp);
        if (!$this->conf['useHRDatesSingleWithoutDay']) {
            $this->piVars['day'] = date('d', $tstamp);
        }
    }

    /**
     * Calls user function defined in TypoScript
     *
     * @param    int $mConfKey : if this value is empty the var $mConfKey is not processed
     * @param    mixed   $passVar  : this var is processed in the user function
     *
     * @return    mixed        the processed $passVar
     */
    protected function userProcess($mConfKey, mixed $passVar)
    {
        if ($this->conf[$mConfKey] ?? false) {
            $funcConf = $this->conf[$mConfKey . '.'];
            $funcConf['parentObj'] = &$this;
            $passVar = $this->tsfe->cObj->callUserFunction($this->conf[$mConfKey], $funcConf, $passVar);
        }

        return $passVar;
    }

    /**
     * returns the subpart name. if 'altMainMarkers.' are given this name is used instead of the default marker-name.
     *
     * @param    string $subpartMarker : name of the subpart to be substituted
     *
     * @return    string        new name of the template subpart
     */
    protected function spMarker($subpartMarker)
    {
        $sPBody = substr($subpartMarker, 3, -3);
        $altSPM = '';
        if (isset($this->conf['altMainMarkers.'])) {
            $altSPM = trim((string)$this->cObj->stdWrap(
                $this->conf['altMainMarkers.'][$sPBody],
                $this->conf['altMainMarkers.'][$sPBody . '.']
            ));
            /** @var TimeTracker $timeTracker */
            $timeTracker = GeneralUtility::makeInstance(TimeTracker::class);
            $timeTracker->setTSlogMessage(
                'Using alternative subpart marker for \'' . $subpartMarker . '\': ' . $altSPM,
                1
            );
        }

        return $altSPM ?: $subpartMarker;
    }

    /**
     * Format string with general_stdWrap from configuration
     *
     * @param    string $str string to wrap
     *
     * @return    string        wrapped string
     */
    public function formatStr($str)
    {
        if (is_array($this->conf['general_stdWrap.'] ?? null)) {
            $str = $this->local_cObj->stdWrap($str, $this->conf['general_stdWrap.']);
        }

        return $str;
    }

    /**
     * Returns alternating layouts
     *
     * @param    string  $templateCode       html code of the template subpart
     * @param    int $alternatingLayouts number of alternatingLayouts
     * @param    string  $marker             name of the content-markers in this template-subpart
     *
     * @return    array        html code for alternating content markers
     */
    protected function getLayouts($templateCode, $alternatingLayouts, $marker)
    {
        $out = [];
        if ($this->config['altLayoutsOptionSplit']) {
            $splitLayouts = GeneralUtility::makeInstance(TypoScriptService::class)->explodeConfigurationForOptionSplit(
                ['ln' => $this->config['altLayoutsOptionSplit']],
                $this->config['limit']
            );
            if (is_array($splitLayouts)) {
                foreach ($splitLayouts as $tmpconf) {
                    $a = $tmpconf['ln'];
                    $m = '###' . $marker . ($a ? '_' . $a : '') . '###';
                    if (strstr($templateCode, $m)) {
                        $out[] = $this->markerBasedTemplateService->getSubpart($templateCode, $m);
                    }
                }
            }
        } else {
            for ($a = 0; $a < $alternatingLayouts; $a++) {
                $m = '###' . $marker . ($a ? '_' . $a : '') . '###';
                if (strstr($templateCode, $m)) {
                    $out[] = $this->markerBasedTemplateService->getSubpart($templateCode, $m);
                } else {
                    break;
                }
            }
        }

        return $out;
    }

    /**
     * @param      $singlePid
     * @param      $row
     * @param      $piVarsArray
     * @param bool $urlOnly
     *
     * @return array|string
     */
    public function getSingleViewLink(&$singlePid, &$row, $piVarsArray, $urlOnly = false)
    {
        $tmpY = false;
        $tmpM = false;
        $tmpD = false;
        if ($this->conf['useHRDates']) {
            $piVarsArray['pS'] = null;
            $piVarsArray['pL'] = null;
            $piVarsArray['arc'] = null;
            if ($this->conf['useHRDatesSingle']) {
                $tmpY = $this->piVars['year'] ?? null;
                $tmpM = $this->piVars['month'] ?? null;
                $tmpD = $this->piVars['day'] ?? null;

                $this->getHrDateSingle($row['datetime']);
                $piVarsArray['year'] = $this->piVars['year'] ?? null;
                $piVarsArray['month'] = $this->piVars['month'] ?? null;
                $piVarsArray['day'] = $this->piVars['day'] ?? null;
            }
        } else {
            $piVarsArray['year'] = null;
            $piVarsArray['month'] = null;
        }

        $piVarsArray['tt_news'] = $row['uid'];

        $linkWrap = explode(
            $this->token,
            $this->pi_linkTP_keepPIvars(
                $this->token,
                $piVarsArray,
                $this->allowCaching,
                $this->conf['dontUseBackPid'],
                $singlePid
            )
        );
        $url = $this->cObj->lastTypoLinkResult->getUrl();

        // hook for processing of links
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['getSingleViewLinkHook'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['getSingleViewLinkHook'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $params = ['singlePid' => &$singlePid, 'row' => &$row, 'piVarsArray' => $piVarsArray];
                $_procObj->processSingleViewLink($linkWrap, $url, $params, $this);
            }
        }
        $this->local_cObj->cObjGetSingle(
            'LOAD_REGISTER',
            ['newsMoreLink' => $linkWrap[0] . $this->pi_getLL('more') . $linkWrap[1], 'newsMoreLink_url' => $url]
        );

        if ($this->conf['useHRDates'] && $this->conf['useHRDatesSingle']) {
            $this->piVars['year'] = $tmpY;
            $this->piVars['month'] = $tmpM;
            $this->piVars['day'] = $tmpD;
        }

        if ($urlOnly) {
            return $url;
        }
        return $linkWrap;
    }

    /**
     * Overrides a LocalLang value and takes care of the XLIFF structure.
     *
     * @param string $key   Key of the label
     * @param string $value Value of the label
     */
    protected function overrideLL($key, $value)
    {
        if (isset($this->LOCAL_LANG[$this->LLkey][$key][0]['target'])) {
            $this->LOCAL_LANG[$this->LLkey][$key][0]['target'] = $value;
        } else {
            $this->LOCAL_LANG[$this->LLkey][$key] = $value;
        }
    }

    /**
     * This function returns the mime type of the file specified by the url
     * (copied from t3lib_htmlmail of TYPO3 4.6 which got removed in TYPO3 4.7)
     *
     * @param    string $url : the url
     *
     * @return    string        $mimeType: the mime type found in the header
     */
    protected function getMimeTypeByHttpRequest($url)
    {
        $mimeType = '';
        $headers = trim((string)GeneralUtility::getUrl($url));
        if ($headers) {
            $matches = [];
            if (preg_match('/(Content-Type:[\s]*)([a-zA-Z_0-9\/\-\.\+]*)([\s]|$)/', $headers, $matches)) {
                $mimeType = trim($matches[2]);
            }
        }

        return $mimeType;
    }

    /**
     * @param $val
     * @param $catSelLinkParams
     * @param $lConf
     * @param $output
     *
     * @return array
     */
    protected function handleCatTextMode($val, $catSelLinkParams, $catTitle, $lConf, $output)
    {
        if ($this->config['catTextMode'] == 2) {
            // link to category shortcut
            $target = ($val['shortcut'] ? $val['shortcut_target'] : '');
            $pageID = ($val['shortcut'] ?: $catSelLinkParams);
            $linkedTitle = $this->pi_linkToPage($catTitle, $pageID, $target);
            $output[] = $this->local_cObj->stdWrap($linkedTitle, $lConf['title_stdWrap.']);

            return $output;
        }
        if ($this->config['catTextMode'] == 3) {
            if ($this->conf['useHRDates']) {
                $output[] = $this->local_cObj->stdWrap($this->pi_linkTP_keepPIvars($catTitle, [
                    'cat' => $val['uid'],
                    'year' => ($this->piVars['year'] ?: null),
                    'month' => ($this->piVars['month'] ?: null),
                    'backPid' => null,
                    'tt_news' => null,
                    $this->pointerName => null,
                ], $this->allowCaching, 0, $catSelLinkParams), $lConf['title_stdWrap.']);

                return $output;
            }
            $output[] = $this->local_cObj->stdWrap($this->pi_linkTP_keepPIvars($catTitle, [
                'cat' => $val['uid'],
                'backPid' => null,
                'tt_news' => null,
                $this->pointerName => null,
            ], $this->allowCaching, 0, $catSelLinkParams), $lConf['title_stdWrap.']);

            return $output;
        }

        return $output;
    }

    /**
     * Set cObj (needed in ajax calls from  CATMENU)
     *
     * @param ContentObjectRenderer $cObj
     */
    public function setCObj(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }

    /**
     * Set cObj data
     */
    public function setCObjData(array $data): void
    {
        $this->cObj->data = $data;
    }

    /**
     * Get cObjUid
     *
     * @return int
     */
    public function getCObjUid()
    {
        return (int)($this->cObj->data['uid']);
    }
}
