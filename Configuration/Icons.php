<?php

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;

return [
    'apps-pagetree-folder-contains-news' => [
        BitmapIconProvider::class,
        'source' => 'EXT:tt_news/Resources/Public/Images/Icons/ext_icon_ttnews_folder.gif',
    ],
    'ttnews-content-element-wizard-icon' => [
        BitmapIconProvider::class,
        'source' => 'EXT:tt_news/Resources/Public/Images/Icons/ContentElementWizardIcon.gif',
    ],
    'tt-news' => [
        BitmapIconProvider::class,
        'source' => 'EXT:tt_news/Resources/Public/Images/Icons/ext_icon.gif',
    ],
    'tt-news-article' => [
        BitmapIconProvider::class,
        'source' => 'EXT:tt_news/Resources/Public/Images/Icons/tt_news_article.gif',
    ],
    'tt-news-exturl' => [
        BitmapIconProvider::class,
        'source' => 'EXT:tt_news/Resources/Public/Images/Icons/tt_news_exturl.gif',
    ],
];
