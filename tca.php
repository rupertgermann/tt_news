<?php
// adds the possiblity to switch the use of the "StoragePid"(general record Storage Page) for tt_news categories
$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);
if ($confArr['useStoragePid']) {
    $fTableWhere = 'AND tt_news_cat.pid=###STORAGE_PID### ';
}

// ******************************************************************
// This is the standard TypoScript news table, tt_news
// ******************************************************************
$TCA['tt_news'] = Array (
	'ctrl' => $TCA['tt_news']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'title,hidden,datetime,starttime,archivedate,category,author,author_email,short,image,imagecaption,links,related,news_files'
	),
	'columns' => Array (	
		'starttime' => Array (
			'exclude' => 1,	
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'datetime',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'endtime' => Array (
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',	
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'datetime',
				'checkbox' => '0',
				'default' => '0',
				'range' => Array (
					'upper' => mktime(0,0,0,12,31,2020),
					'lower' => mktime(0,0,0,date('m')-1,date('d'),date('Y'))
				)
			)
		),
		'hidden' => Array (
			#'l10n_mode' => 'mergeIfNotBlank',
			'exclude' => 1,	
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '1'
			)
		),
		'fe_group' => Array (
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
		),
		'title' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'l10n_mode' => 'prefixLangTitle',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			)
		),
		'ext_url' => Array (
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.external',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256',
				'wizards' => Array(
					'_PADDING' => 2,
					'link' => Array(
						'type' => 'popup',
						'title' => 'Link',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				)
			)
		),
		'bodytext' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.text',
			'l10n_mode' => 'prefixLangTitle',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5',
				'wizards' => Array(
					'_PADDING' => 4,
					'RTE' => Array(
						'notNewRecords' => 1,
						'RTEonly' => 1,
						'type' => 'script',
						'title' => 'LLL:EXT:cms/locallang_ttc.php:bodytext.W.RTE',
						'icon' => 'wizard_rte2.gif',
						'script' => 'wizard_rte.php',
					),
				)
			)
		),
		'short' => Array (
			'exclude' => 1,	
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.subheader',	
			'l10n_mode' => 'prefixLangTitle',
			'config' => Array (
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'type' => Array (
			'exclude' => 1,	
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:tt_news/locallang_tca.php:tt_news.type.I.0', 0),
					Array('LLL:EXT:tt_news/locallang_tca.php:tt_news.type.I.1', 1),
					Array('LLL:EXT:tt_news/locallang_tca.php:tt_news.type.I.2', 2)
				),
				'default' => 0
			)
		),
		'datetime' => Array (
			'l10n_mode' => 'mergeIfNotBlank',
			'exclude' => 1,	
			'label' => 'LLL:EXT:tt_news/locallang_tca.php:tt_news.datetime',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'datetime',
				'default' => mktime(date("H"),date("i"),0,date("m"),date("d"),date("Y"))
				)
		),
		'archivedate' => Array (
			'l10n_mode' => 'mergeIfNotBlank',
			'exclude' => 1,
			'label' => 'LLL:EXT:tt_news/locallang_tca.php:tt_news.archivedate',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'date',
				'default' => '0'
			)
		),
		'image' => Array (
			'exclude' => 1,	
			'l10n_mode' => 'exclude',	
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.images',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => '1000',
				'uploadfolder' => 'uploads/pics',
				'show_thumbs' => '1',
				'size' => '3',
				'maxitems' => '10',
				'minitems' => '0'
			)
		),
		'imagecaption' => Array (
			'exclude' => 1,	
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.caption',
			'l10n_mode' => 'prefixLangTitle',
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '3'
			)
		),
		'imagealttext' => Array (
			'exclude' => 1,	
			'label' => 'LLL:EXT:tt_news/locallang_tca.php:tt_news.imagealttext',
			'l10n_mode' => 'prefixLangTitle',
			'config' => Array (
				'type' => 'text',
				'cols' => '20',
				'rows' => '3'
			)
		),
		'imagetitletext' => Array (
			'exclude' => 1,	
			'label' => 'LLL:EXT:tt_news/locallang_tca.php:tt_news.imagetitletext',
			'l10n_mode' => 'prefixLangTitle',
			'config' => Array (
				'type' => 'text',
				'cols' => '20',
				'rows' => '3'
			)
		),
		'author' => Array (
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',		
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.author',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'author_email' => Array (
			'exclude' => 1,	
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.email',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'related' => Array (
			'exclude' => 1,	
			'l10n_mode' => 'exclude',	
			'label' => 'LLL:EXT:tt_news/locallang_tca.php:tt_news.related',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
					'allowed' => 'tt_news',
					'MM' => 'tt_news_related_mm',
				'size' => '3',
				'autoSizeMax' => 10,
				'maxitems' => '200',
				'minitems' => '0',
				'show_thumbs' => '1'
			)
		),
		'keywords' => Array (
			'l10n_mode' => 'mergeIfNotBlank',
			'exclude' => 1,	
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.keywords',
			'config' => Array (
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'links' => Array (
			'l10n_mode' => 'mergeIfNotBlank',	
			'exclude' => 1,	
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.links',	
			'config' => Array (
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'category' => Array (
			'exclude' => 1,	
			'l10n_mode' => 'exclude',	
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.category',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'tt_news_cat',
				'foreign_table_where' => $fTableWhere.'ORDER BY tt_news_cat.sorting',
				'size' => 3,
				'autoSizeMax' => 10,
				'minitems' => 0,
				'maxitems' => 100,
				'MM' => 'tt_news_cat_mm',
				'wizards' => Array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => Array(
						'type' => 'script',
						'title' => 'Create new category',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'tt_news_cat',
							'pid' => '###STORAGE_PID###',
							'setValue' => 'set'
						),
						'script' => 'wizard_add.php',
					),
					'edit' => Array(
							'type' => 'popup',
							'title' => 'Edit category',
							'script' => 'wizard_edit.php',
							'popup_onlyOpenIfSelected' => 1,
							'icon' => 'edit2.gif',
							'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			)
		),
		'page' => Array (
			'exclude' => 1,
		#	'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.shortcut_page',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
					'allowed' => 'pages',
				'size' => '1',
				'maxitems' => '1',
				'minitems' => '0',
				'show_thumbs' => '1'
			)
		),
		# filelinks
		'news_files' => Array (
			'exclude' => 1,
			'l10n_mode' => 'exclude',	
			'label' => 'LLL:EXT:cms/locallang_ttc.php:media',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => '',	// Must be empty for disallowed to work.
				'disallowed' => 'php,php3',
				'max_size' => '10000',
				'uploadfolder' => 'uploads/media',
				'show_thumbs' => '1',
				'size' => '3',
				'autoSizeMax' => '10',
				'maxitems' => '10',
				'minitems' => '0'
			)
		),
		'sys_language_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tt_news',
				'foreign_table_where' => 'AND tt_news.uid=###REC_FIELD_l18n_parent### AND tt_news.sys_language_uid IN (-1,0)',
				'wizards' => Array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					
					'edit' => Array(
							'type' => 'popup',
							'title' => 'edit default language version of this record ',
							'script' => 'wizard_edit.php',
							'popup_onlyOpenIfSelected' => 1,
							'icon' => 'edit2.gif',
							'JSopenParams' => 'height=600,width=700,status=0,menubar=0,scrollbars=1,resizable=1',
					),
				),
			)
		),
		'l18n_diffsource' => Array('config'=>array('type'=>'passthrough')),
		't3ver_label' => Array (
			'displayCond' => 'EXT:version:LOADED:true',
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.versionLabel',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'max' => '30',
			)
		),
		
		
	),
	'types' => Array (	


// rte like tt_content
		#'0' => Array('showitem' => 'hidden;;;;1-1-1,type,sys_language_uid,title;;;;2-2-2,datetime,starttime;;1,archivedate,category,author,author_email,keywords,--div--,short;;;;3-3-3,bodytext;;9;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|table|image]:rte_transform[flag=rte_enabled|mode=ts];4-4-4, rte_enabled, text_properties;5-5-5,image;;;;6-6-6,imagecaption,--div--,links;;;;7-7-7,related,news_files'),
	


// divider to Tabs	
		'0' => Array('showitem' => 'title;;1;;,datetime;;2;;1-1-1,author;;3;;,short,bodytext;;4;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|table|image]:rte_transform[flag=rte_enabled|mode=ts];4-4-4,--div--;Relations,category,image;;;;1-1-1,imagecaption;;5;;,links;;;;2-2-2,related;;;;3-3-3,news_files;;;;4-4-4'),

		
		
		'1' => Array('showitem' =>
		'title;;1;;,datetime;;2;;1-1-1,author;;3;;,short,page;;4;;,--div--;Relations,category,image;;;;1-1-1,imagecaption'),

		'2' => Array('showitem' =>
		'title;;1;;,datetime;;2;;1-1-1,author;;3;;,short,ext_url;;4;;,--div--;Relations,category,image;;;;1-1-1,imagecaption')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'hidden,type,starttime,endtime,fe_group'),
		'2' => Array('showitem' => 'archivedate,l18n_parent,sys_language_uid'),
		'3' => Array('showitem' => 't3ver_label,author_email'),
		'4' => Array('showitem' => 'keywords'),
		'5' => Array('showitem' => 'imagealttext,imagetitletext'),
		
	
	)
);



