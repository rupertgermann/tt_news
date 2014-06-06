<?php
/**
 * $Id: ext_tables.php 44687 2011-03-06 15:21:07Z rupi $
 */

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
	// get extension configuration
$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);





$TCA['tt_news'] = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:tt_news/locallang_tca.xml:tt_news',
		'label' => ($confArr['label']) ? $confArr['label'] : 'title',
		'label_alt' => $confArr['label_alt'] . ($confArr['label_alt2'] ? ',' . $confArr['label_alt2'] : ''),
		'label_alt_force' => $confArr['label_alt_force'],
		'default_sortby' => 'ORDER BY datetime DESC',
		'prependAtCopy' => $confArr['prependAtCopy'] ? 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy' : '',
 		'versioningWS' => TRUE,
		'versioning_followPages' => TRUE,
		'origUid' => 't3_origuid',
		'shadowColumnsForNewPlaceholders' => 'sys_language_uid,l18n_parent,starttime,endtime,fe_group',

		'dividers2tabs' => TRUE,
		'useColumnsForDefaultValues' => 'type',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'languageField' => 'sys_language_uid',
		'crdate' => 'crdate',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'type' => 'type',
		'cruser_id' => 'cruser_id',
		'editlock' => 'editlock',
		'enablecolumns' => array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'typeicon_column' => 'type',
		'typeicons' => array (
			'1' => t3lib_extMgm::extRelPath($_EXTKEY).'res/gfx/tt_news_article.gif',
			'2' => t3lib_extMgm::extRelPath($_EXTKEY).'res/gfx/tt_news_exturl.gif',
		),
//		'mainpalette' => '10',
		'thumbnail' => 'image',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'res/gfx/ext_icon.gif',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php'
	)
);


#$category_OrderBy = $confArr['category_OrderBy'];
$TCA['tt_news_cat'] = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:tt_news/locallang_tca.xml:tt_news_cat',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'default_sortby' => 'ORDER BY uid',
		'treeParentField' => 'parent_category',
		'dividers2tabs' => TRUE,
		'enablecolumns' => array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
// 		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'hideAtCopy' => true,
		'mainpalette' => '2,10',
		'crdate' => 'crdate',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'res/gfx/tt_news_cat.gif',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php'
	)
);

	// load tt_content to $TCA array
t3lib_div::loadTCA('tt_content');
	// remove some fields from the tt_content content element
$TCA['tt_content']['types']['list']['subtypes_excludelist'][9] = 'layout,select_key,pages,recursive';
	// add FlexForm field to tt_content
$TCA['tt_content']['types']['list']['subtypes_addlist'][9] = 'pi_flexform';
	// add tt_news to the "insert plugin" content element (list_type = 9)
t3lib_extMgm::addPlugin(array('LLL:EXT:tt_news/locallang_tca.xml:tt_news', 9));

