<?php
defined('TYPO3_MODE') or die();

$boot = function () {

// get extension configuration
$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);


// allow news and news-category records on normal pages
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tt_news_cat');
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tt_news');


// initalize "context sensitive help" (csh)
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tt_news',
        'EXT:tt_news/csh/locallang_csh_ttnews.php');
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tt_news_cat',
        'EXT:tt_news/csh/locallang_csh_ttnewscat.php');
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('xEXT_tt_news',
        'EXT:tt_news/csh/locallang_csh_manual.xml');
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_txttnewsM1',
        'EXT:tt_news/csh/locallang_csh_mod_newsadmin.xml');


    if (TYPO3_MODE == 'BE') {
        if ($confArr['showBackEndModule']) {
            TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
                'web',
                'txttnewsM1',
                '',
                null,
                [
                    'routeTarget' => RG\TtNews\Module\NewsAdminModule::class . '::mainAction',
                    'access' => 'user,group',
                    'name' => 'web_txttnewsM1',
                    'navigationComponentId' => 'typo3-pagetree',
                    'icon' => 'EXT:tt_news/Classes/Module/moduleicon.gif',

                    'labels' => [

                        'll_ref' => 'LLL:EXT:tt_news/Classes/Module/locallang_mod.xml',
                    ],
                ]
            );
        }

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables'][$_EXTKEY][0]['fList'] = 'uid,title,author,category,datetime,archivedate,tstamp';
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables'][$_EXTKEY][0]['icon'] = true;

        // Register context menu for the tt_news category manager
        $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
            'name' => \RG\TtNews\ClickMenu::class
        );

        /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        $iconRegistry->registerIcon(
            'apps-pagetree-folder-contains-news',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            array(
                'source' => 'EXT:tt_news/Resources/Public/Images/Icons/ext_icon_ttnews_folder.gif',
            )
        );
        $iconRegistry->registerIcon(
            'ttnews-content-element-wizard-icon',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            array(
                'source' => 'EXT:tt_news/Resources/Public/Images/ContentElementWizardIcon.gif',
            )
        );

        $iconRegistry->registerIcon(
            'tt-news',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            array(
                'source' => 'EXT:tt_news/Resources/Public/Images/Icons/ext_icon.gif',
            )
        );
        $iconRegistry->registerIcon(
            'tt-news-article',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            array(
                'source' => 'EXT:tt_news/Resources/Public/Images/Icons/tt_news_article.gif',
            )
        );
        $iconRegistry->registerIcon(
            'tt-news-exturl',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            array(
                'source' => 'EXT:tt_news/Resources/Public/Images/Icons/tt_news_exturl.gif',
            )
        );

        // add folder icon
        $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-tt_news'] = 'apps-pagetree-folder-contains-news';
        $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = array(
            0 => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news',
            1 => 'tt_news',
            2 => 'apps-pagetree-folder-contains-news'
        );

        // Register HTML template for the tt_news BackEnd Module
        $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_ttnews_admin.html'] = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tt_news') . 'mod1/mod_ttnews_admin.html';

    }
};

$boot();
unset($boot);

