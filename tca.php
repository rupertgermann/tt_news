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
			'exclude' => 1,	
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '1'
			)
		),
		'fe_group' => Array (
			'exclude' => 1,	
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
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			)
		),
		'ext_url' => Array (
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
			'config' => Array (
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'type' => Array (
			'exclude' => 1,	
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
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '3'
			)
		),
		'author' => Array (
			'exclude' => 1,	
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
			'exclude' => 1,	
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.keywords',
			'config' => Array (
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'links' => Array (
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
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.category',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'tt_news_cat',
				'foreign_table_where' => $fTableWhere.'ORDER BY tt_news_cat.uid',
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
		
		
		
	),
	'types' => Array (	
// non-rte '0' => Array('showitem' => 'hidden;;;;1-1-1,type,title;;;;2-2-2,datetime,starttime;;1,archivedate,category,author,author_email,keywords,--div--,short;;;;3-3-3,bodytext,image;;;;4-4-4,imagecaption,--div--,links;;;;5-5-5,related'),

// full enabled rte		'0' => Array('showitem' => 'hidden;;;;1-1-1,type,sys_language_uid,title;;;;2-2-2,datetime,starttime;;1,archivedate,category,author,author_email,keywords,--div--,short;;;;3-3-3,bodytext;;9;richtext[*]:rte_transform[flag=rte_enabled|mode=ts];3-3-3,image;;;;4-4-4,imagecaption,--div--,links;;;;5-5-5,related'),

// rte like tt_content
		'0' => Array('showitem' => 'hidden;;;;1-1-1,type,sys_language_uid,title;;;;2-2-2,datetime,starttime;;1,archivedate,category,author,author_email,keywords,--div--,short;;;;3-3-3,bodytext;;9;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|table|image]:rte_transform[flag=rte_enabled|mode=ts];4-4-4, rte_enabled, text_properties;5-5-5,image;;;;6-6-6,imagecaption,--div--,links;;;;7-7-7,related,news_files'),
		
		
		
		'1' => Array('showitem' => 'hidden;;;;1-1-1,type,sys_language_uid,page,title;;;;2-2-2,datetime,starttime;;1,archivedate,category,author,author_email,keywords,--div--,short;;;;3-3-3,image;;;;4-4-4,imagecaption'),
		'2' => Array('showitem' => 'hidden;;;;1-1-1,type,sys_language_uid,ext_url,title;;;;2-2-2,datetime,starttime;;1,archivedate,category,author,author_email,keywords,--div--,short;;;;3-3-3,image;;;;4-4-4,imagecaption')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'endtime,fe_group')
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
	),
		
	'types' => Array (	
		'0' => Array('showitem' => 'title,image;;1;;1-1-1'),
	
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'shortcut,shortcut_target'),
	)
);


?>