<?php

declare(strict_types=1);

use RG\TtNews\Tree\TableConfiguration\NewsDatabaseTreeDataProvider;

// get extension confArr
$confArr = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tt_news'] ?? [];
// switch the use of the "StoragePid"(general record Storage Page) for tt_news categories
$fTableWhere = (($confArr['useStoragePid'] ?? false) ? 'AND tt_news_cat.pid=###STORAGE_PID### ' : '');
// page where records will be stored in that have been created with a wizard
$sPid = ($fTableWhere !== '' && $fTableWhere !== '0' ? '###STORAGE_PID###' : '###CURRENT_PID###');
// l10n_mode for text fields
$l10n_mode = ($confArr['l10n_mode_prefixLangTitle'] ? 'prefixLangTitle' : '');
// l10n_mode for the image field
$l10n_mode_image = ($confArr['l10n_mode_imageExclude'] ? 'exclude' : 'mergeIfNotBlank');
// hide new localizations
$hideNewLocalizations = ($confArr['hideNewLocalizations'] ? 'mergeIfNotBlank' : '');

// ******************************************************************
// This is the standard TypoScript news table, tt_news
// ******************************************************************
return [
    'ctrl' => [
        'title' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news',
        'label' => $confArr['label'] ?: 'title',
        'label_alt' => $confArr['label_alt'] . ($confArr['label_alt2'] ? ',' . $confArr['label_alt2'] : ''),
        'label_alt_force' => $confArr['label_alt_force'],
        'default_sortby' => 'ORDER BY datetime DESC',
        'prependAtCopy' => $confArr['prependAtCopy'] ? 'core.db.general:prependAtCopy' : '',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'useColumnsForDefaultValues' => 'type',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'languageField' => 'sys_language_uid',
        'crdate' => 'crdate',
        'tstamp' => 'tstamp',
        'delete' => 'deleted',
        'type' => 'type',
        'editlock' => 'editlock',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'typeicon_column' => 'type',
        'typeicons' => [
            '1' => 'EXT:tt_news/Resources/Public/Images/Icons/tt_news_article.gif',
            '2' => 'EXT:tt_news/Resources/Public/Images/Icons/tt_news_exturl.gif',
        ],
        'typeicon_classes' => [
            '0' => 'tt-news',
            '1' => 'tt-news-article',
            '2' => 'tt-news-exturl',
            'default' => 'tt-news',
        ],
        'thumbnail' => 'image',
        'iconfile' => 'EXT:tt_news/Resources/Public/Images/Icons/ext_icon.gif',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'starttime' => [
            'exclude' => 1,
            'label' => 'core.db.general:starttime',
            'config' => [
                'type' => 'datetime',
                'size' => 16,
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'searchable' => false,
            ],
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'core.db.general:endtime',
            'config' => [
                'type' => 'datetime',
                'size' => 16,
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'searchable' => false,
            ],
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news_cat.hidden',
            'config' => [
                'type' => 'check',
                'default' => '1',
            ],
        ],
        'fe_group' => [
            'exclude' => 1,
            'label' => 'core.db.general:fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => [
                    ['label' => 'core.db.general:hide_at_login', 'value' => -1],
                    ['label' => 'core.db.general:any_login', 'value' => -2],
                    ['label' => 'core.db.general:fe_group', 'value' => '--div--'],
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'title' => [
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.title',
            'l10n_mode' => $l10n_mode,
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '256',
            ],
        ],
        'ext_url' => [
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.ext_url',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '256',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'renderType' => 'link',
                'appearance' => [
                    'browserTitle' => 'Link',
                ],
                'searchable' => false,
            ],
        ],
        'bodytext' => [
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.text',
            'l10n_mode' => $l10n_mode,
            'config' => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 5,
                'softref' => 'typolink_tag,email[subst],url',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
            ],
        ],
        'no_auto_pb' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.no_auto_pb',
            'config' => [
                'type' => 'check',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'short' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.subheader',
            'l10n_mode' => $l10n_mode,
            'config' => [
                'type' => 'text',
                'cols' => '40',
                'rows' => '3',
            ],
        ],
        'type' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.type.I.0', 'value' => 0],
                    ['label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.type.I.1', 'value' => 1],
                    ['label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.type.I.2', 'value' => 2],
                ],
                'default' => 0,
            ],
        ],
        'datetime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.datetime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'searchable' => false,
            ],
        ],
        'archivedate' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.archivedate',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'searchable' => false,
            ],
        ],
        'image' => [
            'exclude' => 1,
            'l10n_mode' => $l10n_mode_image,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.images',
            'config' => [
                'type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'maxitems' => 99,
                'minitems' => 0,
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                ],
                'overrideChildTca' => [
                    'types' => [
                        '0' => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                        2 => [
                            'showitem' => '
                                --palette--;;newsImagePalette,
                                --palette--;;filePalette',
                        ],
                    ],
                ],
            ],
        ],
        'author' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.author',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
                'max' => '80',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'searchable' => false,
            ],
        ],
        'author_email' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.author_email',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
                'max' => '80',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'searchable' => false,
            ],
        ],
        'related' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.related',
            'config' => [
                'type' => 'group',
                'allowed' => 'tt_news,pages',
                'MM' => 'tt_news_related_mm',
                'size' => '3',
                'autoSizeMax' => 10,
                'maxitems' => '200',
                'minitems' => '0',
            ],
        ],
        'keywords' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.keywords',
            'config' => [
                'type' => 'text',
                'cols' => '40',
                'rows' => '3',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'searchable' => false,
            ],
        ],
        'links' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.links',
            'config' => [
                'type' => 'text',
                'cols' => '40',
                'rows' => '3',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'searchable' => false,
            ],
        ],
        'category' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.category',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'tt_news_cat',
                'foreign_table_where' => ' ORDER BY tt_news_cat.title ASC',
                'MM' => 'tt_news_cat_mm',
                'MM_match_fields' => [
                    'tablenames' => '',
                ],
                'size' => 20,
                'minitems' => $confArr['requireCategories'] ? 1 : 0,
                'maxitems' => 500,
                'renderMode' => 'tree',
                'treeConfig' => [
                    'dataProvider' => NewsDatabaseTreeDataProvider::class,
                    'parentField' => 'parent_category',
                    'appearance' => [
                        'showHeader' => true,
                        'maxLevels' => 99,
                    ],
                ],
            ],
        ],
        'page' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.page',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => '1',
                'default' => 0,
                'maxitems' => '1',
                'minitems' => '0',
            ],
        ],
        'news_files' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media',
            'config' => [
                'type' => 'file',
                'allowed' => '',
                'disallowed' => 'php,php3',
                'maxitems' => 999,
                'minitems' => 0,
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                ],
            ],
        ],
        'slug' =>  [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:pages.slug',
            'displayCond' => 'VERSION:IS:false',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => ['title'],
                    'fieldSeparator' => '-',
                    'replacements' => [
                        '/' => '',
                        '®' => 'R',
                    ],
                ],
                'fallbackCharacter' => '-',
                'eval' => 'uniqueInSite',
                'default' => '',
                'searchable' => false,
            ],
        ],
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'core.db.general:language',
            'config' => ['type' => 'language'],
        ],
        'l18n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'core.db.general:l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 0,
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tt_news',
                'foreign_table_where' => 'AND tt_news.pid=###CURRENT_PID### AND tt_news.sys_language_uid IN (-1,0)',
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        't3ver_label' => [
            'displayCond' => 'FIELD:t3ver_label:REQ:true',
            'label' => 'core.db.general:versionLabel',
            'config' => [
                'type' => 'none',
                'size' => 27,
            ],
        ],

        'editlock' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:editlock',
            'config' => [
                'type' => 'check',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],

        /**
         * The following fields have to be configured here to get them processed by the listview in the tt_news BE module
         * they should never appear in the 'showitem' list as editable fields, though.
         */
        'uid' => [
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.uid',
            'config' => [
                'type' => 'none',
            ],
        ],
        'pid' => [
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.pid',
            'config' => [
                'type' => 'none',
            ],
        ],
        'tstamp' => [
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tstamp',
            'config' => [
                'type' => 'datetime', 'searchable' => false,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' =>
                'hidden, type,title,slug,short,bodytext,
            --div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.special, datetime,archivedate,--palette--;;author,keywords,--palette--;;language,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.media, image,links,news_files,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.catAndRels, category,related,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.access, starttime,endtime,fe_group,editlock,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.extended,
			',
        ],

        '1' => [
            'showitem' =>
                'hidden, type,title,page,short,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.special, datetime,archivedate,--palette--;;author,keywords,--palette--;;language,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.media, image,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.categories, category,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.access, starttime,endtime,fe_group,editlock,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.extended,
			',
        ],

        '2' => [
            'showitem' =>
                'hidden, type,title,ext_url,short,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.special, datetime,archivedate,--palette--;;author,keywords,--palette--;;language,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.media, image,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.categories, category,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.access, starttime,endtime,fe_group,editlock,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.extended,
			',
        ],
    ],
    'palettes' => [
        '2' => ['showitem' => 'no_auto_pb'],

        'author' => [
            'showitem' => 'author,author_email',
        ],
        'language' => [
            'showitem' => 'sys_language_uid,--linebreak--,t3ver_label,l18n_parent',
        ],
    ],
];
