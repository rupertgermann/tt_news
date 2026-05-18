<?php

declare(strict_types=1);

use RG\TtNews\Tree\TableConfiguration\NewsDatabaseTreeDataProvider;

// ******************************************************************
// This is the standard TypoScript news category table, tt_news_cat
// ******************************************************************

return [
    'ctrl' => [
        'title' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news_cat',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'delete' => 'deleted',
        'default_sortby' => 'ORDER BY uid',
        'treeParentField' => 'parent_category',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'hideAtCopy' => true,
        'mainpalette' => '2,10',
        'crdate' => 'crdate',
        'iconfile' => 'EXT:tt_news/Resources/Public/Images/Icons/tt_news_cat.gif',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.title',
            'config' => [
                'type' => 'input',
                'size' => '60',
                'max' => '256',
                'required' => true,
            ],
        ],
        'title_lang_ol' => [
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news_cat.title_lang_ol',
            'config' => [
                'type' => 'input',
                'size' => '60',
                'max' => '256',
                'searchable' => false,

            ],
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news_cat.hidden',
            'config' => [
                'type' => 'check',
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
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'core.db.general:starttime',
            'config' => [
                'type' => 'datetime',
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
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'searchable' => false,
            ],
        ],
        'parent_category' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news_cat.parent_category',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tt_news_cat',
                'foreign_table_where' => ' ORDER BY tt_news_cat.title ASC',
                'size' => 30,
                'minitems' => 0,
                'maxitems' => 1,
                'renderType' => 'selectTree',
                'default' => 0,
                'treeConfig' => [
                    'parentField' => 'parent_category',
                    'dataProvider' => NewsDatabaseTreeDataProvider::class,
                    'appearance' => [
                        'showHeader' => true,
                    ],
                ],
            ],
        ],
        'image' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news_cat.image',
            'config' => [
                'type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'maxitems' => 1,
                'minitems' => 0,
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                ],
            ],

        ],
        'shortcut' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news_cat.shortcut',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => '1',
                'maxitems' => '1',
                'minitems' => '0',
                'default' => 0,
            ],
        ],
        'shortcut_target' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news_cat.shortcut_target',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'checkbox' => '',
                'eval' => 'trim',
                'max' => '40',
                'searchable' => false,
            ],
        ],
        'single_pid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news_cat.single_pid',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => '1',
                'maxitems' => '1',
                'minitems' => '0',
                'default' => 0,
            ],
        ],
        'description' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news_cat.description',
            'config' => [
                'type' => 'text',
                'cols' => '60',
                'rows' => '3',
                'searchable' => false,
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '--palette--;;title,parent_category,--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.special,image,--palette--;;shortcut,single_pid,description,--palette--,--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.access,hidden,starttime,endtime,fe_group,--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.tabs.extended',
        ],

    ],
    'palettes' => [
        'title' => [
            'showitem' => 'title,--linebreak--,title_lang_ol',
        ],
        'shortcut' => [
            'showitem' => 'shortcut,--linebreak--,shortcut_target',
        ],
    ],
];
