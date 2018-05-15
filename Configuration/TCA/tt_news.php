<?php

// get extension confArr
$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);
// switch the use of the "StoragePid"(general record Storage Page) for tt_news categories
$fTableWhere = ($confArr['useStoragePid'] ? 'AND tt_news_cat.pid=###STORAGE_PID### ' : '');
// page where records will be stored in that have been created with a wizard
$sPid = ($fTableWhere ? '###STORAGE_PID###' : '###CURRENT_PID###');
// l10n_mode for text fields
$l10n_mode = ($confArr['l10n_mode_prefixLangTitle'] ? 'prefixLangTitle' : '');
$l10n_mode_author = ($confArr['l10n_mode_prefixLangTitle'] ? 'mergeIfNotBlank' : '');
// l10n_mode for the image field
$l10n_mode_image = ($confArr['l10n_mode_imageExclude'] ? 'exclude' : 'mergeIfNotBlank');
// hide new localizations
$hideNewLocalizations = ($confArr['hideNewLocalizations'] ? 'mergeIfNotBlank' : '');
// ******************************************************************
// This is the standard TypoScript news table, tt_news
// ******************************************************************
return Array(
    'ctrl' => array(
        'title' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news',
        'label' => ($confArr['label']) ? $confArr['label'] : 'title',
        'label_alt' => $confArr['label_alt'] . ($confArr['label_alt2'] ? ',' . $confArr['label_alt2'] : ''),
        'label_alt_force' => $confArr['label_alt_force'],
        'default_sortby' => 'ORDER BY datetime DESC',
        'prependAtCopy' => $confArr['prependAtCopy'] ? 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy' : '',
        'versioningWS' => true,
        'versioning_followPages' => true,
        'origUid' => 't3_origuid',
        'shadowColumnsForNewPlaceholders' => 'sys_language_uid,l18n_parent,starttime,endtime,fe_group',
        'dividers2tabs' => true,
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
        'enablecolumns' => array(
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ),
        'typeicon_column' => 'type',
        'typeicons' => array(
            '1' => 'EXT:tt_news/Resources/Public/Icons/tt_news_article.gif',
            '2' => 'EXT:tt_news/Resources/Public/Icons/tt_news_exturl.gif',
        ),
        'thumbnail' => 'image',
        'iconfile' => 'EXT:tt_news/Resources/Public/Icons/ext_icon.gif',
        'searchFields' => 'uid,title,short,bodytext'
    ),
    'interface' => Array(
        'showRecordFieldList' => 'title,hidden,datetime,starttime,archivedate,category,author,author_email,short,image,imagecaption,links,related,news_files'
    ),
    'columns' => Array(
        'starttime' => Array(
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => Array(
                'type' => 'input',
                'eval' => 'datetime',
                'default' => '0',
                'renderType' => 'inputDateTime'
            )
        ),
        'endtime' => Array(
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => Array(
                'type' => 'input',
                'eval' => 'datetime',
                'checkbox' => '0',
                'renderType' => 'inputDateTime',
                'range' => Array(
                    'upper' => mktime(0, 0, 0, 12, 31, 2020),
                    'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
                )
            )
        ),
        'hidden' => Array(
            'l10n_mode' => $hideNewLocalizations,
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => Array(
                'type' => 'check',
                'default' => '1'
            )
        ),
        'fe_group' => Array(
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.fe_group',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => Array(
                    Array('LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login', -1),
                    Array('LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.any_login', -2),
                    Array('LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.usergroups', '--div--')
                ),
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
            )
        ),
        'title' => Array(
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.title',
            'l10n_mode' => $l10n_mode,
            'config' => Array(
                'type' => 'input',
                'size' => '40',
                'max' => '256'
            )
        ),
        'ext_url' => Array(
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.external',
            'config' => Array(
                'type' => 'input',
                'size' => '40',
                'max' => '256',
                'wizards' => Array(
                    '_PADDING' => 2,
                    'link' => Array(
                        'type' => 'popup',
                        'title' => 'Link',
                        'icon' => 'actions-wizard-link',
                        'module' => array(
                            'name' => 'wizard_link',
                            'urlParameters' => array(
                                'mode' => 'wizard'
                            )
                        ),
                        'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
                    )
                )
            )
        ),
        'bodytext' => Array(
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.text',
            'defaultExtras' => 'richtext:rte_transform[mode=ts_css]',
            'l10n_mode' => $l10n_mode,
            'config' => Array(
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'softref' => 'typolink_tag,images,email[subst],url',
                'wizards' => Array(
                    'RTE' => array(
                        'notNewRecords' => 1,
                        'RTEonly' => 1,
                        'type' => 'script',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.W.RTE',
                        'icon' => 'actions-wizard-rte',
                        'module' => array(
                            'name' => 'wizard_rte'
                        )
                    )
                )
            )
        ),
        'no_auto_pb' => Array(
            'l10n_mode' => 'mergeIfNotBlank',
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.no_auto_pb',
            'config' => Array(
                'type' => 'check'
            )
        ),
        'short' => Array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.subheader',
            'l10n_mode' => $l10n_mode,
            'config' => Array(
                'type' => 'text',
                'cols' => '40',
                'rows' => '3'
            )
        ),
        'type' => Array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.type',
            'config' => Array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => Array(
                    Array('LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.type.I.0', 0),
                    Array('LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.type.I.1', 1),
                    Array('LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.type.I.2', 2)
                ),
                'default' => 0
            )
        ),
        'datetime' => Array(
            'l10n_mode' => 'mergeIfNotBlank',
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.datetime',
            'config' => Array(
                'type' => 'input',
                'eval' => 'datetime',
                'renderType' => 'inputDateTime'
            )
        ),
        'archivedate' => Array(
            'l10n_mode' => 'mergeIfNotBlank',
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.archivedate',
            'config' => Array(
                'type' => 'input',
                'eval' => 'date',
                'default' => '0',
                'renderType' => 'inputDateTime'
            )
        ),
        'image' => Array(
            'exclude' => 1,
            'l10n_mode' => $l10n_mode_image,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.images',
            'config' => Array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'max_size' => '10000',
                'uploadfolder' => 'uploads/pics',
                'show_thumbs' => '1',
                'size' => 3,
                'autoSizeMax' => 15,
                'maxitems' => '99',
                'minitems' => '0'
            )
        ),
        'imagecaption' => Array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.caption',
            'l10n_mode' => $l10n_mode,
            'config' => Array(
                'type' => 'text',
                'cols' => '30',
                'rows' => '3'
            )
        ),
        'imagealttext' => Array(
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.imagealttext',
            'l10n_mode' => $l10n_mode,
            'config' => Array(
                'type' => 'text',
                'cols' => '20',
                'rows' => '3'
            )
        ),
        'imagetitletext' => Array(
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.imagetitletext',
            'l10n_mode' => $l10n_mode,
            'config' => Array(
                'type' => 'text',
                'cols' => '20',
                'rows' => '3'
            )
        ),
        'author' => Array(
            'exclude' => 1,
            'l10n_mode' => $l10n_mode_author,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.author',
            'config' => Array(
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
                'max' => '80'
            )
        ),
        'author_email' => Array(
            'exclude' => 1,
            'l10n_mode' => $l10n_mode_author,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.email',
            'config' => Array(
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
                'max' => '80'
            )
        ),
        'related' => Array(
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.related',
            'config' => Array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tt_news,pages',
                'MM' => 'tt_news_related_mm',
                'size' => '3',
                'autoSizeMax' => 10,
                'maxitems' => '200',
                'minitems' => '0',
                'show_thumbs' => '1',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest'
                    )
                )
            )
        ),
        'keywords' => Array(
            'l10n_mode' => 'mergeIfNotBlank',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.keywords',
            'config' => Array(
                'type' => 'text',
                'cols' => '40',
                'rows' => '3'
            )
        ),
        'links' => Array(
            'l10n_mode' => $l10n_mode_author,
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.links',
            'config' => Array(
                'type' => 'text',
                'cols' => '40',
                'rows' => '3'
            )
        ),
        'category' => Array(
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.category',
            'config' => Array(
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'tt_news_cat',
                'foreign_table_where' => ' ORDER BY tt_news_cat.title ASC',
                'MM' => 'tt_news_cat_mm',
                'size' => 10,
                'autoSizeMax' => 50,
                'minitems' => $confArr['requireCategories'] ? 1 : 0,
                'maxitems' => 500,
                'renderMode' => 'tree',
                'treeConfig' => array(
                    'dataProvider' => \RG\TtNews\Tree\TableConfiguration\NewsDatabaseTreeDataProvider::class,
                    'parentField' => 'parent_category',
                    'appearance' => array(
                        'showHeader' => true,
                        'width' => 400,
                        'maxLevels' => 99,
                    ),
                )
            )
        ),
        'page' => Array(
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.shortcut_page',
            'config' => Array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => '1',
                'maxitems' => '1',
                'minitems' => '0',
                'show_thumbs' => '1'
            )
        ),
        'news_files' => Array(
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media',
            'config' => Array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => '',    // Must be empty for disallowed to work.
                'disallowed' => 'php,php3',
                'max_size' => '10000',
                'uploadfolder' => 'uploads/media',
                'show_thumbs' => '1',
                'size' => '3',
                'autoSizeMax' => '10',
                'maxitems' => '100',
                'minitems' => '0'
            )
        ),
        'sys_language_uid' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => array(
                    array(
                        'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ),
                ),
                'default' => 0,
            )
        ),
        'l18n_parent' => Array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => Array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => Array(
                    Array('', 0),
                ),
                'foreign_table' => 'tt_news',
                'foreign_table_where' => 'AND tt_news.pid=###CURRENT_PID### AND tt_news.sys_language_uid IN (-1,0)',
            )
        ),
        'l18n_diffsource' => Array(
            'config' => array(
                'type' => 'passthrough'
            )
        ),
        't3ver_label' => Array(
            'displayCond' => 'FIELD:t3ver_label:REQ:true',
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.versionLabel',
            'config' => Array(
                'type' => 'none',
                'cols' => 27
            )
        ),

        'editlock' => Array(
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:editlock',
            'config' => Array(
                'type' => 'check'
            )
        ),


        /**
         * The following fields have to be configured here to get them processed by the listview in the tt_news BE module
         * they should never appear in the 'showitem' list as editable fields, though.
         */
        'uid' => Array(
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.uid',
            'config' => Array(
                'type' => 'none'
            )
        ),
        'pid' => Array(
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.pid',
            'config' => Array(
                'type' => 'none'
            )
        ),
        'tstamp' => Array(
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tstamp',
            'config' => Array(
                'type' => 'input',
                'eval' => 'datetime',
            )
        ),
    ),
    'types' => Array(
        '0' => Array(
            'showitem' =>
                'hidden, type,title,short,bodytext,
            --div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.special, datetime,archivedate,--palette--;;author,keywords,--palette--;;language,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.media, image,--palette--;;imagetexts,links,news_files,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.catAndRels, category,related,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.access, starttime,endtime,fe_group,editlock,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.extended,
			'
        ),

        '1' => Array(
            'showitem' =>
                'hidden, type,title,page,short,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.special, datetime,archivedate,--palette--;;author,keywords,--palette--;;language,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.media, image,--palette--;;imagetexts,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.categories, category,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.access, starttime,endtime,fe_group,editlock,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.extended,
			'
        ),

        '2' => Array(
            'showitem' =>
                'hidden, type,title,ext_url,short,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.special, datetime,archivedate,--palette--;;author,keywords,--palette--;;language,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.media, image,--palette--;;imagetexts,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.categories, category,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.access, starttime,endtime,fe_group,editlock,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.extended,
			'
        )
    ),
    'palettes' => Array(
        '2' => Array('showitem' => 'no_auto_pb'),

        'author' => [
            'showitem' => 'author,author_email'
        ],
        'language' => [
            'showitem' => 'sys_language_uid,--linebreak--,t3ver_label,l18n_parent'
        ],
        'imagetexts' => [
            'showitem' => 'imagecaption,--linebreak--,imagealttext,imagetitletext'
        ],
    )
);
