<?php

namespace RG\TtNews\Plugin;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2004 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  (c) 2004-2020 Rupert Germann (rupi@gmx.li)
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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\ResultStatement;
use RG\TtNews\Menu\Catmenu;
use RG\TtNews\Database\Database;
use RG\TtNews\Utility\Div;
use RG\TtNews\Helper\Helpers;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;
use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Plugin 'news' for the 'tt_news' extension.
 *
 * @author     Rupert Germann <rupi@gmx.li>
 * @package    TYPO3
 * @subpackage tt_news
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
    public $config = array(); // the processed TypoScript configuration array
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
    public $sViewSplitLConf = array();
    /**
     * @var array
     */
    public $langArr = array(); // the languages found in the tt_news sysfolder
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
    public $fieldNames = array();
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
    public $categories = array();
    /**
     * @var array
     */
    public $pageArray = array(); // internal cache with an array of the pages in the pid-list
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
    public $errors = array();

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
     * disables internal rendering. If set to true an external renderer like Fluid can be used
     *
     * @var bool
     */
    private $useUpstreamRenderer = false;

    /**
     * @var array
     */
    public $upstreamVars = array();

    /**
     * @var
     */
    private $listData;

    /**
     * TtNews constructor.
     */
    public function __construct()
    {
        //if search => disable cache hash check to avoid pageNotFoundOnCHashError, see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::reqCHash
        if (GeneralUtility::_GPmerged($this->prefixId)['swords']) {
            $this->pi_checkCHash = false;
        }

        $this->markerBasedTemplateService = new MarkerBasedTemplateService();
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
     * @throws DBALException
     */
    public function main_news($content, $conf)
    {
        $this->confArr = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tt_news'];

        $this->helpers = new Helpers($this);
        $this->conf = $conf; //store configuration

        if ($this->conf['upstreamRendererFunc']) {
            $this->useUpstreamRenderer = true;
        }

        // leave early if USER_INT
        $this->convertToUserIntObject = $this->conf['convertToUserIntObject'] ? 1 : 0;
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

            $theCode = (string)strtoupper(trim($theCode));
            $this->theCode = $theCode;
            // initialize category vars
            $this->initCategoryVars();
            $this->initGenericMarkers();

            switch ($theCode) {
                case 'SINGLE' :
                case 'SINGLE2' :
                    $content .= $this->displaySingle();
                    break;
                case 'LATEST' :
                case 'LIST' :
                case 'LIST2' :
                case 'LIST3' :
                case 'HEADER_LIST' :
                case 'SEARCH' :
                case 'XML' :
                    $content .= $this->displayList();
                    break;
                case 'AMENU' :
                    $content .= $this->displayArchiveMenu();
                    break;
                case 'CATMENU' :
                    $content .= $this->displayCatMenu();
                    break;
                default :
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


    /**
     * [Describe function...]
     *
     */
    protected function preInit()
    {
        // Init FlexForm configuration for plugin
        $this->pi_initPIflexForm();

        $flexformTyposcript = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'myTS', 's_misc');
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
        $code = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'what_to_display', 'sDEF');
        $this->config['code'] = ($code ? $code : $this->cObj->stdWrap($this->conf['code'], $this->conf['code.']));

        if ($this->conf['displayCurrentRecord']) {
            $this->config['code'] = $this->conf['defaultCode'] ? trim($this->conf['defaultCode']) : 'SINGLE';
            $this->tt_news_uid = $this->cObj->data['uid'];
        }

        // get codes and decide which function is used to process the content
        $codes = GeneralUtility::trimExplode(',',
            $this->config['code'] ? $this->config['code'] : $this->conf['defaultCode'], 1);
        if (!count($codes)) { // no code at all
            $codes = array();
            $this->errors[] = 'No code given';
        }

        $this->codes = $codes;
    }


    /**
     * Init Function: here all the needed configuration values are stored in class variables..
     *
     * @throws DBALException
     */
    protected function init()
    {
        $this->db = Database::getInstance();
        $this->tsfe = $GLOBALS['TSFE'];
        $this->pi_loadLL('EXT:tt_news/Resources/Private/Language/Plugin/locallang_pi.xlf'); // Loading language-labels
        $this->pi_setPiVarDefaults(); // Set default piVars from TS

        $this->SIM_ACCESS_TIME = $GLOBALS['SIM_ACCESS_TIME'];
        // fallback for TYPO3 < 4.2
        if (!$this->SIM_ACCESS_TIME) {
            $simTime = $GLOBALS['SIM_EXEC_TIME'];
            $this->SIM_ACCESS_TIME = $simTime - ($simTime % 60);
        }

        $this->initCaching();

        $this->local_cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class); // Local cObj.
        $this->enableFields = $this->getEnableFields('tt_news');

        if ($this->tt_news_uid === 0) { // no tt_news_uid set by displayCurrentRecord
            $this->tt_news_uid = intval($this->piVars['tt_news']); // Get the submitted uid of a news (if any)
        }

        $this->token = md5(microtime());

        if (ExtensionManagementUtility::isLoaded('version')) {
            $this->versioningEnabled = true;
        }
        // load available syslanguages
        $this->initLanguages();
        // sys_language_mode defines what to do if the requested translation is not found
        $this->sys_language_mode = ($this->conf['sys_language_mode'] ? $this->conf['sys_language_mode'] : $this->tsfe->sys_language_mode);


        if ($this->conf['searchFieldList']) {
            // get fieldnames from the tt_news db-table
            $this->fieldNames = array_keys($this->db->admin_get_fields('tt_news'));
            $searchFieldList = $this->helpers->validateFields($this->conf['searchFieldList'], $this->fieldNames);
            if ($searchFieldList) {
                $this->searchFieldList = $searchFieldList;
            }
        }
        // Archive:
        $archiveMode = trim($this->conf['archiveMode']); // month, quarter or year listing in AMENU
        $this->config['archiveMode'] = $archiveMode ? $archiveMode : 'month';

        // arcExclusive : -1=only non-archived; 0=don't care; 1=only archived
        $arcExclusive = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'archive', 'sDEF');
        $this->arcExclusive = $arcExclusive ? $arcExclusive : intval($this->conf['archive']);

        $this->config['datetimeDaysToArchive'] = intval($this->conf['datetimeDaysToArchive']);
        $this->config['datetimeHoursToArchive'] = intval($this->conf['datetimeHoursToArchive']);
        $this->config['datetimeMinutesToArchive'] = intval($this->conf['datetimeMinutesToArchive']);

        if ($this->conf['useHRDates']) {
            $this->helpers->convertDates();
        }

        // list of pages where news records will be taken from
        if (!$this->conf['dontUsePidList']) {
            $this->initPidList();
        }

        // itemLinkTarget is only used for categoryLinkMode 3 (catselector) in framesets
        $this->conf['itemLinkTarget'] = trim($this->conf['itemLinkTarget']);
        // id of the page where the search results should be displayed
        $this->config['searchPid'] = intval($this->conf['searchPid']);

        // pages in Single view will be divided by this token
        $this->config['pageBreakToken'] = trim($this->conf['pageBreakToken']) ? trim($this->conf['pageBreakToken']) : '<---newpage--->';

        $this->config['singleViewPointerName'] = trim($this->conf['singleViewPointerName']) ? trim($this->conf['singleViewPointerName']) : 'sViewPointer';

        $maxWordsInSingleView = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'maxWordsInSingleView',
            's_misc'));
        $maxWordsInSingleView = $maxWordsInSingleView ? $maxWordsInSingleView : intval($this->conf['maxWordsInSingleView']);
        $this->config['maxWordsInSingleView'] = $maxWordsInSingleView ? $maxWordsInSingleView : 0;
        $this->config['useMultiPageSingleView'] = $this->conf['useMultiPageSingleView'];

        // pid of the page with the single view. the old var PIDitemDisplay is still processed if no other value is found
        $singlePid = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'PIDitemDisplay', 's_misc');
        $this->config['singlePid'] = $singlePid ? $singlePid : intval($this->cObj->stdWrap($this->conf['singlePid'],
            $this->conf['singlePid.']));
        if (!$this->config['singlePid']) {
            $this->errors[] = 'No singlePid defined';
        }
        // pid to return to when leaving single view
        $backPid = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'backPid', 's_misc'));
        $backPid = $backPid ? $backPid : intval($this->conf['backPid']);
        $backPid = $backPid ? $backPid : intval($this->piVars['backPid']);
        $backPid = $backPid ? $backPid : $this->tsfe->id;
        $this->config['backPid'] = $backPid;

        // max items per page
        $FFlimit = MathUtility::forceIntegerInRange($this->pi_getFFvalue($this->cObj->data['pi_flexform'],
            'listLimit', 's_template'), 0, 1000);

        $limit = MathUtility::forceIntegerInRange($this->cObj->stdWrap($this->conf['limit'],
            $this->conf['limit.']), 0, 1000);
        $limit = $limit ? $limit : 50;
        $this->config['limit'] = $FFlimit ? $FFlimit : $limit;

        $latestLimit = MathUtility::forceIntegerInRange($this->cObj->stdWrap($this->conf['latestLimit'],
            $this->conf['latestLimit.']), 0, 1000);
        $latestLimit = $latestLimit ? $latestLimit : 10;
        $this->config['latestLimit'] = $FFlimit ? $FFlimit : $latestLimit;

        // orderBy and groupBy statements for the list Query
        $orderBy = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'listOrderBy', 'sDEF');
        $orderByTS = trim($this->conf['listOrderBy']);
        $orderBy = $orderBy ? $orderBy : $orderByTS;
        $this->config['orderBy'] = $orderBy;

        if ($orderBy) {
            $ascDesc = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'ascDesc', 'sDEF');
            $this->config['ascDesc'] = $ascDesc;
            if ($this->config['ascDesc']) {
                // remove ASC/DESC from 'orderBy' if it is already set from TS
                $this->config['orderBy'] = preg_replace('/( DESC| ASC)\b/i', '', $this->config['orderBy']);
            }
        }
        $this->config['groupBy'] = trim($this->conf['listGroupBy']);

        // if this is set, the first image is handled as preview image, which is only shown in list view
        $fImgPreview = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'firstImageIsPreview', 's_misc');
        $this->config['firstImageIsPreview'] = $fImgPreview ? $fImgPreview : $this->conf['firstImageIsPreview'];
        $forcefImgPreview = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'forceFirstImageIsPreview',
            's_misc');
        $this->config['forceFirstImageIsPreview'] = $forcefImgPreview ? $fImgPreview : $this->conf['forceFirstImageIsPreview'];

        // List start id
        //		$listStartId = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'listStartId', 's_misc'));
        //		$this->config['listStartId'] = /*$listStartId?$listStartId:*/intval($this->conf['listStartId']);
        // supress pagebrowser
        $noPageBrowser = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'noPageBrowser', 's_template');
        $this->config['noPageBrowser'] = $noPageBrowser ? $noPageBrowser : $this->conf['noPageBrowser'];

        // image sizes/optionSplit given from FlexForms
        $this->config['FFimgH'] = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'imageMaxHeight',
            's_template'));
        $this->config['FFimgW'] = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'imageMaxWidth',
            's_template'));

        // Get number of alternative Layouts (loop layout in LATEST and LIST view) default is 2:
        $altLayouts = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'alternatingLayouts',
            's_template'));
        $altLayouts = $altLayouts ? $altLayouts : intval($this->conf['alternatingLayouts']);
        $this->alternatingLayouts = $altLayouts ? $altLayouts : 2;

        $altLayouts = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'altLayoutsOptionSplit',
            's_template'));
        $this->config['altLayoutsOptionSplit'] = $altLayouts ? $altLayouts : trim($this->conf['altLayoutsOptionSplit']);

        // Get cropping length


        $croppingLenghtOptionSplit = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'],
            'croppingLenghtOptionSplit', 's_template'));
        $this->config['croppingLenghtOptionSplit'] = $croppingLenghtOptionSplit ? $croppingLenghtOptionSplit : trim($this->conf['croppingLenghtOptionSplit']);
        $this->config['croppingLenght'] = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'croppingLenght',
            's_template'));

        $this->initTemplate();

        // Configure caching
        if (isset($this->conf['allowCaching'])) {
            $this->allowCaching = $this->conf['allowCaching'] ? 1 : 0;
        }
        if (!$this->allowCaching) {
            $this->tsfe->set_no_cache();
        }

        // get siteUrl for links in rss feeds. the 'dontInsert' option seems to be needed in some configurations depending on the baseUrl setting
        if (!$this->conf['displayXML.']['dontInsertSiteUrl']) {
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
     * @throws DBALException
     */

    public function displayList($excludeUids = '0')
    {
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
                $searchMarkers = array(
                    '###FORM_URL###' => $this->pi_linkTP_keepPIvars_url(array('pointer' => null, 'cat' => null), 0, 1,
                        $this->config['searchPid']),
                    '###SWORDS###' => htmlspecialchars($this->piVars['swords']),
                    '###SEARCH_BUTTON###' => $this->pi_getLL('searchButtonLabel')
                );

                // Hook for any additional form fields
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['additionalFormSearchFields'])) {
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
                unset($searchMarkers);

                // do the search and add the result to the $where string
                if ($this->piVars['swords']) {
                    $where = $this->searchWhere(trim($this->piVars['swords']));
                    $theCode = 'SEARCH';
                } else {
                    $where = ($this->conf['emptySearchAtStart'] ? 'AND 1=0' : ''); // display an empty list, if 'emptySearchAtStart' is set.
                }
                break;

            // xml news export
            case 'XML' :
                $prefix_display = 'displayXML';
                // $this->arcExclusive = -1; // Only latest, non archive news
                $this->allowCaching = $this->conf['displayXML.']['xmlCaching'];
                $this->config['limit'] = $this->conf['displayXML.']['xmlLimit'] ? $this->conf['displayXML.']['xmlLimit'] : $this->config['limit'];

                switch ($this->conf['displayXML.']['xmlFormat']) {
                    case 'rss091' :
                        $templateName = 'TEMPLATE_RSS091';
                        $this->templateCode = $this->getFileResource($this->conf['displayXML.']['rss091_tmplFile']);
                        break;

                    case 'rss2' :
                        $templateName = 'TEMPLATE_RSS2';
                        $this->templateCode = $this->getFileResource($this->conf['displayXML.']['rss2_tmplFile']);
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
        $userCodes = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['what_to_display'];

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
            if (($this->arcExclusive > 0 && !$this->piVars['pS'] && $theCode != 'SEARCH')) {
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

        if ($this->piVars['pS'] && !$this->piVars['pL']) {
            $noPeriod = 1; // override the period length checking in getSelectConf
        }

        if ($this->conf['displayCurrentRecord'] && $this->tt_news_uid) {
            $this->pid_list = $this->cObj->data['pid'];
            $where = 'AND tt_news.uid=' . $this->tt_news_uid;
        }

        if ($excludeUids) {
            $where = ' AND tt_news.uid NOT IN (' . $excludeUids . ')';
        }

        // build parameter Array for List query
        $selectConf = $this->getSelectConf($where, $noPeriod);

        // performing query to count all news (we need to know it for browsing):
        if ($selectConf['leftjoin'] || ($this->theCode == 'RELATED' && $this->relNewsUid)) {
            $selectConf['selectFields'] = 'COUNT(DISTINCT tt_news.uid) as c';
        } else {
            $selectConf['selectFields'] = 'COUNT(tt_news.uid) as c';
        }

        $newsCount = 0;
        $countSelConf = $selectConf;
        unset($countSelConf['orderBy']);

        if (($res = $this->exec_getQuery('tt_news', $countSelConf)->fetch())) {
            $newsCount = $res['c'];
        }

        $this->newsCount = $newsCount;

        // Only do something if the query result is not empty
        if ($newsCount > 0) {
            // Init Templateparts: $t['total'] is complete template subpart (TEMPLATE_LATEST f.e.)
            // $t['item'] is an array with the alternative subparts (NEWS, NEWS_1, NEWS_2 ...)
            $t = array();
            $t['total'] = $this->getNewsSubpart($this->templateCode, $this->spMarker('###' . $templateName . '###'));

            $t['item'] = $this->getLayouts($t['total'], $this->alternatingLayouts, 'NEWS');

            // Parse out markers in the templates to prevent unnecessary queries and code from executing
            $this->renderMarkers = $this->getMarkers($t['total']);

            // build query for display:
            if ($selectConf['leftjoin'] || ($this->theCode == 'RELATED' && $this->relNewsUid)) {
                $selectConf['selectFields'] = 'DISTINCT tt_news.uid, tt_news.*';
            } else {
                $selectConf['selectFields'] = 'tt_news.*';
            }

            // exclude the LATEST template from changing its content with the pagebrowser. This can be overridden by setting the conf var latestWithPagebrowser
            if ($this->theCode != 'LATEST' || $this->conf['latestWithPagebrowser']) {
                $selectConf['begin'] = intval($this->piVars[$this->pointerName]) * $this->config['limit'];
            }

            $selectConf['max'] = $this->config['limit'];

            // Reset:
            $subpartArray = array();
            $wrappedSubpartArray = array();
            $markerArray = array();


            // get the list of news items and fill them in the CONTENT subpart
            $subpartArray['###CONTENT###'] = $this->getListContent($t['item'], $selectConf, $prefix_display);

            if ($this->isRenderMarker('###NEWS_CATEGORY_ROOTLINE###')) {
                $markerArray['###NEWS_CATEGORY_ROOTLINE###'] = '';
                if ($this->conf['catRootline.']['showCatRootline'] && $this->piVars['cat'] && !strpos($this->piVars['cat'],
                        ',')) {
                    $markerArray['###NEWS_CATEGORY_ROOTLINE###'] = $this->getCategoryPath(array(
                        array('catid' => intval($this->piVars['cat']))
                    ));
                }
            }

            if ($theCode == 'XML') {
                $markerArray = $this->getXmlHeader();
                $subpartArray['###HEADER###'] = $this->markerBasedTemplateService->substituteMarkerArray($this->getNewsSubpart($t['total'],
                    '###HEADER###'), $markerArray);
                if ($this->conf['displayXML.']['xmlFormat']) {
                    if (!empty($this->rdfToc)) {
                        $markerArray['###NEWS_RDF_TOC###'] = '<rdf:Seq>' . "\n" . $this->rdfToc . "\t\t\t" . '</rdf:Seq>';
                    } else {
                        $markerArray['###NEWS_RDF_TOC###'] = '';
                    }
                }
                $subpartArray['###HEADER###'] = $this->markerBasedTemplateService->substituteMarkerArray($this->getNewsSubpart($t['total'],
                    '###HEADER###'), $markerArray);
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
                if ($this->conf['userPageBrowserFunc']) {
                    $markerArray = $this->userProcess('userPageBrowserFunc', $markerArray);
                } else {
                    $this->pi_alwaysPrev = $pbConf['alwaysPrev'];
                    if ($this->conf['usePiBasePagebrowser'] && $this->isRenderMarker('###BROWSE_LINKS###')) {

                        $markerArray = $this->getPagebrowserContent($markerArray, $pbConf, $this->pointerName);

                    } else {
                        $markerArray['###BROWSE_LINKS###'] = $this->makePageBrowser($pbConf['showResultCount'],
                            $pbConf['tableParams'], $this->pointerName);
                    }
                }
            }

            // Adds hook for processing of extra global markers
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraGlobalMarkerHook'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraGlobalMarkerHook'] as $_classRef) {
                    $_procObj = GeneralUtility::makeInstance($_classRef);
                    $markerArray = $_procObj->extraGlobalMarkerProcessor($this, $markerArray);
                }
            }
            if (!$this->useUpstreamRenderer) {
                $content .= $this->markerBasedTemplateService->substituteMarkerArrayCached($t['total'], $markerArray,
                    $subpartArray,
                    $wrappedSubpartArray);
            }
        } elseif (strpos($where, '1=0') !== false) {
            // first view of the search page with the parameter 'emptySearchAtStart' set
            $markerArray['###SEARCH_EMPTY_MSG###'] = $this->local_cObj->stdWrap($this->pi_getLL('searchEmptyMsg'),
                $this->conf['searchEmptyMsg_stdWrap.']);
            $searchEmptyMsg = $this->getNewsSubpart($this->templateCode,
                $this->spMarker('###TEMPLATE_SEARCH_EMPTY###'));

            $content .= $this->markerBasedTemplateService->substituteMarkerArrayCached($searchEmptyMsg, $markerArray);
        } elseif ($this->piVars['swords']) {
            // no results
            $markerArray['###SEARCH_EMPTY_MSG###'] = $this->local_cObj->stdWrap($this->pi_getLL('noResultsMsg'),
                $this->conf['searchEmptyMsg_stdWrap.']);
            $searchEmptyMsg = $this->getNewsSubpart($this->templateCode,
                $this->spMarker('###TEMPLATE_SEARCH_EMPTY###'));
            $content .= $this->markerBasedTemplateService->substituteMarkerArrayCached($searchEmptyMsg, $markerArray);
        } elseif ($theCode == 'XML') {
            // fill at least the template header
            // Init Templateparts: $t['total'] is complete template subpart (TEMPLATE_LATEST f.e.)
            $t = array();
            $t['total'] = $this->getNewsSubpart($this->templateCode, $this->spMarker('###' . $templateName . '###'));

            $this->renderMarkers = $this->getMarkers($t['total']);

            // Reset:
            $subpartArray = array();
            // header data
            $markerArray = $this->getXmlHeader();
            $subpartArray['###HEADER###'] = $this->markerBasedTemplateService->substituteMarkerArray($this->getNewsSubpart($t['total'],
                '###HEADER###'), $markerArray);
            // substitute the xml declaration (it's not included in the subpart ###HEADER###)
            $t['total'] = $this->markerBasedTemplateService->substituteMarkerArray($t['total'], array(
                '###XML_DECLARATION###' => $markerArray['###XML_DECLARATION###']
            ));
            $t['total'] = $this->markerBasedTemplateService->substituteMarkerArray($t['total'],
                array('###SITE_LANG###' => $markerArray['###SITE_LANG###']));
            $t['total'] = $this->markerBasedTemplateService->substituteSubpart($t['total'], '###HEADER###',
                $subpartArray['###HEADER###'], 0);
            $t['total'] = $this->markerBasedTemplateService->substituteSubpart($t['total'], '###CONTENT###', '', 0);

            $content .= $t['total'];
        } elseif ($this->arcExclusive && $this->piVars['pS'] && $this->tsfe->sys_language_content) {
            $markerArray = array();
            // this matches if a user has switched languages within a archive period that contains no items in the desired language
            $content .= $this->local_cObj->stdWrap($this->pi_getLL('noNewsForArchPeriod'),
                $this->conf['noNewsToListMsg_stdWrap.']);
        } else {
            $markerArray = array();
            $content .= $this->local_cObj->stdWrap($this->pi_getLL('noNewsToListMsg'),
                $this->conf['noNewsToListMsg_stdWrap.']);
        }

        if ($this->conf['upstreamRendererFunc']) {
            if (!isset($markerArray)) {
                $markerArray = array();
            }
            $content = $this->userProcess('upstreamRendererFunc',
                array('rows' => $this->listData, 'markerArray' => $markerArray, 'content' => $content));
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

        $wrapArrFields = explode(',',
            'disabledLinkWrap,inactiveLinkWrap,activeLinkWrap,browseLinksWrap,showResultsWrap,showResultsNumbersWrap,browseBoxWrap');
        $wrapArr = array();
        foreach ($wrapArrFields as $key) {
            if ($pbConf[$key]) {
                $wrapArr[$key] = $pbConf[$key];
            }
        }

        $tmpPS = false;
        $tmpPL = false;
        if ($this->conf['useHRDates']) {
            // prevent adding pS & pL to pagebrowser links if useHRDates is enabled
            $tmpPS = $this->piVars['pS'];
            unset($this->piVars['pS']);
            $tmpPL = $this->piVars['pL'];
            unset($this->piVars['pL']);
        }

        if ($this->allowCaching) {
            // if there is a GETvar in the URL that is not in this list, caching will be disabled for the pagebrowser links
            $this->pi_isOnlyFields = $pointerName . ',tt_news,year,month,day,pS,pL,arc,cat';

            $pi_isOnlyFieldsArr = explode(',', $this->pi_isOnlyFields);
            $highestVal = 0;
            foreach ($pi_isOnlyFieldsArr as $v) {
                if ($this->piVars[$v] > $highestVal) {
                    $highestVal = $this->piVars[$v];
                }
            }
            $this->pi_lowerThan = $highestVal + 1;
        }

        // render pagebrowser
        $markerArray['###BROWSE_LINKS###'] = $this->pi_list_browseresults($pbConf['showResultCount'],
            $pbConf['tableParams'], $wrapArr, $pointerName, $pbConf['hscText']);

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
     * @throws DBALException
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

        $this->splitLConf = array();
        $cropSuffix = false;
        if ($this->conf['enableOptionSplit']) {
            if ($this->config['croppingLenghtOptionSplit']) {
                $crop = $lConf['subheader_stdWrap.']['crop'];
                if ($this->config['croppingLenght']) {
                    $crop = $this->config['croppingLenght'];
                }
                $cparts = explode('|', $crop);
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
        $pTmp = $this->tsfe->ATagParams;
        $cc = 0;

        $piVarsArray = array(
            'backPid' => ($this->conf['dontUseBackPid'] ? null : $this->config['backPid']),
            'year' => ($this->conf['dontUseBackPid'] ? null : ($this->piVars['year'] ? $this->piVars['year'] : null)),
            'month' => ($this->conf['dontUseBackPid'] ? null : ($this->piVars['month'] ? $this->piVars['month'] : null))
        );


        // needed for external renderer
        $this->listData = array();

        // Getting elements
        while (($row = $this->db->sql_fetch_assoc($res))) {
            // gets the option splitted config for this record
            if ($this->conf['enableOptionSplit'] && !empty($this->splitLConf[$cc])) {
                $lConf = $this->splitLConf[$cc];
                $lConf['subheader_stdWrap.']['crop'] .= $cropSuffix;

            }

            $wrappedSubpartArray = array();
            $titleField = $lConf['linkTitleField'] ? $lConf['linkTitleField'] : '';

            // First get workspace/version overlay:
            if ($this->versioningEnabled) {
                $this->tsfe->sys_page->versionOL('tt_news', $row);
            }
            // Then get localization of record:
            if ($this->tsfe->sys_language_content) {
                $row = $this->tsfe->sys_page->getRecordOverlay('tt_news', $row, $this->tsfe->sys_language_content,
                    $this->tsfe->sys_language_contentOL);
            }

            // Register displayed news item globally:
            $GLOBALS['T3_VAR']['displayedNews'][] = $row['uid'];

            $this->tsfe->ATagParams = $pTmp . ' title="' . $this->local_cObj->stdWrap(trim(htmlspecialchars($row[$titleField])),
                    $lConf['linkTitleField.']) . '"';

            if ($this->conf[$prefix_display . '.']['catOrderBy']) {
                $this->config['catOrderBy'] = $this->conf[$prefix_display . '.']['catOrderBy'];
            }

            $this->categories = array();
            $this->categories[$row['uid']] = $this->getCategories($row['uid']);

            $catSPid = false;
            if ($row['type'] == 1 || $row['type'] == 2) {
                // News type article or external url
                $this->local_cObj->setCurrentVal($row['type'] == 1 ? $row['page'] : $row['ext_url']);
                $pageTypoLink = $this->local_cObj->typolink('|||', $this->conf['pageTypoLink.']);
                $wrappedSubpartArray['###LINK_ITEM###'] = explode('|||', $pageTypoLink);

                // fill the link string in a register to access it from TS
                $this->local_cObj->cObjGetSingle('LOAD_REGISTER', array(
                    'newsMoreLink' => $this->local_cObj->typolink($this->pi_getLL('more'), $this->conf['pageTypoLink.'])
                ));
            } else {
                //  Overwrite the singlePid from config-array with a singlePid given from the first entry in $this->categories
                if ($this->conf['useSPidFromCategory'] && is_array($this->categories)) {
                    $tmpcats = $this->categories;
                    if (is_array($tmpcats[$row['uid']])) {
                        $catSPid = array_shift($tmpcats[$row['uid']]);
                    }
                }
                $singlePid = $catSPid['single_pid'] ? $catSPid['single_pid'] : $this->config['singlePid'];
                $wrappedSubpartArray['###LINK_ITEM###'] = $this->getSingleViewLink($singlePid, $row, $piVarsArray);
            }

            // reset ATagParams
            $this->tsfe->ATagParams = $pTmp;
            $markerArray = $this->getItemMarkerArray($row, $lConf, $prefix_display);

            // XML
            if ($this->theCode == 'XML') {
                if ($row['type'] == 2) {
                    // external URL
                    $exturl = trim(strpos($row['ext_url'],
                        'http://') !== false ? $row['ext_url'] : 'http://' . $row['ext_url']);
                    $exturl = (strpos($exturl, ' ') ? substr($exturl, 0, strpos($exturl, ' ')) : $exturl);
                    $rssUrl = $exturl;
                } elseif ($row['type'] == 1) {
                    // internal URL
                    $rssUrl = $this->pi_getPageLink($row['page'], '');
                    if (strpos($rssUrl, '://') === false) {
                        $rssUrl = $this->config['siteUrl'] . $rssUrl;
                    }
                } else {
                    // News detail link
                    $link = $this->getSingleViewLink($singlePid, $row, $piVarsArray, true);
                    $rssUrl = trim(strpos($link, '://') === false ? $this->config['siteUrl'] : '') . $link;

                }
                // replace square brackets [] in links with their URLcodes and replace the &-sign with its ASCII code
                $rssUrl = preg_replace(array('/\[/', '/\]/', '/&/'), array('%5B', '%5D', '&#38;'), $rssUrl);
                $markerArray['###NEWS_LINK###'] = $rssUrl;

                if ($this->conf['displayXML.']['xmlFormat'] == 'rdf') {
                    $this->rdfToc .= "\t\t\t\t" . '<rdf:li resource="' . $rssUrl . '" />' . "\n";
                }
            }

            $layoutNum = ($itempartsCount == 0 ? 0 : ($cc % $itempartsCount));
            if (!$this->useUpstreamRenderer) {
                // Store the result of template parsing in the Var $itemsOut, use the alternating layouts
                $itemsOut .= $this->markerBasedTemplateService->substituteMarkerArrayCached($itemparts[$layoutNum],
                    $markerArray, array(),
                    $wrappedSubpartArray);
            }

            $this->listData[] = array('row' => $row, 'markerArray' => $markerArray, 'categories' => $this->categories);

            $cc++;
            if ($cc == $limit) {
                break;
            }
        }

        return $itemsOut;
    }


    /**
     * Displays the "single view" of a news article. Is also used when displaying single news records with the "insert
     * records" content element.
     *
     * @return    string        html-code for the "single view"
     * @throws DBALException
     */
    public function displaySingle()
    {

        $lConf = $this->conf['displaySingle.'];
        $content = '';
        $selectConf = array();
        $selectConf['selectFields'] = '*';
        $selectConf['fromTable'] = 'tt_news';
        $selectConf['where'] = 'tt_news.uid=' . $this->tt_news_uid;
        $selectConf['where'] .= $this->enableFields;


        // function Hook for processing the selectConf array
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['sViewSelectConfHook'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['sViewSelectConfHook'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $selectConf = $_procObj->processSViewSelectConfHook($this, $selectConf);
            }
        }

        $res = $this->db->exec_SELECTquery($selectConf['selectFields'], $selectConf['fromTable'], $selectConf['where'],
            $selectConf['groupBy'], $selectConf['orderBy'], $selectConf['limit']);

        $row = $this->db->sql_fetch_assoc($res);

        // First get workspace/version overlay and fix workspace pid:
        if ($this->versioningEnabled) {
            $this->tsfe->sys_page->versionOL('tt_news', $row);
            $this->tsfe->sys_page->fixVersioningPid('tt_news', $row);
        }
        // Then get localization of record:
        // (if the content language is not the default language)
        if ($this->tsfe->sys_language_content) {
            $OLmode = ($this->sys_language_mode == 'strict' ? 'hideNonTranslated' : '');
            $row = $this->tsfe->sys_page->getRecordOverlay('tt_news', $row, $this->tsfe->sys_language_content, $OLmode);
        }

        // Adds hook for processing of extra item array
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraItemArrayHook'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraItemArrayHook'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $row = $_procObj->extraItemArrayProcessor($row, $lConf, $this);
            }
        }

        // Register displayed news item globally:
        $GLOBALS['T3_VAR']['displayedNews'][] = $row['uid'];
        $markerArray = array();

        if (is_array($row) && ($row['pid'] > 0 || $this->vPrev)) { // never display versions of a news record (having pid=-1) for normal website users

            $this->upstreamVars['mode'] = 'display';

            // If type is 1 or 2 (internal/external link), redirect to accordant page:
            if (is_array($row) && GeneralUtility::inList('1,2', $row['type'])) {
                $redirectUrl = $this->local_cObj->getTypoLink_URL(
                    $row['type'] == 1 ? $row['page'] : $row['ext_url']
                );
                HttpUtility::redirect($redirectUrl);
            }
            $item = false;
            // reset marker array
            $wrappedSubpartArray = array();

            if (!$this->useUpstreamRenderer) {
                // Get the subpart code
                if ($this->conf['displayCurrentRecord']) {
                    $item = trim($this->getNewsSubpart($this->templateCode,
                        $this->spMarker('###TEMPLATE_SINGLE_RECORDINSERT###'), $row));
                }

                if (!$item) {
                    $item = $this->getNewsSubpart($this->templateCode,
                        $this->spMarker('###TEMPLATE_' . $this->theCode . '###'), $row);
                }

                $this->renderMarkers = $this->getMarkers($item);

                // build the backToList link
                if ($this->conf['useHRDates']) {
                    $wrappedSubpartArray['###LINK_ITEM###'] = explode('|', $this->pi_linkTP_keepPIvars('|', array(
                        'tt_news' => null,
                        'backPid' => null,
                        $this->config['singleViewPointerName'] => null,
                        'pS' => null,
                        'pL' => null
                    ), $this->allowCaching, ($this->conf['dontUseBackPid'] ? 1 : 0), $this->config['backPid']));
                } else {
                    $wrappedSubpartArray['###LINK_ITEM###'] = explode('|', $this->pi_linkTP_keepPIvars('|', array(
                        'tt_news' => null,
                        'backPid' => null,
                        $this->config['singleViewPointerName'] => null
                    ), $this->allowCaching, ($this->conf['dontUseBackPid'] ? 1 : 0), $this->config['backPid']));
                }
            }

            // set the title of the single view page to the title of the news record
            if ($this->conf['substitutePagetitle']) {


                /**
                 * TODO: 05.05.2009
                 * pagetitle stdWrap
                 */

                $this->tsfe->page['title'] = $row['title'];
                // set pagetitle for indexed search to news title
                $this->tsfe->indexedDocTitle = $row['title'];
            }
            if ($lConf['catOrderBy']) {
                $this->config['catOrderBy'] = $lConf['catOrderBy'];
            }
            $this->categories = array();
            $this->categories[$row['uid']] = $this->getCategories($row['uid']);

            $markerArray = $this->getItemMarkerArray($row, $lConf, 'displaySingle');
            if (!$this->useUpstreamRenderer) {
                // Substitute
                $content = $this->markerBasedTemplateService->substituteMarkerArrayCached($item, $markerArray, array(),
                    $wrappedSubpartArray);
            }

        } elseif ($this->sys_language_mode == 'strict' && $this->tt_news_uid && $this->tsfe->sys_language_content) {
            // not existing translation
            if ($this->conf['redirectNoTranslToList']) {
                // redirect to list page
                $this->pi_linkToPage(' ', $this->conf['backPid']);
                HttpUtility::redirect($this->cObj->lastTypoLinkUrl);
            }

            $this->upstreamVars['mode'] = 'noTranslation';
            $noTranslMsg = $this->local_cObj->stdWrap($this->pi_getLL('noTranslMsg'),
                $this->conf['noNewsIdMsg_stdWrap.']);
            $content = $noTranslMsg;
        } elseif ($row['pid'] < 0) {
            // a non-public version of a record was requested
            $this->upstreamVars['mode'] = 'nonPlublicVersion';
            $nonPlublicVersion = $this->local_cObj->stdWrap($this->pi_getLL('nonPlublicVersionMsg'),
                $this->conf['nonPlublicVersionMsg_stdWrap.']);
            $content = $nonPlublicVersion;
        } else {
            // if singleview is shown with no tt_news uid given from GETvars (&tx_ttnews[tt_news]=) an error message is displayed.
            $this->upstreamVars['mode'] = 'noNewsId';
            $noNewsIdMsg = $this->local_cObj->stdWrap($this->pi_getLL('noNewsIdMsg'),
                $this->conf['noNewsIdMsg_stdWrap.']);
            $content = $noNewsIdMsg;
        }

        if ($this->conf['upstreamRendererFunc']) {
            $content = $this->userProcess('upstreamRendererFunc',
                array('row' => $row, 'markerArray' => $markerArray, 'content' => $content));
        }

        return $content;
    }


    /**
     * generates the News archive menu
     *
     * @return    string        html code of the archive menu
     * @throws DBALException
     */
    public function displayArchiveMenu()
    {
        $this->arcExclusive = 1;
        $selectConf = $this->getSelectConf('', 1);
        $selectConf['where'] .= $this->enableFields;

        // Finding maximum and minimum values:
        $row = $this->getArchiveMenuRange($selectConf);

        if ($row['minval'] || $row['maxval']) {
            $dateArr = array();
            $arcMode = $this->config['archiveMode'];
            $c = 0;
            $theDate = 0;
            while ($theDate < $row['maxval']) {
                switch ($arcMode) {
                    case 'month' :
                        $theDate = mktime(0, 0, 0, date('m', $row['minval']) + $c, 1, date('Y', $row['minval']));
                        break;
                    case 'quarter' :
                        $theDate = mktime(0, 0, 0, floor(date('m', $row['minval']) / 3) + 1 + (3 * $c), 1,
                            date('Y', $row['minval']));
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
                $storeKey = md5(serialize(array(
                    $this->catExclusive,
                    $this->config['catSelection'],
                    $this->tsfe->sys_language_content,
                    $selectConf['pidInList'],
                    $arcMode
                )));
                $cachedPeriodAccum = $this->cache->get($storeKey);
            }

            if (!empty($cachedPeriodAccum)) {
                 $periodAccum = $cachedPeriodAccum;
            } else {

                $periodAccum = array();
                foreach ($dateArr as $k => $v) {
                    $periodInfo = array();
                    $periodInfo['start'] = $v;
                    $periodInfo['active'] = ($this->piVars['pS'] == $v ? 1 : 0);
                    $periodInfo['stop'] = $dateArr[$k + 1] - 1;
                    $periodInfo['HRstart'] = date('d-m-Y', $periodInfo['start']);
                    $periodInfo['HRstop'] = date('d-m-Y', $periodInfo['stop']);
                    $periodInfo['quarter'] = floor(date('m', $v) / 3) + 1;

                    $select_fields = 'COUNT(DISTINCT tt_news.uid)';
                    $from_table = 'tt_news';
                    $join = ($selectConf['leftjoin'] ? ' LEFT JOIN ' . $selectConf['leftjoin'] : '');
                    $where_clause = $tmpWhere . ' AND tt_news.datetime>=' . $periodInfo['start'] . ' AND tt_news.datetime<' . $periodInfo['stop'];

                    $res = $this->db->exec_SELECTquery($select_fields, $from_table . $join, $where_clause);

                    $row = $this->db->sql_fetch_row($res);

                    $periodInfo['count'] = $row[0];
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
            $archiveLink = ($archiveLink ? $archiveLink : $this->tsfe->id);

            $this->conf['parent.']['addParams'] = $this->conf['archiveTypoLink.']['addParams'];
            $amenuLinkCat = null;

            if (!$this->conf['disableCategoriesInAmenuLinks']) {
                if ($this->piVars_catSelection && $this->config['amenuWithCatSelector']) {
                    // use the catSelection from piVars only if 'amenuWithCatSelector' is given.
                    $amenuLinkCat = $this->piVars_catSelection;
                } else {
                    $amenuLinkCat = $this->actuallySelectedCategories;
                }
            }

            $itemsOutArr = array();
            $oldyear = 0;
            $itemsOut = '';
            foreach ($periodAccum as $pArr) {
                $wrappedSubpartArray = array();
                $markerArray = array();

                $year = date('Y', $pArr['start']);
                if ($this->conf['useHRDates']) {
                    $month = date('m', $pArr['start']);
                    if ($arcMode == 'year') {
                        $archLinkArr = $this->pi_linkTP_keepPIvars('|', array('cat' => $amenuLinkCat, 'year' => $year),
                            $this->allowCaching, 1, $archiveLink);
                    } else {
                        $archLinkArr = $this->pi_linkTP_keepPIvars('|',
                            array('cat' => $amenuLinkCat, 'year' => $year, 'month' => $month), $this->allowCaching, 1,
                            $archiveLink);
                    }
                    $wrappedSubpartArray['###LINK_ITEM###'] = explode('|', $archLinkArr);
                } else {
                    $wrappedSubpartArray['###LINK_ITEM###'] = explode('|', $this->pi_linkTP_keepPIvars('|', array(
                        'cat' => $amenuLinkCat,
                        'pS' => $pArr['start'],
                        'pL' => ($pArr['stop'] - $pArr['start']),
                        'arc' => 1
                    ), $this->allowCaching, 1, $archiveLink));
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
                    $markerArray['###ARCHIVE_YEAR###'] = $veryLocal_cObj->stdWrap($yearTitle,
                        $this->conf['archiveYear_stdWrap.']);
                }

                $markerArray['###ARCHIVE_TITLE###'] = $veryLocal_cObj->cObjGetSingle($this->conf['archiveTitleCObject'],
                    $this->conf['archiveTitleCObject.'], 'archiveTitleCObject');
                $markerArray['###ARCHIVE_COUNT###'] = $pArr['count'];
                $markerArray['###ARCHIVE_ITEMS###'] = ($pArr['count'] == 1 ? $this->pi_getLL('archiveItem') : $this->pi_getLL('archiveItems'));
                $markerArray['###ARCHIVE_ACTIVE###'] = ($this->piVars['pS'] == $pArr['start'] ? $this->conf['archiveActiveMarkerContent'] : '');

                $layoutNum = ($tCount == 0 ? 0 : ($cc % $tCount));
                $amenuitem = $this->markerBasedTemplateService->substituteMarkerArrayCached($t['item'][$layoutNum],
                    $markerArray, array(),
                    $wrappedSubpartArray);

                if ($this->conf['newsAmenuUserFunc']) {
                    // fill the generated data to an array to pass it to a userfuction as a single variable
                    $itemsOutArr[] = array('html' => $amenuitem, 'data' => $pArr);
                } else {
                    $itemsOut .= $amenuitem;
                }
                $cc++;
            }

            // Pass to user defined function
            if ($this->conf['newsAmenuUserFunc']) {
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
            $subpartArray = array();
            $wrappedSubpartArray = array();
            $markerArray = array();
            $markerArray['###ARCHIVE_HEADER###'] = $this->local_cObj->stdWrap($this->pi_getLL('archiveHeader'),
                $this->conf['archiveHeader_stdWrap.']);
            // Set content
            $subpartArray['###CONTENT###'] = $itemsOut;
            $content = $this->markerBasedTemplateService->substituteMarkerArrayCached($t['total'], $markerArray,
                $subpartArray,
                $wrappedSubpartArray);
        } else {
            // if nothing is found in the archive display the TEMPLATE_ARCHIVE_NOITEMS message
            $markerArray['###ARCHIVE_HEADER###'] = $this->local_cObj->stdWrap($this->pi_getLL('archiveHeader'),
                $this->conf['archiveHeader_stdWrap.']);
            $markerArray['###ARCHIVE_EMPTY_MSG###'] = $this->local_cObj->stdWrap($this->pi_getLL('archiveEmptyMsg'),
                $this->conf['archiveEmptyMsg_stdWrap.']);
            $noItemsMsg = $this->getNewsSubpart($this->templateCode, $this->spMarker('###TEMPLATE_ARCHIVE_NOITEMS###'));
            $content = $this->markerBasedTemplateService->substituteMarkerArrayCached($noItemsMsg, $markerArray);
        }


        return $content;
    }


    /**
     * Displays a hirarchical menu from tt_news categories
     *
     * @return    string        html for the category menu
     * @throws DBALException
     */
    public function displayCatMenu()
    {
        $content = '';
        $lConf = $this->conf['displayCatMenu.'];
        $mode = $lConf['mode'] ? $lConf['mode'] : 'tree';
        $this->dontStartFromRootRecord = false;

        $this->initCatmenuEnv($lConf);

        switch ($mode) {
            case 'nestedWraps' :
                $fields = '*';
                $lConf = $this->conf['displayCatMenu.'];
                $addCatlistWhere = '';
                if ($this->dontStartFromRootRecord) {
                    $addCatlistWhere = 'tt_news_cat.uid IN (' . implode(',', $this->cleanedCategoryMounts) . ')';
                }
                $res = $this->db->exec_SELECTquery($fields, 'tt_news_cat',
                    ($this->dontStartFromRootRecord ? $addCatlistWhere : 'tt_news_cat.parent_category=0') . $this->SPaddWhere . $this->enableCatFields . $this->catlistWhere,
                    '', 'tt_news_cat.' . $this->config['catOrderBy']);

                $cArr = array();
                if (!$lConf['hideCatmenuHeader']) {
                    $cArr[] = $this->local_cObj->stdWrap($this->pi_getLL('catmenuHeader', 'Select a category:'),
                        $lConf['catmenuHeader_stdWrap.']);
                }
                while (($row = $this->db->sql_fetch_assoc($res))) {
                    $cArr[] = $row;
                    $subcats = $this->helpers->getSubCategoriesForMenu($row['uid'], $fields, $this->catlistWhere);
                    if (count($subcats)) {
                        $cArr[] = $subcats;
                    }
                }
                $content = $this->getCatMenuContent($cArr, $lConf);
                break;
            case 'tree' :
            case 'ajaxtree' :
                /** @var Catmenu $catTreeObj */
                $catTreeObj = GeneralUtility::makeInstance(Catmenu::class);
                if ($mode == 'ajaxtree') {
                    $this->getPageRenderer()->addJsFooterFile('/typo3conf/ext/tt_news/Resources/Public/JavaScript/NewsCatmenu.js');
                }
                $catTreeObj->init($this);
                $catTreeObj->treeObj->FE_USER = &$this->tsfe->fe_user;

                $content = '<div id="ttnews-cat-tree">' . $catTreeObj->treeObj->getBrowsableTree() . '</div>';
                break;
            default :
                // hook for user catmenu
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['userDisplayCatmenuHook'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['userDisplayCatmenuHook'] as $_classRef) {
                        $_procObj = GeneralUtility::makeInstance($_classRef);
                        $content .= $_procObj->userDisplayCatmenu($lConf, $this);
                    }
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
     * @throws DBALException
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
        if ($this->isRenderMarker('###NEWS_LANGUAGE###')) {
            if ($this->conf['showLangLabels']) {
                $markerArray['###NEWS_LANGUAGE###'] = $this->langArr[$row['sys_language_uid']]['title'];
            }

            if ($this->langArr[$row['sys_language_uid']]['flag'] && $this->conf['showFlags']) {
                $fImgFile = ($this->conf['flagPath'] ? $this->conf['flagPath'] : 'media/flags/flag_') . $this->langArr[$row['sys_language_uid']]['flag'];
                $fImgConf = $this->conf['flagImage.'];
                $fImgConf['file'] = $fImgFile;
                $flagImg = $this->local_cObj->cObjGetSingle('IMAGE', $fImgConf);
                $markerArray['###NEWS_LANGUAGE###'] .= $flagImg;
            }
        }

        if ($row['title'] && $this->isRenderMarker('###NEWS_TITLE###')) {
            $markerArray['###NEWS_TITLE###'] = $this->local_cObj->stdWrap($row['title'], $lConf['title_stdWrap.']);
        }
        if ($row['author'] && $this->isRenderMarker('###NEWS_AUTHOR###')) {
            $newsAuthor = $this->local_cObj->stdWrap($row['author'] ? $this->local_cObj->stdWrap($this->pi_getLL('preAuthor'),
                    $lConf['preAuthor_stdWrap.']) . $row['author'] : '', $lConf['author_stdWrap.']);
            $markerArray['###NEWS_AUTHOR###'] = $this->formatStr($newsAuthor);
        }
        if ($row['author_email'] && $this->isRenderMarker('###NEWS_EMAIL###')) {
            $markerArray['###NEWS_EMAIL###'] = $this->local_cObj->stdWrap($row['author_email'],
                $lConf['email_stdWrap.']);
        }

        if ($row['datetime']) {
            if ($this->isRenderMarker('###NEWS_DATE###')) {
                $markerArray['###NEWS_DATE###'] = $this->local_cObj->stdWrap($row['datetime'], $lConf['date_stdWrap.']);
            }
            if ($this->isRenderMarker('###NEWS_TIME###')) {
                $markerArray['###NEWS_TIME###'] = $this->local_cObj->stdWrap($row['datetime'], $lConf['time_stdWrap.']);
            }
            if ($this->isRenderMarker('###NEWS_AGE###')) {
                $markerArray['###NEWS_AGE###'] = $this->local_cObj->stdWrap($row['datetime'], $lConf['age_stdWrap.']);
            }
            if ($this->isRenderMarker('###TEXT_NEWS_AGE###')) {
                $markerArray['###TEXT_NEWS_AGE###'] = $this->local_cObj->stdWrap($this->pi_getLL('textNewsAge'),
                    $lConf['textNewsAge_stdWrap.']);
            }
        }

        if ($this->isRenderMarker('###NEWS_SUBHEADER###') && (!$this->piVars[$this->config['singleViewPointerName']] || $this->conf['subheaderOnAllSViewPages'])) {
            $markerArray['###NEWS_SUBHEADER###'] = $this->formatStr($this->local_cObj->stdWrap($row['short'],
                $lConf['subheader_stdWrap.']));
        }
        if ($row['keywords'] && $this->isRenderMarker('###NEWS_KEYWORDS###')) {
            $markerArray['###NEWS_KEYWORDS###'] = $this->local_cObj->stdWrap($row['keywords'],
                $lConf['keywords_stdWrap.']);
        }

        if (!$this->piVars[$this->config['singleViewPointerName']] && $textRenderObj == 'displaySingle') {
            // load the keywords in the register 'newsKeywords' to access it from TS
            $this->local_cObj->cObjGetSingle('LOAD_REGISTER',
                array('newsKeywords' => $row['keywords'], 'newsSubheader' => $row['short']));
        }

        $sViewPagebrowser = false;
        $newscontent = false;
        if ($this->isRenderMarker('###NEWS_CONTENT###')) {
            if ($textRenderObj == 'displaySingle' && !$row['no_auto_pb'] && $this->config['maxWordsInSingleView'] > 1 && $this->config['useMultiPageSingleView']) {
                $row['bodytext'] = $this->helpers->insertPagebreaks($row['bodytext'],
                    count(GeneralUtility::trimExplode(' ', $row['short'], 1)));
            }

            if (strpos($row['bodytext'], $this->config['pageBreakToken'])) {
                if ($this->config['useMultiPageSingleView'] && $textRenderObj == 'displaySingle') {
                    $tmp = $this->helpers->makeMultiPageSView($row['bodytext'], $lConf);
                    $newscontent = $tmp[0];
                    $sViewPagebrowser = $tmp[1];
                } else {
                    $newscontent = $this->formatStr($this->local_cObj->stdWrap(preg_replace('/' . $this->config['pageBreakToken'] . '/',
                        '', $row['bodytext']), $lConf['content_stdWrap.']));
                }
            } else {
                $newscontent = $this->formatStr($this->local_cObj->stdWrap($row['bodytext'],
                    $lConf['content_stdWrap.']));
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

            $markerArray['###BACK_TO_LIST###'] = sprintf($this->pi_getLL('backToList', ''),
                $backPtitle);
        }

        // get related news
        $relatedNews = false;
        if ($this->isRenderMarker('###TEXT_RELATED###') || $this->isRenderMarker('###NEWS_RELATED###')) {

            if ($this->conf['renderRelatedNewsAsList']) {
                $relatedNews = $this->getRelatedNewsAsList($row['uid']);
            } else {
                $relatedNews = $this->getRelated($row['uid']);
            }


            if ($relatedNews) {
                $rel_stdWrap = GeneralUtility::trimExplode('|',
                    $this->conf['related_stdWrap.']['wrap']);
                $markerArray['###TEXT_RELATED###'] = $rel_stdWrap[0] . $this->local_cObj->stdWrap($this->pi_getLL('textRelated'),
                        $this->conf['relatedHeader_stdWrap.']);
                $markerArray['###NEWS_RELATED###'] = $relatedNews . $rel_stdWrap[1];
            }
        }


        // Links
        $newsLinks = false;
        $links = trim($row['links']);
        if ($links && ($this->isRenderMarker('###TEXT_LINKS###') || $this->isRenderMarker('###NEWS_LINKS###'))) {
            $links_stdWrap = GeneralUtility::trimExplode('|', $lConf['links_stdWrap.']['wrap']);
            $newsLinks = $this->local_cObj->stdWrap($this->formatStr($row['links']), $lConf['linksItem_stdWrap.']);
            $markerArray['###TEXT_LINKS###'] = $links_stdWrap[0] . $this->local_cObj->stdWrap($this->pi_getLL('textLinks'),
                    $lConf['linksHeader_stdWrap.']);
            $markerArray['###NEWS_LINKS###'] = $newsLinks . $links_stdWrap[1];
        }
        // filelinks
        if ($row['news_files'] && ($this->isRenderMarker('###TEXT_FILES###') || $this->isRenderMarker('###FILE_LINK###') || $this->theCode == 'XML')) {
            $this->getFileLinks($markerArray, $row);
        }

        // show news with the same categories in SINGLE view
        if ($textRenderObj == 'displaySingle' && $this->conf['showRelatedNewsByCategory'] && count($this->categories[$row['uid']])
            && ($this->isRenderMarker('###NEWS_RELATEDBYCATEGORY###') || $this->isRenderMarker('###TEXT_RELATEDBYCATEGORY###'))) {
            $this->getRelatedNewsByCategory($markerArray, $row);

        }


        // the markers: ###ADDINFO_WRAP_B### and ###ADDINFO_WRAP_E### are only inserted, if there are any files, related news or links
        if ($relatedNews || $newsLinks || $markerArray['###FILE_LINK###'] || $markerArray['###NEWS_RELATEDBYCATEGORY###']) {
            $addInfo_stdWrap = GeneralUtility::trimExplode('|',
                $lConf['addInfo_stdWrap.']['wrap']);
            $markerArray['###ADDINFO_WRAP_B###'] = $addInfo_stdWrap[0];
            $markerArray['###ADDINFO_WRAP_E###'] = $addInfo_stdWrap[1];
        }

        // Page fields:
        if ($this->isRenderMarker('###PAGE_UID###')) {
            $markerArray['###PAGE_UID###'] = $row['pid'];
        }

        if ($this->isRenderMarker('###PAGE_TITLE###')) {
            $markerArray['###PAGE_TITLE###'] = $this->getPageArrayEntry($row['pid'], 'title');
        }
        if ($this->isRenderMarker('###PAGE_AUTHOR###')) {
            $markerArray['###PAGE_AUTHOR###'] = $this->local_cObj->stdWrap($this->getPageArrayEntry($row['pid'],
                'author'), $lConf['author_stdWrap.']);
        }
        if ($this->isRenderMarker('###PAGE_AUTHOR_EMAIL###')) {
            $markerArray['###PAGE_AUTHOR_EMAIL###'] = $this->local_cObj->stdWrap($this->getPageArrayEntry($row['pid'],
                'author_email'), $lConf['email_stdWrap.']);
        }

        // XML
        if ($this->theCode == 'XML') {
            $this->getXmlMarkers($markerArray, $row, $lConf);
        }

        $this->getGenericMarkers($markerArray);
        //		debug($markerArray, ' ('.__CLASS__.'::'.__FUNCTION__.')', __LINE__, __FILE__, 3);

        // Adds hook for processing of extra item markers
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraItemMarkerHook'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraItemMarkerHook'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $markerArray = $_procObj->extraItemMarkerProcessor($markerArray, $row, $lConf, $this);
            }
        }
        // Pass to userdefined function
        if ($this->conf['itemMarkerArrayFunc']) {
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
        $markerArray['###NEWS_TITLE###'] = $this->helpers->cleanXML($this->local_cObj->stdWrap($row['title'],
            $lConf['title_stdWrap.']));
        $markerArray['###NEWS_AUTHOR###'] = $row['author_email'] ? '<author>' . $row['author_email'] . '</author>' : '';
        if ($this->conf['displayXML.']['xmlFormat'] == 'atom03' || $this->conf['displayXML.']['xmlFormat'] == 'atom1') {
            $markerArray['###NEWS_AUTHOR###'] = $row['author'];
        }

        if ($this->conf['displayXML.']['xmlFormat'] == 'rss2' || $this->conf['displayXML.']['xmlFormat'] == 'rss091') {
            $markerArray['###NEWS_SUBHEADER###'] = $this->helpers->cleanXML($this->local_cObj->stdWrap($row['short'],
                $lConf['subheader_stdWrap.']));
        } elseif ($this->conf['displayXML.']['xmlFormat'] == 'atom03' || $this->conf['displayXML.']['xmlFormat'] == 'atom1') {
            //html doesn't need to be striped off in atom feeds
            $lConf['subheader_stdWrap.']['stripHtml'] = 0;
            $markerArray['###NEWS_SUBHEADER###'] = $this->local_cObj->stdWrap($row['short'],
                $lConf['subheader_stdWrap.']);
            //just removing some whitespace to ease atom feed building
            $markerArray['###NEWS_SUBHEADER###'] = str_replace('\n', '', $markerArray['###NEWS_SUBHEADER###']);
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

        $markerArray['###NEWS_ATOM_ENTRY_ID###'] = 'tag:' . substr($this->config['siteUrl'], 11, -1) . ',' . date('Y',
                $row['crdate']) . ':article' . $row['uid'];
        $markerArray['###SITE_LINK###'] = $this->config['siteUrl'];
    }


    /**
     * @param $markerArray
     * @param $row
     */
    protected function getFileLinks(&$markerArray, $row)
    {
        $files_stdWrap = GeneralUtility::trimExplode('|',
            $this->conf['newsFiles_stdWrap.']['wrap']);
        $markerArray['###TEXT_FILES###'] = $files_stdWrap[0] . $this->local_cObj->stdWrap($this->pi_getLL('textFiles'),
                $this->conf['newsFilesHeader_stdWrap.']);

        $filesPath = trim($this->conf['newsFiles.']['path']);

        if (MathUtility::canBeInterpretedAsInteger($row['news_files'])) {
            // seems that tt_news files have been migrated to FAL
            $filesPath = '';
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            $fileObjects = $fileRepository->findByRelation('tt_news', 'news_files', $row['uid']);
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

        $fileArr = explode(',', $row['news_files']);
        $filelinks = '';
        $rss2Enclousres = '';
        foreach ($fileArr as $val) {
            // fills the marker ###FILE_LINK### with the links to the atached files
            $fileName = ($falFilesTitles[$val] != '' ? $falFilesTitles[$val] : basename($val));
            $filelinks .= $this->local_cObj->stdWrap(
                $this->local_cObj->typoLink($fileName,['parameter'=> $filesPath . $val]),
                $this->conf['newsFiles.']['stdWrap.']);

            // <enclosure> support for RSS 2.0
            if ($this->theCode == 'XML') {
                $theFile = $filesPath . $val;

                if (@is_file($theFile)) {
                    $fileURL = $this->config['siteUrl'] . $theFile;
                    $fileSize = filesize($theFile);
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
     * @throws DBALException
     */
    protected function getRelatedNewsByCategory(&$markerArray, $row)
    {
        // save some variables which are used to build the backLink to the list view
        $tmpcatExclusive = $this->catExclusive;
        $tmparcExclusive = $this->arcExclusive;
        $tmpcode = $this->theCode;
        $tmpBrowsePage = intval($this->piVars['pointer']);
        unset($this->piVars['pointer']);
        $tmpPS = intval($this->piVars['pS']);
        unset($this->piVars['pS']);
        $tmpPL = intval($this->piVars['pL']);
        unset($this->piVars['pL']);

        $confSave = $this->conf;
        $configSave = $this->config;
        $tmp_renderMarkers = $this->renderMarkers;
        $local_cObjSave = clone $this->local_cObj;

        ArrayUtility::mergeRecursiveWithOverrule($this->conf,
            $this->conf['relNewsByCategory.'] ? $this->conf['relNewsByCategory.'] : array());
        $this->config = $this->conf;
        $this->config['catOrderBy'] = $configSave['catOrderBy'];

        $this->arcExclusive = $this->conf['archive'];
        $this->LOCAL_LANG_loaded = false;
        $this->pi_loadLL(); // Loading language-labels


        if ($this->conf['code']) {
            $this->theCode = strtoupper($this->conf['code']);
        }

        if (is_array($this->categories[$row['uid']])) {
            $this->catExclusive = implode(',', array_keys($this->categories[$row['uid']]));
        }

        $relNewsByCat = trim($this->displayList($row['uid']));

        if ($relNewsByCat) {
            $cat_rel_stdWrap = GeneralUtility::trimExplode('|',
                $this->conf['relatedByCategory_stdWrap.']['wrap']);
            $lbl = $this->pi_getLL('textRelatedByCategory');
            $markerArray['###TEXT_RELATEDBYCATEGORY###'] = $cat_rel_stdWrap[0] . $this->local_cObj->stdWrap($lbl,
                    $this->conf['relatedByCategoryHeader_stdWrap.']);
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
        } else {
            foreach ($lConf as $mName => $renderObj) {
                $genericMarker = '###GENERIC_' . strtoupper($mName) . '###';

                if (!is_array($lConf[$mName . '.']) || !$this->isRenderMarker($genericMarker)) {
                    continue;
                }

                $markerArray[$genericMarker] = $this->local_cObj->cObjGetSingle($renderObj, $lConf[$mName . '.'],
                    'tt_news generic marker: ' . $mName);
            }
        }
    }

    /**
     *
     */
    protected function initGenericMarkers()
    {
        if (is_array($this->conf['genericmarkers.'])) {
            $this->genericMarkerConf = $this->conf['genericmarkers.'];

            // merge with special configuration (based on current CODE [SINGLE, LIST, LATEST]) if this is available
            if (is_array($this->genericMarkerConf[$this->theCode . '.'])) {
                ArrayUtility::mergeRecursiveWithOverrule($this->genericMarkerConf,
                    $this->genericMarkerConf[$this->theCode . '.']);
            }
        }
    }

    /**
     * @param $row
     * @param $lConf
     * @param $markerArray
     *
     * @return mixed
     * @throws DBALException
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

        $fN = ($lConf['nextPrevRecSortingField'] ? $lConf['nextPrevRecSortingField'] : 'datetime');
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
        $pTmp = $this->tsfe->ATagParams;
        $this->tsfe->ATagParams = $pTmp . ' title="' . $this->local_cObj->stdWrap(trim(htmlspecialchars($title)),
                $lConf[$p . 'LinkTitle_stdWrap.']) . '"';
        $link = $this->getSingleViewLink($this->tsfe->id, $rec, array());

        if ($lConf['showTitleAsPrevNextLink']) {
            $lbl = $title;
        } else {
            $lbl = $this->pi_getLL($p . 'Article');
        }

        $lbl = $this->local_cObj->stdWrap($lbl, $lConf[$p . 'LinkLabel_stdWrap.']);

        $this->tsfe->ATagParams = $pTmp;

        return $this->local_cObj->stdWrap($link[0] . $lbl . $link[1], $lConf[$p . 'Link_stdWrap.']);
    }


    /**
     * @param        $getPrev
     * @param array  $selectConf
     * @param string $fN
     * @param mixed  $fV
     *
     * @return mixed
     * @throws DBALException
     */
    protected function getPrevNextRec($getPrev, $selectConf, $fN, $fV)
    {

        $row = $this->db->exec_SELECTgetSingleRow(
            'tt_news.uid, tt_news.title, tt_news.' . $fN . ($fN == 'datetime' ? '' : ', tt_news.datetime'),
            'tt_news' . ($selectConf['leftjoin'] ? ' LEFT JOIN ' . $selectConf['leftjoin'] : ''),
            $selectConf['where'] . ' AND tt_news.' . $fN . ($getPrev ? '<' : '>') . '"' . $fV . '"',
            '',
            'tt_news.' . $fN . ($getPrev ? ' DESC' : ' ASC'));

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

        $pTmp = $this->tsfe->ATagParams;
        if (count($this->categories[$row['uid']]) && ($this->config['catImageMode'] || $this->config['catTextMode'])) {
            // wrap for all categories
            $cat_stdWrap = GeneralUtility::trimExplode('|',
                $lConf['category_stdWrap.']['wrap']);
            $markerArray['###CATWRAP_B###'] = $cat_stdWrap[0];
            $markerArray['###CATWRAP_E###'] = $cat_stdWrap[1];
            $markerArray['###TEXT_CAT###'] = $this->pi_getLL('textCat');
            $markerArray['###TEXT_CAT_LATEST###'] = $this->pi_getLL('textCatLatest');

            $news_category = array();
            $theCatImgCodeArray = array();
            $catTextLenght = 0;
            $wroteRegister = false;

            $catSelLinkParams = ($this->conf['catSelectorTargetPid'] ? ($this->conf['itemLinkTarget'] ? $this->conf['catSelectorTargetPid'] . ' ' . $this->conf['itemLinkTarget'] : $this->conf['catSelectorTargetPid']) : $this->tsfe->id);

            foreach ($this->categories[$row['uid']] as $val) {

                // find categories, wrap them with links and collect them in the array $news_category.
                $catTitle = htmlspecialchars($val['title']);
                $this->tsfe->ATagParams = $pTmp . ' title="' . $catTitle . '"';
                $titleWrap = ($val['parent_category'] > 0 ? 'subCategoryTitleItem_stdWrap.' : 'categoryTitleItem_stdWrap.');
                if ($this->config['catTextMode'] == 0) {
                    $markerArray['###NEWS_CATEGORY###'] = '';
                } elseif ($this->config['catTextMode'] == 1) {
                    // display but don't link
                    $news_category[] = $this->local_cObj->stdWrap($catTitle, $lConf[$titleWrap]);
                } elseif ($this->config['catTextMode'] == 2) {
                    // link to category shortcut
                    $news_category[] = $this->local_cObj->stdWrap($this->pi_linkToPage($catTitle, $val['shortcut'],
                        $val['shortcut_target']), $lConf[$titleWrap]);
                } elseif ($this->config['catTextMode'] == 3) {
                    // act as category selector


                    if ($this->conf['useHRDates']) {
                        $news_category[] = $this->local_cObj->stdWrap($this->pi_linkTP_keepPIvars($catTitle, array(
                            'cat' => $val['catid'],
                            'year' => ($this->piVars['year'] ? $this->piVars['year'] : null),
                            'month' => ($this->piVars['month'] ? $this->piVars['month'] : null),
                            'backPid' => null,
                            $this->pointerName => null
                        ), $this->allowCaching, 0, $catSelLinkParams), $lConf[$titleWrap]);

                    } else {
                        $news_category[] = $this->local_cObj->stdWrap($this->pi_linkTP_keepPIvars($catTitle, array(
                            'cat' => $val['catid'],
                            'backPid' => null,
                            $this->pointerName => null
                        ), $this->allowCaching, 0, $catSelLinkParams), $lConf[$titleWrap]);
                    }
                }

                $catTextLenght += strlen($catTitle);
                if ($this->config['catImageMode'] == 0 || empty($val['image'])) {
                    $markerArray['###NEWS_CATEGORY_IMAGE###'] = '';
                } else {
                    $catPicConf = array();
                    $catPicConf['image.']['file'] = 'uploads/pics/' . $val['image'];
                    $catPicConf['image.']['file.']['maxW'] = intval($this->config['catImageMaxWidth']);
                    $catPicConf['image.']['file.']['maxH'] = intval($this->config['catImageMaxHeight']);
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
                            $catPicConf['image.']['stdWrap.']['innerWrap'] = $this->pi_linkToPage('|', $val['shortcut'],
                                $this->conf['itemLinkTarget']);
                        }

                        if ($this->config['catImageMode'] == 3) {
                            // act as category selector
                            $catPicConf['image.']['altText'] = $this->pi_getLL('altTextCatSelector') . $catTitle;
                            if ($this->conf['useHRDates']) {
                                $catPicConf['image.']['stdWrap.']['innerWrap'] = $this->pi_linkTP_keepPIvars('|', array(
                                    'cat' => $val['catid'],
                                    'year' => ($this->piVars['year'] ? $this->piVars['year'] : null),
                                    'month' => ($this->piVars['month'] ? $this->piVars['month'] : null),
                                    'backPid' => null,
                                    $this->pointerName => null
                                ), $this->allowCaching, 0, $catSelLinkParams);
                            } else {
                                $catPicConf['image.']['stdWrap.']['innerWrap'] = $this->pi_linkTP_keepPIvars('|', array(
                                    'cat' => $val['catid'],
                                    'backPid' => null,
                                    $this->pointerName => null
                                ), $this->allowCaching, 0, $catSelLinkParams);
                            }
                        }
                    } else {
                        $catPicConf['image.']['altText'] = $val['title'];
                    }

                    // add linked category image to output array
                    $img = $this->local_cObj->cObjGetSingle('IMAGE', $catPicConf['image.']);
                    $swrap = ($val['parent_category'] > 0 ? 'subCategoryImgItem_stdWrap.' : 'categoryImgItem_stdWrap.');
                    $theCatImgCodeArray[] = $this->local_cObj->stdWrap($img, $lConf[$swrap]);
                }
                if (!$wroteRegister) {
                    // Load the uid of the first assigned category to the register 'newsCategoryUid'
                    $this->local_cObj->cObjGetSingle('LOAD_REGISTER', array('newsCategoryUid' => $val['catid']));
                    $wroteRegister = true;
                }
            }
            if ($this->config['catTextMode'] != 0) {
                $categoryDivider = $this->local_cObj->stdWrap($this->conf['categoryDivider'],
                    $this->conf['categoryDivider_stdWrap.']);
                $news_category = implode($categoryDivider,
                    array_slice($news_category, 0, intval($this->config['maxCatTexts'])));
                if ($this->config['catTextLength']) {
                    // crop the complete category titles if 'catTextLength' value is given
                    $markerArray['###NEWS_CATEGORY###'] = (strlen($news_category) < intval($this->config['catTextLength']) ? $news_category : substr($news_category,
                            0, intval($this->config['catTextLength'])) . '...');
                } else {
                    $markerArray['###NEWS_CATEGORY###'] = $this->local_cObj->stdWrap($news_category,
                        $lConf['categoryTitles_stdWrap.']);
                }
            }
            if ($this->config['catImageMode'] != 0) {
                $theCatImgCode = implode('', array_slice($theCatImgCodeArray, 0,
                    intval($this->config['maxCatImages']))); // downsize the image array to the 'maxCatImages' value
                $markerArray['###NEWS_CATEGORY_IMAGE###'] = $this->local_cObj->stdWrap($theCatImgCode,
                    $lConf['categoryImages_stdWrap.']);
            }
            // XML
            if ($this->theCode == 'XML') {
                $newsCategories = explode(', ', $news_category);

                $xmlCategories = '';
                foreach ($newsCategories as $xmlCategory) {
                    $xmlCategories .= '<category>' . $this->local_cObj->stdWrap($xmlCategory,
                            $lConf['categoryTitles_stdWrap.']) . '</category>' . "\n\t\t\t";
                }

                $markerArray['###NEWS_CATEGORY###'] = $xmlCategories;
            }
        }
        $this->tsfe->ATagParams = $pTmp;

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
        if ($this->conf['imageMarkerFunc']) {
            $markerArray = $this->userProcess('imageMarkerFunc', array($markerArray, $lConf));
        } else {
            $imgPath = 'uploads/pics/';
            if (MathUtility::canBeInterpretedAsInteger($row['image'])) {
                // seems that tt_news images have been migrated to FAL
                $imgPath = '';
                $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                $fileObjects = $fileRepository->findByRelation('tt_news', 'image', $row['uid']);
                if (!empty($fileObjects)) {
                    $falImages = [];
                    foreach ($fileObjects as $fileObject) {
                        /** @var FileInterface $fileObject */
                        $falImages[] = $fileObject->getPublicUrl();
                    }
                    if (!empty($falImages)) {
                        $row['image'] = implode(',', $falImages);
                    }
                }
            }

            $imageNum = isset($lConf['imageCount']) ? $lConf['imageCount'] : 1;
            $imageNum = MathUtility::forceIntegerInRange($imageNum, 0, 100);
            $theImgCode = '';
            $imgs = GeneralUtility::trimExplode(',', $row['image'], 1);
            $imgsCaptions = explode(chr(10), $row['imagecaption']);
            $imgsAltTexts = explode(chr(10), $row['imagealttext']);
            $imgsTitleTexts = explode(chr(10), $row['imagetitletext']);

            reset($imgs);

            if ($textRenderObj == 'displaySingle') {
                $markerArray = $this->getSingleViewImages($lConf, $imgs, $imgsCaptions, $imgsAltTexts, $imgsTitleTexts,
                    $imageNum, $markerArray, $imgPath);
            } else {

                $imageMode = $textRenderObj == 'displayLatest' ? $lConf['latestImageMode'] : $lConf['listImageMode'];

                $suf = '';
                if (is_numeric(substr($lConf['image.']['file.']['maxW'], -1)) && $imageMode) {
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
                if ($suf && $lConf['image.']['file.']['maxW'] && !$lConf['image.']['file.']['width']) {
                    $lConf['image.']['file.']['width'] = $lConf['image.']['file.']['maxW'] . $suf;
                    unset($lConf['image.']['file.']['maxW']);
                }
                if ($suf && $lConf['image.']['file.']['maxH'] && !$lConf['image.']['file.']['height']) {
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

                        $theImgCode .= $this->local_cObj->cObjGetSingle('IMAGE',
                                $lConf['image.']) . $this->local_cObj->stdWrap($imgsCaptions[$cc],
                                $lConf['caption_stdWrap.']);
                    }

                    $cc++;
                }

                if ($cc) {
                    $markerArray['###NEWS_IMAGE###'] = $this->local_cObj->wrap($theImgCode, $lConf['imageWrapIfAny']);
                } else {
                    $markerArray['###NEWS_IMAGE###'] = $this->local_cObj->stdWrap($markerArray['###NEWS_IMAGE###'],
                        $lConf['image.']['noImage_stdWrap.']);
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
        $sViewSplitLConf = array();
        $tmpMarkers = array();
        $iC = count($imgs);

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
        if ($this->piVars[$this->config['singleViewPointerName']]) {

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
        if ($this->conf['enableOptionSplit']) {
            if ($lConf['imageMarkerOptionSplit']) {
                $ostmp = explode('|*|', $lConf['imageMarkerOptionSplit']);
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

                $imgHtml = $this->local_cObj->cObjGetSingle('IMAGE',
                        $lConf['image.']) . $this->local_cObj->stdWrap($imgsCaptions[$cc], $lConf['caption_stdWrap.']);

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
            $markerArray['###' . $marker . $m . '###'] = $this->local_cObj->stdWrap($markerArray['###' . $marker . $m . '###'],
                $lConf['image.']['noImage_stdWrap.']);
        }

        return $markerArray;
    }


    /**
     * gets categories and subcategories for a news record
     *
     * @param    integer $uid    : uid of the current news record
     * @param    bool    $getAll : ...
     *
     * @return    array        $categories: array of found categories
     * @throws DBALException
     */
    function getCategories($uid, $getAll = false)
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
                $this->tsfe->sys_language_content,
                $this->conf['useSPidFromCategory'],
                $this->conf['useSPidFromCategoryRecusive'],
                $this->conf['displaySubCategories'],
                $this->config['useSubCategories']
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
            $where_clause = 'tt_news_cat_mm.uid_local=' . intval($uid) . ' AND tt_news_cat_mm.uid_foreign=tt_news_cat.uid';
            $where_clause .= $addWhere;

            $groupBy = '';
            $orderBy = $mmCatOrderBy;
            $limit = '';

            $res = $this->db->exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);

            $categories = array();
            $maincat = 0;

            while (($row = $this->db->sql_fetch_assoc($res))) {

                $maincat .= ',' . $row['uid'];
                $rows = array($row);
                if ($this->conf['displaySubCategories'] && $this->config['useSubCategories']) {
                    $subCategories = array();
                    $subcats = implode(',',
                        array_unique(explode(',', Div::getSubCategories($rows[0]['uid'], $addWhere))));

                    $subres = $this->db->exec_SELECTquery('tt_news_cat.*', 'tt_news_cat',
                        'tt_news_cat.uid IN (' . ($subcats ? $subcats : 0) . ')' . $addWhere, '',
                        'tt_news_cat.' . $this->config['catOrderBy']);

                    while (($subrow = $this->db->sql_fetch_assoc($subres))) {
                        $subCategories[] = $subrow;
                    }
                    $rows = array_merge($rows, $subCategories);
                }

                foreach ($rows as $val) {
                    $parentSP = false;
                    $catTitle = '';
                    if ($this->tsfe->sys_language_content) {
                        // find translations of category titles
                        $catTitleArr = GeneralUtility::trimExplode('|', $val['title_lang_ol']);
                        $catTitle = $catTitleArr[($this->tsfe->sys_language_content - 1)];
                    }
                    $catTitle = $catTitle ? $catTitle : $val['title'];

                    if ($this->conf['useSPidFromCategory'] && $this->conf['useSPidFromCategoryRecusive']) {
                        $parentSP = $this->helpers->getRecursiveCategorySinglePid($val['uid']);
                    }
                    $singlePid = ($parentSP ? $parentSP : $val['single_pid']);

                    $categories[$val['uid']] = array(
                        'title' => $catTitle,
                        'image' => $val['image'],
                        'shortcut' => $val['shortcut'],
                        'shortcut_target' => $val['shortcut_target'],
                        'single_pid' => $singlePid,
                        'catid' => $val['uid'],
                        'parent_category' => (!GeneralUtility::inList($maincat,
                            $val['uid']) && $this->conf['displaySubCategories'] ? $val['parent_category'] : ''),
                        'sorting' => $val['sorting'],
                        'mmsorting' => $val['mmsorting']
                    );

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
     * @throws DBALException
     */
    protected function getCategoryPath($categoryArray)
    {

        $catRootline = '';
        if (is_array($categoryArray)) {
            $pTmp = $this->tsfe->ATagParams;
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
            $theRowArray = array();
            $output = array();
            while ($uid != 0 && $loopCheck > 0) {
                $loopCheck--;
                $res = $this->db->exec_SELECTquery('*', 'tt_news_cat',
                    'uid=' . intval($uid) . $this->SPaddWhere . $this->enableCatFields);

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
                    if ($lConf['linkTitles'] && GeneralUtility::inList('2,3',
                            $this->config['catTextMode'])) {
                        $this->tsfe->ATagParams = ($pTmp ? $pTmp . ' ' : '') . 'title="' . $val['title'] . '"';
                        $output = $this->handleCatTextMode($val, $catSelLinkParams, $lConf, $output);
                    } else {
                        $output[] = $this->local_cObj->stdWrap($val['title'], $lConf['title_stdWrap.']);
                    }
                }
            }

            $catRootline = implode($lConf['divider'], $output);
            if ($catRootline) {
                $catRootline = $this->local_cObj->stdWrap($catRootline, $lConf['catRootline_stdWrap.']);
            }

            $this->tsfe->ATagParams = $pTmp;
        }

        return $catRootline;
    }


    /**
     * This function calls itself recursively to convert the nested category array to HTML
     *
     * @param    array   $array_in : the nested categories
     * @param    array   $lConf    : TS configuration
     * @param    integer $l        : level counter
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
                        $catmenuLevel_stdWrap = explode('|||',
                            $this->local_cObj->stdWrap('|||', $lConf['catmenuLevel' . $l . '_stdWrap.']));
                        $result .= $catmenuLevel_stdWrap[0];
                    } else {
                        $catmenuLevel_stdWrap = '';
                    }
                    if (is_array($array_in[$key])) {
                        $result .= $this->getCatMenuContent($array_in[$key], $lConf, $l + 1);
                    } elseif ($key == $titlefield) {
                        if ($this->tsfe->sys_language_content && $array_in['uid']) {
                            // get translations of category titles
                            $catTitleArr = GeneralUtility::trimExplode('|',
                                $array_in['title_lang_ol']);
                            $syslang = $this->tsfe->sys_language_content - 1;
                            $val = $catTitleArr[$syslang] ? $catTitleArr[$syslang] : $val;
                        }
                        // if (!$title) $title = $val;
                        $catSelLinkParams = ($this->conf['catSelectorTargetPid'] ? ($this->conf['itemLinkTarget'] ? $this->conf['catSelectorTargetPid'] . ' ' . $this->conf['itemLinkTarget'] : $this->conf['catSelectorTargetPid']) : $this->tsfe->id);
                        $pTmp = $this->tsfe->ATagParams;
                        if ($this->conf['displayCatMenu.']['insertDescrAsTitle']) {
                            $this->tsfe->ATagParams = ($pTmp ? $pTmp . ' ' : '') . 'title="' . $array_in['description'] . '"';
                        }
                        if ($array_in['uid']) {
                            if ($this->piVars['cat'] == $array_in['uid']) {
                                $result .= $this->local_cObj->stdWrap($this->pi_linkTP_keepPIvars($val,
                                    array('cat' => $array_in['uid']), $this->allowCaching, 1, $catSelLinkParams),
                                    $lConf['catmenuItem_ACT_stdWrap.']);
                            } else {
                                $result .= $this->local_cObj->stdWrap($this->pi_linkTP_keepPIvars($val,
                                    array('cat' => $array_in['uid']), $this->allowCaching, 1, $catSelLinkParams),
                                    $lConf['catmenuItem_NO_stdWrap.']);
                            }
                        } else {
                            $result .= $this->pi_linkTP_keepPIvars($val, array(), $this->allowCaching, 1,
                                $catSelLinkParams);
                        }
                        $this->tsfe->ATagParams = $pTmp;
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
     * @throws DBALException
     */
    protected function getRelatedNewsAsList($uid)
    {
        // save some variables which are used to build the backLink to the list view
        $tmpcatExclusive = $this->catExclusive;
        $tmparcExclusive = $this->arcExclusive;
        $tmpCategories = $this->categories;
        $tmpcode = $this->theCode;
        $tmpBrowsePage = intval($this->piVars['pointer']);
        unset($this->piVars['pointer']);
        $tmpPS = intval($this->piVars['pS']);
        unset($this->piVars['pS']);
        $tmpPL = intval($this->piVars['pL']);
        unset($this->piVars['pL']);

        $confSave = $this->conf;
        $configSave = $this->config;
        $tmplocal_cObj = clone $this->local_cObj;
        $tmp_renderMarkers = $this->renderMarkers;


        if (!is_array($this->conf['displayRelated.'])) {
            $this->conf['displayRelated.'] = array();
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
     * @param    integer $uid of the current news record
     *
     * @return    string        html code for the related news list
     * @throws DBALException
     */
    protected function getRelated($uid)
    {

        $lConf = $this->conf['getRelatedCObject.'];
        $visibleCategories = '';
        $sPidByCat = array();
        if ($this->conf['checkCategoriesOfRelatedNews'] || $this->conf['useSPidFromCategory']) {
            // get visible categories and their singlePids
            $catres = $this->db->exec_SELECTquery('tt_news_cat.uid,tt_news_cat.single_pid', 'tt_news_cat',
                '1=1' . $this->SPaddWhere . $this->enableCatFields);

            $catTemp = array();
            while (($catrow = $this->db->sql_fetch_assoc($catres))) {
                $sPidByCat[$catrow['uid']] = $catrow['single_pid'];
                $catTemp[] = $catrow['uid'];
            }
            if ($this->conf['checkCategoriesOfRelatedNews']) {
                $visibleCategories = implode(',', $catTemp);
            }
        }
        $relPages = false;
        if ($this->conf['usePagesRelations']) {
            $relPages = $this->getRelatedPages($uid);
        }
        //		$select_fields = 'DISTINCT uid, pid, title, short, datetime, archivedate, type, page, ext_url, sys_language_uid, l18n_parent, M.tablenames';
        $select_fields = ' uid, pid, title, short, datetime, archivedate, type, page, ext_url, sys_language_uid, l18n_parent, tt_news_related_mm.tablenames, image, bodytext';

        //		$where = 'tt_news.uid=M.uid_foreign AND M.uid_local=' . $uid . ' AND M.tablenames!=' . $this->db->fullQuoteStr('pages', 'tt_news_related_mm');
        $where = 'tt_news_related_mm.uid_local=' . $uid . '
					AND tt_news.uid=tt_news_related_mm.uid_foreign
					AND tt_news_related_mm.tablenames!=' . $this->db->fullQuoteStr('pages', 'tt_news_related_mm');

        $groupBy = '';
        if ($lConf['groupBy']) {
            $groupBy = trim($lConf['groupBy']);
        }
        $orderBy = '';
        if ($lConf['orderBy']) {
            $orderBy = trim($lConf['orderBy']);
        }

        if ($this->conf['useBidirectionalRelations']) {
            //			$where = '((' . $where . ') OR (tt_news.uid=M.uid_local AND M.uid_foreign=' . $uid . ' AND M.tablenames!=' . $this->db->fullQuoteStr('pages', 'tt_news_related_mm') . '))';


            $where = '((' . $where . ')
					OR (tt_news_related_mm.uid_foreign=' . $uid . '
						AND tt_news.uid=tt_news_related_mm.uid_local
						AND tt_news_related_mm.tablenames!=' . $this->db->fullQuoteStr('pages',
                    'tt_news_related_mm') . '))';
        }


        //		$from_table = 'tt_news,tt_news_related_mm AS M';
        $from_table = 'tt_news_related_mm, tt_news';

        $res = $this->db->exec_SELECTquery($select_fields, $from_table, $where . $this->enableFields, $groupBy,
            $orderBy);

        if ($res) {
            $relrows = array();
            while (($relrow = $this->db->sql_fetch_assoc($res))) {

                $currentCats = array();
                if ($this->conf['checkCategoriesOfRelatedNews'] || $this->conf['useSPidFromCategory']) {
                    $currentCats = $this->getCategories($relrow['uid'], true);
                }
                if ($this->conf['checkCategoriesOfRelatedNews']) {
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

            if (is_array($relPages[0]) && $this->conf['usePagesRelations']) {
                $relrows = array_merge_recursive($relPages, $relrows);
            }

            $piVarsArray = array(
                'backPid' => ($this->conf['dontUseBackPid'] ? null : $this->config['backPid']),
                'year' => ($this->conf['dontUseBackPid'] ? null : ($this->piVars['year'] ? $this->piVars['year'] : null)),
                'month' => ($this->conf['dontUseBackPid'] ? null : ($this->piVars['month'] ? $this->piVars['month'] : null))
            );

            /** @var ContentObjectRenderer $veryLocal_cObj */
            $veryLocal_cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class); // Local cObj.
            $lines = array();

            foreach ($relrows as $row) {
                if ($this->tsfe->sys_language_content && $row['tablenames'] != 'pages') {
                    $OLmode = ($this->sys_language_mode == 'strict' ? 'hideNonTranslated' : '');
                    $row = $this->tsfe->sys_page->getRecordOverlay('tt_news', $row, $this->tsfe->sys_language_content,
                        $OLmode);
                    if (!is_array($row)) {
                        continue;
                    }
                }
                $veryLocal_cObj->start($row, 'tt_news');

                if ($row['type'] != 1 && $row['type'] != 2) {
                    // only normal news
                    $catSPid = false;
                    if ($row['sPidByCat'] && $this->conf['useSPidFromCategory']) {
                        $catSPid = $row['sPidByCat'];
                    }
                    $sPid = ($catSPid ? $catSPid : $this->config['singlePid']);
                    $newsAddParams = '&tx_ttnews[tt_news]=' . (int)$row['uid'];

                    // load the parameter string into the register 'newsAddParams' to access it from TS
                    $veryLocal_cObj->cObjGetSingle('LOAD_REGISTER',
                        array('newsAddParams' => $newsAddParams, 'newsSinglePid' => $sPid));

                    if (!$this->conf['getRelatedCObject.']['10.']['default.']['10.']['typolink.']['parameter'] || $catSPid) {
                        $this->conf['getRelatedCObject.']['10.']['default.']['10.']['typolink.']['parameter'] = $sPid;
                    }
                }

                $lines[] = $veryLocal_cObj->cObjGetSingle($this->conf['getRelatedCObject'],
                    $this->conf['getRelatedCObject.'], 'getRelatedCObject');
            }
            return implode('', $lines);
        } else {
            return '';
        }
    }


    /**
     * @param $uid
     *
     * @return array
     * @throws DBALException
     */
    protected function getRelatedPages($uid)
    {
        $relPages = array();

        $select_fields = 'uid,title,tstamp,description,subtitle,tt_news_related_mm.tablenames';
        $from_table = 'pages,tt_news_related_mm';
        $where = 'tt_news_related_mm.uid_local=' . $uid . '
					AND pages.uid=tt_news_related_mm.uid_foreign
					AND tt_news_related_mm.tablenames=' . $this->db->fullQuoteStr('pages',
                'tt_news_related_mm') . $this->getEnableFields('pages');

        $pres = $this->db->exec_SELECTquery($select_fields, $from_table, $where, '', 'title');

        while (($prow = $this->db->sql_fetch_assoc($pres))) {
            if ($this->tsfe->sys_language_content) {
                $prow = $this->tsfe->sys_page->getPageOverlay($prow, $this->tsfe->sys_language_content);
            }

            $relPages[] = array(
                'title' => $prow['title'],
                'datetime' => $prow['tstamp'],
                'archivedate' => 0,
                'type' => 1,
                'page' => $prow['uid'],
                'short' => $prow['subtitle'] ? $prow['subtitle'] : $prow['description'],
                'tablenames' => $prow['tablenames']
            );
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
            $tmpPS = $this->piVars['pS'];
            unset($this->piVars['pS']);
            $tmpPL = $this->piVars['pL'];
            unset($this->piVars['pL']);
        }

        // Initializing variables:
        $pointer = $this->piVars[$pointerName];
        $count = $this->internal['res_count'];
        $results_at_a_time = MathUtility::forceIntegerInRange($this->internal['results_at_a_time'],
            1, 1000);
        $maxPages = MathUtility::forceIntegerInRange($this->internal['maxPages'], 1, 100);
        $max = MathUtility::forceIntegerInRange(ceil($count / $results_at_a_time), 1,
            $maxPages);
        $pointer = intval($pointer);
        $links = array();

        // Make browse-table/links:
        if ($this->pi_alwaysPrev >= 0) {
            if ($pointer > 0) {
                $links[] = '
					<td nowrap="nowrap"><p>' . $this->pi_linkTP_keepPIvars($this->pi_getLL('pi_list_browseresults_prev',
                        '< Previous'), array(
                        $pointerName => ($pointer - 1 ? $pointer - 1 : '')
                    ), $this->allowCaching) . '</p></td>';
            } elseif ($this->pi_alwaysPrev) {
                $links[] = '
					<td nowrap="nowrap"><p>' . $this->pi_getLL('pi_list_browseresults_prev',
                        '< Previous') . '</p></td>';
            }
        }

        for ($a = 0; $a < $max; $a++) {
            $links[] = '
					<td' . ($pointer == $a ? $this->pi_classParam('browsebox-SCell') : '') . ' nowrap="nowrap"><p>' . $this->pi_linkTP_keepPIvars(trim($this->pi_getLL('pi_list_browseresults_page',
                        'Page') . ' ' . ($a + 1)), array(
                    $pointerName => ($a ? $a : '')
                ), $this->allowCaching) . '</p></td>';
        }
        if ($pointer < ceil($count / $results_at_a_time) - 1) {
            $links[] = '
					<td nowrap="nowrap"><p>' . $this->pi_linkTP_keepPIvars($this->pi_getLL('pi_list_browseresults_next',
                    'Next >'), array(
                    $pointerName => $pointer + 1
                ), $this->allowCaching) . '</p></td>';
        }

        $pR1 = $pointer * $results_at_a_time + 1;
        $pR2 = $pointer * $results_at_a_time + $results_at_a_time;
        $sTables = '

		<!--
			List browsing box:
		-->
		<div' . $this->pi_classParam('browsebox') . '>' . ($showResultCount ? '
			<p>' . ($this->internal['res_count'] ? sprintf(str_replace('###SPAN_BEGIN###',
                    '<span' . $this->pi_classParam('browsebox-strong') . '>',
                    $this->pi_getLL('pi_list_browseresults_displays',
                        'Displaying results ###FROM### to ###TO### out of ###OUT_OF###')),
                    $this->internal['res_count'] > 0 ? $pR1 : 0, min(array(
                        $this->internal['res_count'],
                        $pR2
                    )), $this->internal['res_count']) : $this->pi_getLL('pi_list_browseresults_noResults',
                    'Sorry, no items were found.')) . '</p>' : '') . '

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
     * @throws DBALException
     */
    protected function getXmlHeader()
    {

        $lConf = $this->conf['displayXML.'];
        $markerArray = array();

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

        $imgFile = GeneralUtility::getFileAbsFileName($this->cObj->stdWrap($lConf['xmlIcon'],
            $lConf['xmlIcon.']));
        $imgSize = is_file($imgFile) ? getimagesize($imgFile) : '';
        $markerArray['###IMG_W###'] = $imgSize[0];
        $markerArray['###IMG_H###'] = $imgSize[1];

        $relImgFile = str_replace(Environment::getPublicPath() . '/', '', $imgFile);
        $markerArray['###IMG###'] = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $relImgFile;

        $markerArray['###NEWS_WEBMASTER###'] = $lConf['xmlWebMaster'];
        $markerArray['###NEWS_MANAGINGEDITOR###'] = $lConf['xmlManagingEditor'];

        $selectConf = Array();
        $selectConf['pidInList'] = $this->pid_list;
        // select only normal news (type=0) for the RSS feed. You can override this with other types with the TS-var 'xmlNewsTypes'
        $selectConf['selectFields'] = 'max(datetime) as maxval';

        $res = $this->exec_getQuery('tt_news', $selectConf);

        $row = $this->db->sql_fetch_assoc($res);
        // optional tags
        if ($lConf['xmlLastBuildDate']) {
            $markerArray['###NEWS_LASTBUILD###'] = '<lastBuildDate>' . date('D, d M Y H:i:s O',
                    $row['maxval']) . '</lastBuildDate>';
        } else {
            $markerArray['###NEWS_LASTBUILD###'] = '';
        }

        if ($lConf['xmlFormat'] == 'atom03' || $lConf['xmlFormat'] == 'atom1') {
            $markerArray['###NEWS_LASTBUILD###'] = $this->helpers->getW3cDate($row['maxval']);
        }

        if ($lConf['xmlWebMaster']) {
            $markerArray['###NEWS_WEBMASTER###'] = '<webMaster>' . $lConf['xmlWebMaster'] . '</webMaster>';
        } else {
            $markerArray['###NEWS_WEBMASTER###'] = '';
        }

        if ($lConf['xmlManagingEditor']) {
            $markerArray['###NEWS_MANAGINGEDITOR###'] = '<managingEditor>' . $lConf['xmlManagingEditor'] . '</managingEditor>';
        } else {
            $markerArray['###NEWS_MANAGINGEDITOR###'] = '';
        }

        if ($lConf['xmlCopyright']) {
            if ($lConf['xmlFormat'] == 'atom1') {
                $markerArray['###NEWS_COPYRIGHT###'] = '<rights>' . $lConf['xmlCopyright'] . '</rights>';
            } else {
                $markerArray['###NEWS_COPYRIGHT###'] = '<copyright>' . $lConf['xmlCopyright'] . '</copyright>';
            }
        } else {
            $markerArray['###NEWS_COPYRIGHT###'] = '';
        }

        $charset = ($this->tsfe->metaCharset ? $this->tsfe->metaCharset : 'iso-8859-1');
        if ($lConf['xmlDeclaration']) {
            $markerArray['###XML_DECLARATION###'] = trim($lConf['xmlDeclaration']);
        } else {
            $markerArray['###XML_DECLARATION###'] = '<?xml version="1.0" encoding="' . $charset . '"?>';
        }

        // promoting TYPO3 in atom feeds, supress the subversion
        $version = explode('.', ($GLOBALS['TYPO3_VERSION'] ? $GLOBALS['TYPO3_VERSION'] : $GLOBALS['TYPO_VERSION']));
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
     * @throws DBALException
     */
    public function getSelectConf($addwhere, $noPeriod = 0)
    {


        // Get news
        $selectConf = array();
        $selectConf['pidInList'] = $this->pid_list;

        $selectConf['where'] = '';

        $selectConf['where'] .= ' 1=1 ';


        if ($this->arcExclusive) {
            if ($this->conf['enableArchiveDate'] && $this->config['datetimeDaysToArchive'] && $this->arcExclusive > 0) {
                $theTime = $this->SIM_ACCESS_TIME - intval($this->config['datetimeDaysToArchive']) * 3600 * 24;
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
                        $theTime = $this->SIM_ACCESS_TIME - intval($this->config['datetimeMinutesToArchive']) * 60;
                    } elseif ($this->config['datetimeHoursToArchive']) {
                        $theTime = $this->SIM_ACCESS_TIME - intval($this->config['datetimeHoursToArchive']) * 3600;
                    } else {
                        $theTime = $this->SIM_ACCESS_TIME - intval($this->config['datetimeDaysToArchive']) * 86400;
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
            if ($this->config['catSelection'] && (($this->theCode == 'LATEST' && $this->conf['latestWithCatSelector']) || ($this->theCode == 'AMENU' && $this->conf['amenuWithCatSelector']) || (GeneralUtility::inList('LIST,LIST2,LIST3,HEADER_LIST,SEARCH,XML',
                        $this->theCode)))) {
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
                    $selectConf['where'] .= ' AND (tt_news_cat_mm.uid_foreign IN (' . ($tmpCatExclusive ? $tmpCatExclusive : 0) . '))';
                }

                // de-select newsitems by their categories
                if (($this->config['categoryMode'] == -1 || $this->config['categoryMode'] == -2)) {
                    // do not show items with selected categories
                    $selectConf['leftjoin'] = 'tt_news_cat_mm ON tt_news.uid = tt_news_cat_mm.uid_local';
                    $selectConf['where'] .= ' AND (tt_news_cat_mm.uid_foreign NOT IN (' . ($this->catExclusive ? $this->catExclusive : 0) . '))';
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
            if ($this->piVars['arc']) {
                // allow overriding of the arcExclusive parameter from GET vars
                $this->arcExclusive = intval($this->piVars['arc']);
            }
            // select news from a certain period
            if (!$noPeriod && intval($this->piVars['pS'])) {
                $selectConf['where'] .= ' AND tt_news.datetime>=' . intval($this->piVars['pS']);

                if (intval($this->piVars['pL'])) {

                    $pL = intval($this->piVars['pL']);
                    //selecting news for a certain day only
                    if (intval($this->piVars['day'])) {
                        // = 24h, as pS always starts at the beginning of a day (00:00:00)
                        $pL = 86400;
                    }
                    $selectConf['where'] .= ' AND tt_news.datetime<' . (intval($this->piVars['pS']) + $pL);
                }
            }
        }

        // filter Workspaces preview.
        // Since "enablefields" is ignored in workspace previews it's required to filter out news manually which are not visible in the live version AND the selected workspace.
        if ($this->conf['excludeAlreadyDisplayedNews'] && $this->theCode != 'SEARCH' && $this->theCode != 'CATMENU' && $this->theCode != 'AMENU') {
            if (!is_array($GLOBALS['T3_VAR']['displayedNews'])) {
                $GLOBALS['T3_VAR']['displayedNews'] = array();
            } else {
                $excludeUids = implode(',', $GLOBALS['T3_VAR']['displayedNews']);
                if ($excludeUids) {
                    $selectConf['where'] .= ' AND tt_news.uid NOT IN (' . $this->db->cleanIntList($excludeUids) . ')';
                }
            }
        }

        if ($this->theCode != 'AMENU') {
            if ($this->config['groupBy']) {
                $selectConf['groupBy'] = $this->config['groupBy'];
            }

            if ($this->config['orderBy']) {
                if (strtoupper($this->config['orderBy']) == 'RANDOM') {
                    $selectConf['orderBy'] = 'RAND()';
                } else {
                    $selectConf['orderBy'] = $this->config['orderBy'] . ($this->config['ascDesc'] ? ' ' . $this->config['ascDesc'] : '');
                }
            } else {
                $selectConf['orderBy'] = 'datetime DESC';
            }

            // overwrite the groupBy value for categories
            if (!$this->catExclusive && $selectConf['groupBy'] == 'category') {
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
						AND ' . $this->addFromTable . '.tablenames!=' . $this->db->fullQuoteStr('pages',
                    $this->addFromTable);

            if ($this->conf['useBidirectionalRelations']) {
                $where = '((' . $where . ')
						OR (' . $this->addFromTable . '.uid_foreign=' . $this->relNewsUid . '
							AND tt_news.uid=' . $this->addFromTable . '.uid_local
							AND ' . $this->addFromTable . '.tablenames!=' . $this->db->fullQuoteStr('pages',
                        $this->addFromTable) . '))';
            }

            $selectConf['where'] .= ' AND ' . $where;
        }


        // function Hook for processing the selectConf array
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['selectConfHook'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['selectConfHook'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $selectConf = $_procObj->processSelectConfHook($this, $selectConf);
            }
        }

        return $selectConf;
    }


    /**
     * @return string
     * @throws DBALException
     */
    protected function getLanguageWhere()
    {
        $where = '';
        $sys_language_content = $this->tsfe->sys_language_content;
        if ($this->sys_language_mode == 'strict' && $sys_language_content) {
            // sys_language_mode == 'strict': If a certain language is requested, select only news-records from the default
            // language which have a translation. The translated articles will be overlayed later in the list or single function.
            $tmpres = $this->exec_getQuery('tt_news', array(
                'selectFields' => 'tt_news.l18n_parent',
                'where' => 'tt_news.sys_language_uid = ' . $sys_language_content . $this->enableFields,
                'pidInList' => $this->pid_list
            ));

            $strictUids = array();
            while (($tmprow = $this->db->sql_fetch_assoc($tmpres))) {
                $strictUids[] = $tmprow['l18n_parent'];
            }
            $strStrictUids = implode(',', $strictUids);
            // sys_language_uid=-1 = [all languages]
            $where .= '(tt_news.uid IN (' . ($strStrictUids ? $strStrictUids : 0) . ') OR tt_news.sys_language_uid=-1)';
        } else {
            // sys_language_mode NOT 'strict': If a certain language is requested, select only news-records in the default language.
            // The translated articles (if they exist) will be overlayed later in the displayList or displaySingle function.
            $where .= 'tt_news.sys_language_uid IN (0,-1)';
        }

        if ($this->conf['showNewsWithoutDefaultTranslation']) {
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
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['searchWhere'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['searchWhere'] as $_classRef) {
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
     */
    public function getEnableFields($table)
    {
        if (is_array($this->conf['ignoreEnableFields.'])) {
            $ignore_array = $this->conf['ignoreEnableFields.'];
        } else {
            $ignore_array = array();
        }
        if (!is_object($this->tsfe)) {
            $this->tsfe = $GLOBALS['TSFE'];
        }
        $show_hidden = ($table == 'pages' ? $this->tsfe->showHiddenPage : $this->tsfe->showHiddenRecords);

        return $this->tsfe->sys_page->enableFields($table, $show_hidden, $ignore_array);
    }


    /**
     * Creates and executes a SELECT query for records from $table and with conditions based on the configuration in
     * the $conf array Implements the "select" function in TypoScript
     *
     * @param $table
     * @param $conf
     *
     * @return ResultStatement|bool
     * @throws DBALException
     */
    protected function exec_getQuery($table, $conf)
    {
        $error = 0;
        // Construct WHERE clause:
        if (!$this->conf['dontUsePidList'] && !strcmp($conf['pidInList'], '')) {
            $conf['pidInList'] = 'this';
        }

        $queryParts = $this->getWhere($table, $conf);

        // Fields:
        $queryParts['SELECT'] = $conf['selectFields'] ? $conf['selectFields'] : '*';

        // Setting LIMIT:
        if (($conf['max'] || $conf['begin']) && !$error) {
            $conf['begin'] = MathUtility::forceIntegerInRange(ceil($this->cObj->calc($conf['begin'])),
                0);
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
            if ($conf['join']) {
                $joinPart = 'JOIN ' . trim($conf['join']);
            } elseif ($conf['leftjoin']) {
                $joinPart = 'LEFT OUTER JOIN ' . trim($conf['leftjoin']);
            } elseif ($conf['rightjoin']) {
                $joinPart = 'RIGHT OUTER JOIN ' . trim($conf['rightjoin']);
            }

            // Compile and return query:
            $queryParts['FROM'] = trim(($this->addFromTable ? $this->addFromTable . ',' : '') . $table . ' ' . $joinPart);

            return $this->db->exec_SELECT_queryArray($queryParts);
        } else {
            return false;
        }
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
        $queryParts = array(
            'SELECT' => '',
            'FROM' => '',
            'WHERE' => '',
            'GROUPBY' => '',
            'ORDERBY' => '',
            'LIMIT' => ''
        );

        if (($where = trim($conf['where']))) {
            $query .= ' AND ' . $where;
        }

        if (trim($conf['pidInList'])) {
            // str_replace instead of ereg_replace 020800
            $listArr = GeneralUtility::intExplode(',', $conf['pidInList']);
            if (count($listArr)) {
                $query .= ' AND ' . $table . '.pid IN (' . implode(',', $listArr) . ')';
            }
        }

        if ($conf['languageField']) {
            if ($this->tsfe->sys_language_contentOL && $TCA[$table] && $TCA[$table]['ctrl']['languageField'] && $TCA[$table]['ctrl']['transOrigPointerField']) {
                // Sys language content is set to zero/-1 - and it is expected that whatever routine processes the output will OVERLAY the records with localized versions!
                $sys_language_content = '0,-1';
            } else {
                $sys_language_content = intval($this->tsfe->sys_language_content);
            }
            $query .= ' AND ' . $conf['languageField'] . ' IN (' . $sys_language_content . ')';
        }

        $query .= $this->enableFields;

        // MAKE WHERE:
        if ($query) {
            $queryParts['WHERE'] = trim(substr($query, 4)); // Stripping of " AND"...
        }

        // GROUP BY
        if (trim($conf['groupBy'])) {
            $queryParts['GROUPBY'] = trim($conf['groupBy']);
        }

        // ORDER BY
        if (trim($conf['orderBy'])) {
            $queryParts['ORDERBY'] = trim($conf['orderBy']);
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
     * @return    void
     * @throws DBALException
     */
    protected function initLanguages()
    {
        $lres = $this->db->exec_SELECTquery('*', 'sys_language', '1=1' . $this->getEnableFields('sys_language'));

        $this->langArr = array();
        $this->langArr[0] = array('title' => $this->conf['defLangLabel'], 'flag' => $this->conf['defLangImage']);

        while (($row = $this->db->sql_fetch_assoc($lres))) {
            $this->langArr[$row['uid']] = $row;
        }
    }


    /**
     *
     */
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
     * @return    void
     * @throws DBALException
     */
    public function initCategoryVars()
    {
        $storagePid = false;


        $lc = $this->conf['displayCatMenu.'];

        if ($this->theCode == 'CATMENU') {
            // init catPidList
            $catPl = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'pages', 's_misc');
            $catPl = ($catPl ? $catPl : $this->cObj->stdWrap($lc['catPidList'], $lc['catPidList.']));
            $catPl = implode(',', GeneralUtility::intExplode(',', $catPl));

            $recursive = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'recursive', 's_misc');
            if (!strcmp($recursive, '') || $recursive === null) {
                $recursive = $this->cObj->stdWrap($lc['recursive'], $lc['recursive.']);
            }

            if ($catPl) {
                $storagePid = $this->pi_getPidList($catPl, $recursive);
            }
        }

        if ($storagePid) {
            $this->SPaddWhere = ' AND tt_news_cat.pid IN (' . $storagePid . ')';
        }

        if ($this->conf['catExcludeList']) {
            $this->SPaddWhere .= ' AND tt_news_cat.uid NOT IN (' . $this->conf['catExcludeList'] . ')';
        }

        $this->enableCatFields = $this->getEnableFields('tt_news_cat');

        $addWhere = $this->SPaddWhere . $this->enableCatFields;

        $useSubCategories = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'useSubCategories', 'sDEF');
        $this->config['useSubCategories'] = (strcmp($useSubCategories,
            '') ? $useSubCategories : $this->conf['useSubCategories']);

        // global ordering for categories, Can be overwritten later by catOrderBy for a certain content element
        $catOrderBy = trim($this->conf['catOrderBy']);
        $this->config['catOrderBy'] = $catOrderBy ? $catOrderBy : 'sorting';

        // categoryModes are: 0=display all categories, 1=display selected categories, -1=display deselected categories
        $categoryMode = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'categoryMode', 'sDEF');

        $this->config['categoryMode'] = $categoryMode ? $categoryMode : intval($this->conf['categoryMode']);
        // catselection holds only the uids of the categories selected by GETvars
        if ($this->piVars['cat']) {

            // catselection holds only the uids of the categories selected by GETvars
            $this->config['catSelection'] = $this->helpers->checkRecords($this->piVars['cat']);
            $this->piVars_catSelection = $this->config['catSelection'];

            if ($this->config['useSubCategories'] && $this->config['catSelection']) {
                // get subcategories for selection from getVars
                $subcats = Div::getSubCategories($this->config['catSelection'], $addWhere);
                $this->config['catSelection'] = implode(',',
                    array_unique(explode(',', $this->config['catSelection'] . ($subcats ? ',' . $subcats : ''))));
            }
        }
        $catExclusive = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'categorySelection', 'sDEF');
        $catExclusive = $catExclusive ? $catExclusive : trim($this->cObj->stdWrap($this->conf['categorySelection'],
            $this->conf['categorySelection.']));
        // ignore cat selection if categoryMode isn't set
        $this->catExclusive = $this->config['categoryMode'] ? $catExclusive : 0;

        $this->catExclusive = $this->helpers->checkRecords($this->catExclusive);
        // store the actually selected categories because we need them for the comparison in categoryMode 2 and -2
        $this->actuallySelectedCategories = $this->catExclusive;

        // get subcategories
        if ($this->config['useSubCategories'] && $this->catExclusive) {
            $subcats = Div::getSubCategories($this->catExclusive, $addWhere);
            $this->catExclusive = implode(',',
                array_unique(explode(',', $this->catExclusive . ($subcats ? ',' . $subcats : ''))));

        }

        // get more category fields from FF or TS
        $fields = explode(',',
            'catImageMode,catTextMode,catImageMaxWidth,catImageMaxHeight,maxCatImages,catTextLength,maxCatTexts');
        foreach ($fields as $key) {
            $value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $key, 's_category');
            $this->config[$key] = (is_numeric($value) ? $value : $this->conf[$key]);
        }
    }


    /**
     * @param $lConf
     *
     * @throws DBALException
     */
    public function initCatmenuEnv(&$lConf)
    {
        if ($lConf['catOrderBy']) {
            $this->config['catOrderBy'] = $lConf['catOrderBy'];
        }

        if ($this->catExclusive) {
            $this->catlistWhere = ' AND tt_news_cat.uid' . ($this->config['categoryMode'] < 0 ? ' NOT' : '') . ' IN (' . $this->catExclusive . ')';
        } else {
            if ($lConf['excludeList']) {
                $this->catlistWhere = ' AND tt_news_cat.uid NOT IN (' . implode(',', GeneralUtility::intExplode(',',
                        $lConf['excludeList'])) . ')';
            }
            if ($lConf['includeList']) {
                $this->catlistWhere .= ' AND tt_news_cat.uid IN (' . implode(',', GeneralUtility::intExplode(',',
                        $lConf['includeList'])) . ')';
            }
        }

        if ($lConf['includeList'] || $lConf['excludeList'] || $this->catExclusive) {

            // MOUNTS (in tree mode) must only contain the main/parent categories. Therefore it is required to filter out the subcategories from $this->catExclusive or $lConf['includeList']
            $categoryMounts = ($this->catExclusive ? $this->catExclusive : $lConf['includeList']);
            $tmpres = $this->db->exec_SELECTquery(
                'uid,parent_category',
                'tt_news_cat',
                'tt_news_cat.uid IN (' . $categoryMounts . ')' . $this->SPaddWhere . $this->enableCatFields,
                '',
                'tt_news_cat.' . $this->config['catOrderBy']);

            $this->cleanedCategoryMounts = array();

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
        if (strpos($fileName, 't3://') === 0) {
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

        $file = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($fileName);
        if ($file != '') {
            $fileContent = file_get_contents($file);
        }

        return $fileContent;
    }

    /**
     * read the template file, fill in global wraps and markers and write the result
     * to '$this->templateCode'
     *
     * @return    void
     */
    protected function initTemplate()
    {
        // read template-file and fill and substitute the Global Markers
        $templateflex_file = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template_file', 's_template');
        if ($templateflex_file) {
            if (false === strpos($templateflex_file, '/')) {
                $templateflex_file = 'uploads/tx_ttnews/' . $templateflex_file;
            }
            $this->templateCode = $this->getFileResource($templateflex_file);
        } else {
            $this->templateCode = $this->getFileResource($this->conf['templateFile']);
        }

        $splitMark = md5(microtime(true));
        $globalMarkerArray = array();
        list($globalMarkerArray['###GW1B###'], $globalMarkerArray['###GW1E###']) = explode($splitMark,
            $this->cObj->stdWrap($splitMark, $this->conf['wrap1.']));
        list($globalMarkerArray['###GW2B###'], $globalMarkerArray['###GW2E###']) = explode($splitMark,
            $this->cObj->stdWrap($splitMark, $this->conf['wrap2.']));
        list($globalMarkerArray['###GW3B###'], $globalMarkerArray['###GW3E###']) = explode($splitMark,
            $this->cObj->stdWrap($splitMark, $this->conf['wrap3.']));
        $globalMarkerArray['###GC1###'] = $this->cObj->stdWrap($this->conf['color1'], $this->conf['color1.']);
        $globalMarkerArray['###GC2###'] = $this->cObj->stdWrap($this->conf['color2'], $this->conf['color2.']);
        $globalMarkerArray['###GC3###'] = $this->cObj->stdWrap($this->conf['color3'], $this->conf['color3.']);
        $globalMarkerArray['###GC4###'] = $this->cObj->stdWrap($this->conf['color4'], $this->conf['color4.']);

        if (!($this->templateCode = $this->markerBasedTemplateService->substituteMarkerArray($this->templateCode,
            $globalMarkerArray))) {
            $this->errors[] = 'No HTML template found';
        }
    }


    /**
     * extends the pid_list given from $conf or FF recursively by the pids of the subpages
     * generates an array from the pagetitles of those pages
     *
     * @return    void
     */
    public function initPidList()
    {
        // pid_list is the pid/list of pids from where to fetch the news items.
        $pid_list = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'pages', 's_misc');
        $pid_list = $pid_list ? $pid_list : trim($this->cObj->stdWrap($this->conf['pid_list'],
            $this->conf['pid_list.']));
        $pid_list = $pid_list ? implode(',', GeneralUtility::intExplode(',', $pid_list)) : $this->tsfe->id;

        $recursive = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'recursive', 's_misc');
        if (!strcmp($recursive, '') || $recursive === null) {
            $recursive = $this->cObj->stdWrap($this->conf['recursive'], $this->conf['recursive.']);
        }

        // extend the pid_list by recursive levels
        $this->pid_list = $this->pi_getPidList($pid_list, $recursive);
        $this->pid_list = $this->pid_list ? $this->pid_list : 0;
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
     * @throws DBALException
     */
    protected function getPageArrayEntry($uid, $fN)
    {
        // Get pages
        $val = '';

        $L = intval($this->tsfe->sys_language_content);

        if ($uid && $fN) {
            $key = $uid . '_' . $L;
            if (is_array($this->pageArray[$key])) {
                $val = $this->pageArray[$key][$fN];
            } else {
                $rows = $this->db->exec_SELECTgetRows('*', 'pages', 'uid=' . $uid);
                $row = $rows[0];
                // get the translated record if the content language is not the default language
                if ($L) {
                    $row = $this->tsfe->sys_page->getPageOverlay($uid, $L);
                }
                $this->pageArray[$key] = $row;
                $val = $this->pageArray[$key][$fN];
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

        return GeneralUtility::makeInstance(TypoScriptService::class)->explodeConfigurationForOptionSplit($lConf,
            $splitCount);
    }


    /**
     * @param $selectConf
     *
     * @return array
     * @throws DBALException
     */
    protected function getArchiveMenuRange($selectConf)
    {
        $range = array('minval' => 0, 'maxval' => 0);

        if ($this->conf['amenuStart']) {
            $range['minval'] = strtotime($this->conf['amenuStart']);
        }
        if ($this->conf['amenuEnd']) {
            $eTime = strtotime($this->conf['amenuEnd']);
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
    protected function getNewsSubpart($myTemplate, $myKey, $row = Array())
    {
        return ($this->markerBasedTemplateService->getSubpart($myTemplate, $myKey));
    }


    /**
     * @param $marker
     *
     * @return bool
     */
    protected function isRenderMarker($marker)
    {
        if ($this->useUpstreamRenderer || in_array($marker, $this->renderMarkers)) {
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
        $matches = array();
        preg_match_all('/###(.+)###/Us', $template, $matches);

        return array_unique($matches[0]);
    }

    /**
     * converts the datetime of a record into variables you can use in realurl
     *
     * @param    integer $tstamp the timestamp to convert into a HR date
     *
     * @return    void
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
     * @param    integer $mConfKey : if this value is empty the var $mConfKey is not processed
     * @param    mixed   $passVar  : this var is processed in the user function
     *
     * @return    mixed        the processed $passVar
     */
    protected function userProcess($mConfKey, $passVar)
    {
        if ($this->conf[$mConfKey]) {
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
            $altSPM = trim($this->cObj->stdWrap($this->conf['altMainMarkers.'][$sPBody],
                $this->conf['altMainMarkers.'][$sPBody . '.']));
            /** @var TimeTracker $timeTracker */
            $timeTracker = GeneralUtility::makeInstance(TimeTracker::class);
            $timeTracker->setTSlogMessage('Using alternative subpart marker for \'' . $subpartMarker . '\': ' . $altSPM,
                1);
        }

        return $altSPM ? $altSPM : $subpartMarker;
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
        if (is_array($this->conf['general_stdWrap.'])) {
            $str = $this->local_cObj->stdWrap($str, $this->conf['general_stdWrap.']);
        }

        return $str;
    }


    /**
     * Returns alternating layouts
     *
     * @param    string  $templateCode       html code of the template subpart
     * @param    integer $alternatingLayouts number of alternatingLayouts
     * @param    string  $marker             name of the content-markers in this template-subpart
     *
     * @return    array        html code for alternating content markers
     */
    protected function getLayouts($templateCode, $alternatingLayouts, $marker)
    {
        $out = array();
        if ($this->config['altLayoutsOptionSplit']) {
            $splitLayouts = GeneralUtility::makeInstance(TypoScriptService::class)->explodeConfigurationForOptionSplit(array('ln' => $this->config['altLayoutsOptionSplit']),
                $this->config['limit']);
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
                $tmpY = $this->piVars['year'];
                $tmpM = $this->piVars['month'];
                $tmpD = $this->piVars['day'];

                $this->getHrDateSingle($row['datetime']);
                $piVarsArray['year'] = $this->piVars['year'];
                $piVarsArray['month'] = $this->piVars['month'];
                $piVarsArray['day'] = ($this->piVars['day'] ? $this->piVars['day'] : null);
            }
        } else {
            $piVarsArray['year'] = null;
            $piVarsArray['month'] = null;
        }

        $piVarsArray['tt_news'] = $row['uid'];

        $linkWrap = explode($this->token,
            $this->pi_linkTP_keepPIvars($this->token, $piVarsArray, $this->allowCaching, $this->conf['dontUseBackPid'],
                $singlePid));
        $url = $this->cObj->lastTypoLinkUrl;

        // hook for processing of links
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['getSingleViewLinkHook'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['getSingleViewLinkHook'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $params = array('singlePid' => &$singlePid, 'row' => &$row, 'piVarsArray' => $piVarsArray);
                $_procObj->processSingleViewLink($linkWrap, $url, $params, $this);
            }
        }
        $this->local_cObj->cObjGetSingle('LOAD_REGISTER',
            array('newsMoreLink' => $linkWrap[0] . $this->pi_getLL('more') . $linkWrap[1], 'newsMoreLink_url' => $url));

        if ($this->conf['useHRDates'] && $this->conf['useHRDatesSingle']) {
            $this->piVars['year'] = $tmpY;
            $this->piVars['month'] = $tmpM;
            $this->piVars['day'] = $tmpD;
        }

        if ($urlOnly) {
            return $url;
        } else {
            return $linkWrap;
        }
    }

    /**
     * Overrides a LocalLang value and takes care of the XLIFF structure.
     *
     * @param string $key   Key of the label
     * @param string $value Value of the label
     *
     * @return void
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
        $headers = trim(GeneralUtility::getUrl($url, 2));
        if ($headers) {
            $matches = array();
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
    protected function handleCatTextMode($val, $catSelLinkParams, $lConf, $output)
    {
        if ($this->config['catTextMode'] == 2) {
            // link to category shortcut
            $target = ($val['shortcut'] ? $val['shortcut_target'] : '');
            $pageID = ($val['shortcut'] ? $val['shortcut'] : $catSelLinkParams);
            $linkedTitle = $this->pi_linkToPage($val['title'], $pageID, $target);
            $output[] = $this->local_cObj->stdWrap($linkedTitle, $lConf['title_stdWrap.']);

            return $output;
        } elseif ($this->config['catTextMode'] == 3) {
            if ($this->conf['useHRDates']) {
                $output[] = $this->local_cObj->stdWrap($this->pi_linkTP_keepPIvars($val['title'], array(
                    'cat' => $val['uid'],
                    'year' => ($this->piVars['year'] ? $this->piVars['year'] : null),
                    'month' => ($this->piVars['month'] ? $this->piVars['month'] : null),
                    'backPid' => null,
                    'tt_news' => null,
                    $this->pointerName => null
                ), $this->allowCaching, 0, $catSelLinkParams), $lConf['title_stdWrap.']);

                return $output;
            } else {
                $output[] = $this->local_cObj->stdWrap($this->pi_linkTP_keepPIvars($val['title'], array(
                    'cat' => $val['uid'],
                    'backPid' => null,
                    'tt_news' => null,
                    $this->pointerName => null
                ), $this->allowCaching, 0, $catSelLinkParams), $lConf['title_stdWrap.']);

                return $output;
            }
        }

        return $output;
    }
}



