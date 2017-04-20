<?php
defined('TYPO3_MODE') or die();

// Add "pages.tx_ttnews_cat_storage_pid" field to TCA column
$additionalColumns = array(
    'tx_ttnews_cat_storage_pid' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_db.xlf:tx_ttnews_cat_storage_pid',
        'config' => array(
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'pages',
            'size' => '1',
            'maxitems' => '1',
            'minitems' => '0',
            'show_thumbs' => '1',
            'wizards' => array(
                'suggest' => array(
                    'type' => 'suggest'
                )
            )
        )
    )
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $additionalColumns);

// Add palette
$GLOBALS['TCA']['pages']['palettes']['tx_ttnews_cat_storage'] = array(
    'showitem' => 'tx_ttnews_cat_storage_pid;LLL:EXT:tt_news/Resources/Private/Language/locallang_db.xlf:pages.tx_ttnews_cat_storage_pid_formlabel',
    'canNotCollapse' => 1
);

// Add to "normal" pages, "external URL", "shortcut page" and "storage PID"
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages',
    '--palette--;LLL:EXT:tt_news/Resources/Private/Language/locallang_db.xlf:pages.palettes.tx_ttnews_cat_storage;tx_ttnews_cat_storage',
    \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_DEFAULT . ','
    . \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK . ','
    . \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SHORTCUT . ','
    . \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SYSFOLDER,
    'after:media'
);
