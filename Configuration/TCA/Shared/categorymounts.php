<?php

// TCA definition for field tt_news_categorymounts in be_users and be_groups
return [
    'exclude' => 1,
    'l10n_mode' => 'exclude',
    'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news.categorymounts',
    'config' => [
        'type' => 'select',
        'renderType' => 'selectTree',
        'foreign_table' => 'tt_news_cat',
        'foreign_table_where' => ' ORDER BY tt_news_cat.title ASC',
        'size' => 10,
        'autoSizeMax' => 50,
        'minitems' => 0,
        'maxitems' => 500,
        'renderMode' => 'tree',
        'treeConfig' => [
            'expandAll' => true,
            'parentField' => 'parent_category',
            'appearance' => [
                'showHeader' => true,
                'width' => 400,
            ],
        ],
    ],
];
