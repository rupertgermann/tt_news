<?php
/**
 * $Id$
 */

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// get extension configuration
$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
  includeLibs.ts_news = EXT:tt_news/pi/class.tx_ttnews.php
  plugin.tt_news = USER
  plugin.tt_news {
    userFunc = tx_ttnews->main_news

    # validate some configuration values and display a message if errors have been found
    enableConfigValidation = 1
  }
');

// allow news and news-category records on normal pages
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tt_news_cat');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tt_news');

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

if (TYPO3_MODE == 'BE') {
    if ($confArr['showBackEndModule']) {
        TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
            'web',
            'txttnewsM1',
            '',
            '',
            [
                'routeTarget' => \tx_ttnews_module1::class . '::mainAction',
                'access' => 'user,group',
                'name' => 'web_txttnewsM1',
                'icon' => 'EXT:tt_news/mod1/moduleicon.gif',
                'navigationComponentId' => 'typo3-pagetree',
                'labels' => 'LLL:EXT:tt_news/mod1/locallang_mod.xml'
            ]
        );
    }

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables'][$_EXTKEY][0]['fList'] = 'uid,title,author,category,datetime,archivedate,tstamp';
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables'][$_EXTKEY][0]['icon'] = TRUE;

    // register contextmenu for the tt_news category manager

    // Adds a tt_news wizard icon to the content element wizard.
    $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_ttnews_wizicon'] = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'pi/class.tx_ttnews_wizicon.php';

    // Register all icons
    \WMDB\TtNews\Utility\IconFactory::registerAllIconIdentifiers();

    // register HTML template for the tt_news BackEnd Module
    $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_ttnews_admin.html'] = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tt_news') . 'mod1/mod_ttnews_admin.html';
}