t3lib_extMgm::addTypoScriptSetup('
  includeLibs.ts_news = EXT:tt_news/pi/class.tx_ttnews.php
  plugin.tt_news = USER
  plugin.tt_news {
    userFunc = tx_ttnews->main_news

    # validate some configuration values and display a message if errors have been found
    enableConfigValidation = 1
  }
');

	// initialize static extension templates
t3lib_extMgm::addStaticFile($_EXTKEY,'pi/static/ts_new/','News settings');
t3lib_extMgm::addStaticFile($_EXTKEY,'pi/static/css/','News CSS-styles');
//t3lib_extMgm::addStaticFile($_EXTKEY,'pi/static/ts_old/','table-based tmpl');
t3lib_extMgm::addStaticFile($_EXTKEY,'pi/static/rss_feed/','News feeds (RSS,RDF,ATOM)');

	// allow news and news-category records on normal pages
t3lib_extMgm::allowTableOnStandardPages('tt_news_cat');
t3lib_extMgm::allowTableOnStandardPages('tt_news');
	// add the tt_news record to the insert records content element
t3lib_extMgm::addToInsertRecords('tt_news');

	// switch the XML files for the FlexForm depending on if "use StoragePid"(general record Storage Page) is set or not.
if ($confArr['useStoragePid']) {
	t3lib_extMgm::addPiFlexFormValue(9, 'FILE:EXT:tt_news/flexform_ds.xml');
} else {
	t3lib_extMgm::addPiFlexFormValue(9, 'FILE:EXT:tt_news/flexform_ds_no_sPID.xml');
}


t3lib_extMgm::addPageTSConfig('
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
t3lib_extMgm::addLLrefForTCAdescr('tt_news','EXT:tt_news/csh/locallang_csh_ttnews.php');
t3lib_extMgm::addLLrefForTCAdescr('tt_news_cat','EXT:tt_news/csh/locallang_csh_ttnewscat.php');
t3lib_extMgm::addLLrefForTCAdescr('xEXT_tt_news','EXT:tt_news/csh/locallang_csh_manual.xml');
t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_txttnewsM1','EXT:tt_news/csh/locallang_csh_mod_newsadmin.xml');

//TODO how to insert CSH to the be_users table ???

	// adds processing for extra "codes" that have been added to the "what to display" selector in the content element by other extensions
include_once(t3lib_extMgm::extPath($_EXTKEY).'lib/class.tx_ttnews_itemsProcFunc.php');
	// class for displaying the category tree in BE forms.
include_once(t3lib_extMgm::extPath($_EXTKEY).'lib/class.tx_ttnews_TCAform_selectTree.php');
	// class that uses hooks in class.t3lib_tcemain.php (processDatamapClass and processCmdmapClass)
	// to prevent not allowed "commands" (copy,delete,...) for a certain BE usergroup
include_once(t3lib_extMgm::extPath($_EXTKEY).'lib/class.tx_ttnews_tcemain.php');





$tempColumns = array (
		'tt_news_categorymounts' => array (
			'exclude' => 1,
		#	'l10n_mode' => 'exclude', // the localizalion mode will be handled by the userfunction
			'label' => 'LLL:EXT:tt_news/locallang_tca.xml:tt_news.categorymounts',
			'config' => array (


				'type' => 'select',
				'form_type' => 'user',
				'userFunc' => 'tx_ttnews_TCAform_selectTree->renderCategoryFields',
				'treeView' => 1,
				'foreign_table' => 'tt_news_cat',
				#'foreign_table_where' => $fTableWhere.'ORDER BY tt_news_cat.'.$confArr['category_OrderBy'],
				'size' => 3,
				'minitems' => 0,
				'maxitems' => 500,
// 				'MM' => 'tt_news_cat_mm',

			)
		),
// 		'tt_news_cmounts_usesubcats' => array (
// 			'exclude' => 1,
// 			'label' => 'LLL:EXT:tt_news/locallang_tca.xml:tt_news.cmounts_usesubcats',
// 			'config' => array (
// 				'type' => 'check'
// 			)
// 		),
);


t3lib_div::loadTCA('be_groups');
t3lib_extMgm::addTCAcolumns('be_groups',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('be_groups','tt_news_categorymounts;;;;1-1-1');

$tempColumns['tt_news_categorymounts']['displayCond'] = 'FIELD:admin:=:0';
// $tempColumns['tt_news_cmounts_usesubcats']['displayCond'] = 'FIELD:admin:=:0';


t3lib_div::loadTCA('be_users');
t3lib_extMgm::addTCAcolumns('be_users',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('be_users','tt_news_categorymounts;;;;1-1-1');


if (TYPO3_MODE == 'BE')	{
	if (t3lib_div::int_from_ver(TYPO3_version) >= 4000000) {
		if (t3lib_div::int_from_ver(TYPO3_version) >= 4002000) {
			t3lib_extMgm::addModule('web','txttnewsM1','',t3lib_extMgm::extPath($_EXTKEY).'mod1/');

			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables'][$_EXTKEY][0]['fList'] = 'uid,title,author,category,datetime,archivedate,tstamp';
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables'][$_EXTKEY][0]['icon'] = TRUE;


		} else {
			/**
			 * @deprecated
			 * this module will be removed completely in future versions
			 */
			t3lib_extMgm::insertModuleFunction(
				'web_info',
				'tx_ttnewscatmanager_modfunc1',
				t3lib_extMgm::extPath($_EXTKEY).'modfunc1/class.tx_ttnewscatmanager_modfunc1.php',
				'LLL:EXT:tt_news/modfunc1/locallang.xml:moduleFunction.tx_ttnews_modfunc1'
			);
		}
		// register contextmenu for the tt_news category manager
		$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
			'name' => 'tx_ttnewscatmanager_cm1',
			'path' => t3lib_extMgm::extPath($_EXTKEY).'cm1/class.tx_ttnewscatmanager_cm1.php'
		);
	}
		// Adds a tt_news wizard icon to the content element wizard.
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_ttnews_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi/class.tx_ttnews_wizicon.php';

		// add folder icon
	if (t3lib_div::int_from_ver(TYPO3_version) < 4004000) {
		$ICON_TYPES['news'] = array('icon' => t3lib_extMgm::extRelPath($_EXTKEY) . 'res/gfx/ext_icon_ttnews_folder.gif');
	} else {
		t3lib_SpriteManager::addTcaTypeIcon('pages', 'contains-news', t3lib_extMgm::extRelPath($_EXTKEY) . 'res/gfx/ext_icon_ttnews_folder.gif');
	}

	if (TYPO3_UseCachingFramework) {
		// register the cache in BE so it will be cleared with "clear all caches"
		try {
			$GLOBALS['typo3CacheFactory']->create(
				'tt_news_cache',
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache']['frontend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache']['backend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache']['options']);
		} catch (t3lib_cache_exception_DuplicateIdentifier $e) {
			// do nothing, a tt_news_cache cache already exists
		}
	}

}

	// register HTML template for the tt_news BackEnd Module
$GLOBALS['TBE_STYLES']['htmlTemplates']['mod_ttnews_admin.html'] = t3lib_extMgm::extRelPath('tt_news').'mod1/mod_ttnews_admin.html';




?>