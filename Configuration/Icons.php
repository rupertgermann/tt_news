<?php

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;

return [
    'apps-pagetree-folder-contains-news' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:tt_news/Resources/Public/Images/Icons/ext_icon_ttnews_folder.gif',
    ],
    'ttnews-content-element-wizard-icon' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:tt_news/Resources/Public/Images/Icons/ContentElementWizardIcon.gif',
    ],
    'tt-news' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:tt_news/Resources/Public/Images/Icons/ext_icon.gif',
    ],
    'tt-news-article' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:tt_news/Resources/Public/Images/Icons/tt_news_article.gif',
    ],
    'tt-news-exturl' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:tt_news/Resources/Public/Images/Icons/tt_news_exturl.gif',
    ],
];
