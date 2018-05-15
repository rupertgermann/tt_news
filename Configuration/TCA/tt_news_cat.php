<?php
// ******************************************************************
// This is the standard TypoScript news category table, tt_news_cat
// ******************************************************************
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news_cat',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'delete' => 'deleted',
        'default_sortby' => 'ORDER BY uid',
        'treeParentField' => 'parent_category',
        'dividers2tabs' => true,
        'enablecolumns' => array(
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ),
        'hideAtCopy' => true,
        'mainpalette' => '2,10',
        'crdate' => 'crdate',
        'iconfile' => 'EXT:tt_news/Resources/Public/Icons/tt_news_cat.gif',
        'searchFields' => 'uid,title'
    ),
    'interface' => Array(
        'showRecordFieldList' => 'title,image,shortcut,shortcut_target'
    ),
    'columns' => Array(
        'title' => Array(
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.title',
            'config' => Array(
                'type' => 'input',
                'size' => '40',
                'max' => '256',
                'eval' => 'required'
            )
        ),
        'title_lang_ol' => Array(
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news_cat.title_lang_ol',
            'config' => Array(
                'type' => 'input',
                'size' => '40',
                'max' => '256',

            )
        ),
        'hidden' => Array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => Array(
                'type' => 'check',
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
                'foreign_table' => 'fe_groups'
            )
        ),
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
                'default' => '0',
                'renderType' => 'inputDateTime',
                'range' => Array(
                    'upper' => mktime(0, 0, 0, 12, 31, 2020),
                    'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
                )
            )
        ),
        'parent_category' => Array(
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news_cat.parent_category',
            'config' => Array(
                'type' => 'select',
                'foreign_table' => 'tt_news_cat',
                'foreign_table_where' => ' ORDER BY tt_news_cat.title ASC',
                'size' => 10,
                'autoSizeMax' => 50,
                'minitems' => 0,
                'maxitems' => 1,
                'renderType' => 'selectTree',
                'treeConfig' => array(
                    'parentField' => 'parent_category',
                    'dataProvider' => \RG\TtNews\Tree\TableConfiguration\NewsDatabaseTreeDataProvider::class,
                    'appearance' => array(
                        'showHeader' => true,
                        'width' => 400
                    ),
                )
            )
        ),
        'image' => Array(
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news_cat.image',
            'config' => Array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'gif,png,jpeg,jpg',
                'max_size' => 1024,
                'uploadfolder' => 'uploads/pics',
                'show_thumbs' => 1,
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            )
        ),
        'shortcut' => Array(
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news_cat.shortcut',
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
        'shortcut_target' => Array(
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news_cat.shortcut_target',
            'config' => Array(
                'type' => 'input',
                'size' => '10',
                'checkbox' => '',
                'eval' => 'trim',
                'max' => '40'
            )
        ),
        'single_pid' => Array(
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news_cat.single_pid',
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
        'description' => Array(
            'exclude' => 1,
            'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news_cat.description',
            'config' => Array(
                'type' => 'text',
                'cols' => '40',
                'rows' => '3'
            )
        ),
    ),

    'types' => Array(
        '0' => Array(
            'showitem' => '
			--palette--;;title,parent_category,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.special, image,--palette--;;shortcut,single_pid,description;;;;1-1-1,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.access, hidden,starttime,endtime,fe_group,
			--div--;LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.tabs.extended,
		'
        ),

    ),
    'palettes' => Array(
        'title' => [
            'showitem' => 'title,--linebreak--,title_lang_ol'
        ],
        'shortcut' => [
            'showitem' => 'shortcut,--linebreak--,shortcut_target'
        ],
    )
);

