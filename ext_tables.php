<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

$boot = function () {
    // allow news and news-category records on normal pages
    ExtensionManagementUtility::allowTableOnStandardPages('tt_news_cat');
    ExtensionManagementUtility::allowTableOnStandardPages('tt_news');

    // initalize "context sensitive help" (csh)
    ExtensionManagementUtility::addLLrefForTCAdescr(
        'tt_news',
        'EXT:tt_news/csh/locallang_csh_ttnews.php'
    );
    ExtensionManagementUtility::addLLrefForTCAdescr(
        'tt_news_cat',
        'EXT:tt_news/csh/locallang_csh_ttnewscat.php'
    );
    ExtensionManagementUtility::addLLrefForTCAdescr(
        'xEXT_tt_news',
        'EXT:tt_news/csh/locallang_csh_manual.xml'
    );
    ExtensionManagementUtility::addLLrefForTCAdescr(
        '_MOD_web_txttnewsM1',
        'EXT:tt_news/csh/locallang_csh_mod_newsadmin.xml'
    );
};

$boot();
unset($boot);