// ******************************************************************
// This is the standard TypoScript news category table, tt_news_cat
// ******************************************************************
$TCA['tt_news_cat'] = Array (
	'ctrl' => $TCA['tt_news_cat']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'title,image,shortcut,shortcut_target'
	),
	'columns' => Array (	
		'title' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256',
				'eval' => 'required'
			)
		),
		'title_lang_ol' => Array (
			'label' => 'LLL:EXT:tt_news/locallang_tca.php:tt_news_cat.title_lang_ol',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256',
		
			)
		),
/*		'hidden' => Array (
		#'l10n_mode' => 'mergeIfNotBlank',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
			)
		),
		*/
		'image' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:tt_news/locallang_tca.php:tt_news_cat.image',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'gif,png,jpeg,jpg',	
				'max_size' => 100,	
				'uploadfolder' => 'uploads/pics',
				'show_thumbs' => 1,	
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'shortcut' => Array (
			'exclude' => 1,	
			'label' => 'LLL:EXT:tt_news/locallang_tca.php:tt_news_cat.shortcut',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => '3',
				'maxitems' => '1',
				'minitems' => '0',
				'show_thumbs' => '1'
			)
		),
		'shortcut_target' => Array (
			'exclude' => 1,	
			'label' => 'LLL:EXT:tt_news/locallang_tca.php:tt_news_cat.shortcut_target',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'checkbox' => '',
				'eval' => 'trim',
				'max' => '40'
			)
		),
		'single_pid' => Array (
			'exclude' => 1,	
			'label' => 'LLL:EXT:tt_news/locallang_tca.php:tt_news_cat.single_pid',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => '3',
				'maxitems' => '1',
				'minitems' => '0',
				'show_thumbs' => '1'
			)
		),
	),
		
	'types' => Array (	
		'0' => Array('showitem' => 'title,title_lang_ol,image;;1;;1-1-1,single_pid'),
	
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'shortcut,shortcut_target'),
	)
);


?>