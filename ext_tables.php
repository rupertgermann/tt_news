<?php
/**
 * $Id$
 */

if (!defined('TYPO3_MODE')) die ('Access denied.');
// get extension configuration
$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);

// remove some fields from the tt_content content element
$TCA['tt_content']['types']['list']['subtypes_excludelist'][9] = 'layout,select_key,pages,recursive';
// add FlexForm field to tt_content
$TCA['tt_content']['types']['list']['subtypes_addlist'][9] = 'pi_flexform';
// add tt_news to the "insert plugin" content element (list_type = 9)
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news', 9));

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
  includeLibs.ts_news = EXT:tt_news/pi/class.tx_ttnews.php
  plugin.tt_news = USER
  plugin.tt_news {
    userFunc = tx_ttnews->main_news

    # validate some configuration values and display a message if errors have been found
    enableConfigValidation = 1
  }
');

// initialize static extension templates
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'pi/static/ts_new/', 'News settings');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'pi/static/css/', 'News CSS-styles');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'pi/static/rss_feed/', 'News feeds (RSS,RDF,ATOM)');

// allow news and news-category records on normal pages
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tt_news_cat');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tt_news');
// add the tt_news record to the insert records content element
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tt_news');

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(9, 'FILE:EXT:tt_news/Resources/Private/Flexform/flexform_ds.xml');

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
	# RTE mode in table "tt_news"
	RTE.config.tt_news.bodytext.proc.overruleMode = ts_css

	TCEFORM.tt_news.bodytext.RTEfullScreenWidth = 100%

mod.web_txttnewsM1 {
	catmenu {
		expandFirst = 1

		show {
			cb_showEditIcons = 1
			cb_expandAll = 1
			cb_showHiddenCategories = 1

			btn_newCategory = 1
		}
	}
	list {
		limit = 15
		pidForNewArticles =
		fList = pid,uid,title,datetime,archivedate,tstamp,category;author
		icon = 1
		searchFields = uid,title,short,bodytext

		# configures the behavior of the record-title link. Possible values:
		# edit: link editform, view: link FE singleView, any other value: no link
		clickTitleMode = edit

		noListWithoutCatSelection = 1

		show {
			cb_showOnlyEditable = 1
			cb_showThumbs = 1
			search = 1

		}
		imageSize = 50

	}
	defaultLanguageLabel =
}

');

// initalize "context sensitive help" (csh)
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tt_news', 'EXT:tt_news/csh/locallang_csh_ttnews.php');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tt_news_cat', 'EXT:tt_news/csh/locallang_csh_ttnewscat.php');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('xEXT_tt_news', 'EXT:tt_news/csh/locallang_csh_manual.xml');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_txttnewsM1', 'EXT:tt_news/csh/locallang_csh_mod_newsadmin.xml');

// adds processing for extra "codes" that have been added to the "what to display" selector in the content element by other extensions
include_once(TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'lib/class.tx_ttnews_itemsProcFunc.php');
// class that uses hooks in class.t3lib_tcemain.php (processDatamapClass and processCmdmapClass)
// to prevent not allowed "commands" (copy,delete,...) for a certain BE usergroup
include_once(TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'lib/class.tx_ttnews_tcemain.php');

$tempColumns = array(
    'tt_news_categorymounts' => array(
        'exclude' => 1,
        'l10n_mode' => 'exclude',
        'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.categorymounts',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectTree',

            'foreign_table' => 'tt_news_cat',
            'foreign_table_where' => ' ORDER BY tt_news_cat.title ASC',
            'size' => 10,
            'autoSizeMax' => 50,
            'minitems' => 0,
            'maxitems' => 500,
            'renderMode' => 'tree',
            'treeConfig' => array(
                'expandAll' => true,
                'parentField' => 'parent_category',
                'appearance' => array(
                    'showHeader' => TRUE,
                    'width' => 400
                ),
            )
        )
    ),
);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_groups', $tempColumns);
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_groups', 'tt_news_categorymounts;;;;1-1-1');

// show the category selection only in non-admin be_users records
$tempColumns['tt_news_categorymounts']['displayCond'] = 'FIELD:admin:=:0';

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns);
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tt_news_categorymounts;;;;1-1-1');

if (TYPO3_MODE == 'BE') {
    if ($confArr['showBackEndModule']) {
        TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('web', 'txttnewsM1', '', TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod1/');
        $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
            'name' => 'tx_ttnewscatmanager_cm1'
        );
    }

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables'][$_EXTKEY][0]['fList'] = 'uid,title,author,category,datetime,archivedate,tstamp';
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables'][$_EXTKEY][0]['icon'] = TRUE;

    // register contextmenu for the tt_news category manager

    // Adds a tt_news wizard icon to the content element wizard.
    $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_ttnews_wizicon'] = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'pi/class.tx_ttnews_wizicon.php';

    // add folder icon
    $iconRegistry
        = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class)->registerIcon(
        'tcarecords-pages-contains-news',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        ['source' => 'EXT:tt_news/res/gfx/ext_icon_ttnews_folder.gif']
    );

    $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-news']
        = 'tcarecords-pages-contains-news';

    // register HTML template for the tt_news BackEnd Module
    $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_ttnews_admin.html'] = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tt_news') . 'mod1/mod_ttnews_admin.html';
}
