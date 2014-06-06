<?php
/**
 * $Id: ext_tables.php 8602 2008-03-15 17:07:57Z rupertgermann $
 */
 
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
	// get extension configuration
$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);

$TCA['tt_news'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:tt_news/locallang_tca.php:tt_news',
		'label' => $confArr['label'],
		'label_alt' => $confArr['label_alt'].($confArr['label_alt2']?','.$confArr['label_alt2']:''),
		'label_alt_force' => $confArr['label_alt_force'],
		'default_sortby' => 'ORDER BY datetime DESC',
		'prependAtCopy' => $confArr['prependAtCopy']?'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy':'',

// see condition below
// 		'versioning' => TRUE,
// 		'versioningWS' => TRUE,
		'versioning_followPages' => TRUE,
		'origUid' => 't3_origuid',
		'shadowColumnsForNewPlaceholders' => 'sys_language_uid,l18n_parent,starttime,endtime,fe_group',


		'dividers2tabs' => $confArr['noTabDividers']?FALSE:TRUE,
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
		'enablecolumns' => Array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'typeicon_column' => 'type',
		'typeicons' => Array (
			'1' => t3lib_extMgm::extRelPath($_EXTKEY).'res/tt_news_article.gif',
			'2' => t3lib_extMgm::extRelPath($_EXTKEY).'res/tt_news_exturl.gif',
		),
		'mainpalette' => '10',
		'thumbnail' => 'image',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'ext_icon.gif',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php'
	)
);






#$category_OrderBy = $confArr['category_OrderBy'];
$TCA['tt_news_cat'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:tt_news/locallang_tca.php:tt_news_cat',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'default_sortby' => 'ORDER BY uid',
		'treeParentField' => 'parent_category',
		'enablecolumns' => Array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
// 		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'hideAtCopy' => true,
		'mainpalette' => '2,10',
		'crdate' => 'crdate',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'res/tt_news_cat.gif',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php'
	)
);

/**
	Compatibility with TYPO3 versions lower than 4.0
*/

// enable Workspace versioning only for TYPO3 v 4.0 and higher
if (t3lib_div::int_from_ver(TYPO3_version) >= 4000000) {
	$TCA['tt_news']['ctrl']['versioningWS'] = TRUE;
} else {
	$TCA['tt_news']['ctrl']['versioning'] = TRUE;

	// disable support for nested fe_groups in tt_news records in TYPO3 versions lower than 4.0
	t3lib_div::loadTCA('tt_news');
	$TCA['tt_news']['columns']['fe_group'] = array (
		'exclude' => 1,
		'l10n_mode' => 'mergeIfNotBlank',
		'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
		'config' => Array (
			'type' => 'select',
			'items' => Array (
				Array('', 0),
				Array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
				Array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
				Array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
			),
			'foreign_table' => 'fe_groups'
		)
	);
	$TCA['tt_news']['palettes']['1'] = Array('showitem' => 'hidden,starttime,endtime,fe_group');
	$TCA['tt_news']['ctrl']['mainpalette'] = false;

	// disable support for nested fe_groups in tt_news_cat records in TYPO3 versions lower than 4.0
	t3lib_div::loadTCA('tt_news_cat');
	$TCA['tt_news_cat']['columns']['fe_group'] = array (
		'exclude' => 1,
		'l10n_mode' => 'mergeIfNotBlank',
		'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
		'config' => Array (
			'type' => 'select',
			'items' => Array (
				Array('', 0),
				Array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
				Array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
				Array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
			),
			'foreign_table' => 'fe_groups'
		)
	);
	$TCA['tt_news_cat']['palettes']['2'] = Array('showitem' => 'hidden,starttime,endtime,fe_group');
	$TCA['tt_news_cat']['ctrl']['mainpalette'] = 2;


}


	// load tt_content to $TCA array
t3lib_div::loadTCA('tt_content');
	// remove some fields from the tt_content content element
$TCA['tt_content']['types']['list']['subtypes_excludelist'][9]='layout,select_key,pages,recursive';
	// add FlexForm field to tt_content
$TCA['tt_content']['types']['list']['subtypes_addlist'][9]='pi_flexform';
	// add tt_news to the "insert plugin" content element (list_type = 9)
t3lib_extMgm::addPlugin(Array('LLL:EXT:tt_news/locallang_tca.php:tt_news', '9'));

	// initialize static extension templates
t3lib_extMgm::addStaticFile($_EXTKEY,'static/ts_new/','CSS-based tmpl');
t3lib_extMgm::addStaticFile($_EXTKEY,'static/css/','default CSS-styles');
t3lib_extMgm::addStaticFile($_EXTKEY,'static/ts_old/','table-based tmpl');
t3lib_extMgm::addStaticFile($_EXTKEY,'static/rss_feed/','News-feed (RSS,RDF,ATOM)');

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

	// sets the transformation mode for the RTE to "ts_css" if the extension css_styled_content is installed (default is: "ts")
if (t3lib_extMgm::isLoaded('css_styled_content')) {
	t3lib_extMgm::addPageTSConfig('
# RTE mode in table "tt_news"
RTE.config.tt_news.bodytext.proc.overruleMode=ts_css');
}

	// initalize "context sensitive help" (csh)
t3lib_extMgm::addLLrefForTCAdescr('tt_news','EXT:tt_news/locallang_csh_ttnews.php');
t3lib_extMgm::addLLrefForTCAdescr('tt_news_cat','EXT:tt_news/locallang_csh_ttnewscat.php');
t3lib_extMgm::addLLrefForTCAdescr('xEXT_tt_news','EXT:tt_news/locallang_csh_manual.xml');

	// adds processing for extra "codes" that have been added to the "what to display" selector in the content element by other extensions
include_once(t3lib_extMgm::extPath($_EXTKEY).'class.tx_ttnews_itemsProcFunc.php');
	// class for displaying the category tree in BE forms.
include_once(t3lib_extMgm::extPath($_EXTKEY).'class.tx_ttnews_treeview.php');
	// class that uses hooks in class.t3lib_tcemain.php (processDatamapClass and processCmdmapClass) to prevent not allowed "commands" (copy,delete,...) for a certain BE usergroup
include_once(t3lib_extMgm::extPath($_EXTKEY).'class.tx_ttnews_tcemain.php');






$tempColumns = Array (
		'tt_news_categorymounts' => Array (
			'exclude' => 1,
		#	'l10n_mode' => 'exclude', // the localizalion mode will be handled by the userfunction
			'label' => 'LLL:EXT:tt_news/locallang_tca.php:tt_news.categorymounts',
			'config' => Array (
			
			
				'type' => 'select',
				'form_type' => 'user',
				'userFunc' => 'tx_ttnews_treeview->displayCategoryTree',
				'treeView' => 1,
				'foreign_table' => 'tt_news_cat',
				#'foreign_table_where' => $fTableWhere.'ORDER BY tt_news_cat.'.$confArr['category_OrderBy'],
				'size' => 3,
				'autoSizeMax' => $confArr['categoryTreeHeigth'],
				'minitems' => 0,
				'maxitems' => 500,
// 				'MM' => 'tt_news_cat_mm',

			)
		),
// 		'tt_news_cmounts_usesubcats' => Array (
// 			'exclude' => 1,
// 			'label' => 'LLL:EXT:tt_news/locallang_tca.php:tt_news.cmounts_usesubcats',
// 			'config' => Array (
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



if (TYPO3_MODE=='BE')	{
	if (t3lib_div::int_from_ver(TYPO3_version) >= 4000000) {
		t3lib_extMgm::insertModuleFunction(
			'web_info',
			'tx_ttnewscatmanager_modfunc1',
			t3lib_extMgm::extPath($_EXTKEY).'modfunc1/class.tx_ttnewscatmanager_modfunc1.php',
			'LLL:EXT:tt_news/modfunc1/locallang.xml:moduleFunction.tx_ttnews_modfunc1'
		);

		$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][]=array(
			'name' => 'tx_ttnewscatmanager_cm1',
			'path' => t3lib_extMgm::extPath($_EXTKEY).'modfunc1/class.tx_ttnewscatmanager_cm1.php'
		);
	}
		// Adds a tt_news wizard icon to the content element wizard.
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_ttnews_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi/class.tx_ttnews_wizicon.php';
		// add folder icon
	$ICON_TYPES['news'] = array('icon' => t3lib_extMgm::extRelPath($_EXTKEY).'ext_icon_ttnews_folder.gif');
	
}



?>