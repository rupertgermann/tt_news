<?php
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Information\Typo3Version;
use RG\TtNews\Module\NewsAdminModule;
use RG\TtNews\Menu\ClickMenu;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
defined('TYPO3') or die();

$boot = function () {

// get extension configuration
    $confArr = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tt_news'];


// allow news and news-category records on normal pages
    ExtensionManagementUtility::allowTableOnStandardPages('tt_news_cat');
    ExtensionManagementUtility::allowTableOnStandardPages('tt_news');


// initalize "context sensitive help" (csh)
    ExtensionManagementUtility::addLLrefForTCAdescr('tt_news',
        'EXT:tt_news/csh/locallang_csh_ttnews.php');
    ExtensionManagementUtility::addLLrefForTCAdescr('tt_news_cat',
        'EXT:tt_news/csh/locallang_csh_ttnewscat.php');
    ExtensionManagementUtility::addLLrefForTCAdescr('xEXT_tt_news',
        'EXT:tt_news/csh/locallang_csh_manual.xml');
    ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_txttnewsM1',
        'EXT:tt_news/csh/locallang_csh_mod_newsadmin.xml');

    $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);


    if (TYPO3_MODE == 'BE') {

        if ($typo3Version->getMajorVersion() < 11) {

            if ($confArr['showBackEndModule']) {
                ExtensionManagementUtility::addModule(
                    'web',
                    'txttnewsM1',
                    '',
                    null,
                    [
                        'routeTarget' => NewsAdminModule::class . '::mainAction',
                        'access' => 'user,group',
                        'name' => 'web_txttnewsM1',
                        'navigationComponentId' => 'typo3-pagetree',
                        'icon' => 'EXT:tt_news/Classes/Module/moduleicon.svg',

                        'labels' => [

                            'll_ref' => 'LLL:EXT:tt_news/Classes/Module/locallang_mod.xml',
                        ],
                    ]
                );

                // Register HTML template for the tt_news BackEnd Module
                $GLOBALS['TBE_STYLES']['htmlTemplates']['mod_ttnews_admin.html'] = ExtensionManagementUtility::extPath('tt_news') . 'mod1/mod_ttnews_admin.html';
            }
        }


        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tt_news'][0]['fList'] = 'uid,title,author,category,datetime,archivedate,tstamp';
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tt_news'][0]['icon'] = true;

        // Register context menu for the tt_news category manager
        $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
            'name' => ClickMenu::class
        );

        /** @var IconRegistry $iconRegistry */
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        $iconRegistry->registerIcon(
            'apps-pagetree-folder-contains-news',
            BitmapIconProvider::class,
            array(
                'source' => 'EXT:tt_news/Resources/Public/Images/Icons/ext_icon_ttnews_folder.gif',
            )
        );
        $iconRegistry->registerIcon(
            'ttnews-content-element-wizard-icon',
            BitmapIconProvider::class,
            array(
                'source' => 'EXT:tt_news/Resources/Public/Images/ContentElementWizardIcon.gif',
            )
        );

        $iconRegistry->registerIcon(
            'tt-news',
            BitmapIconProvider::class,
            array(
                'source' => 'EXT:tt_news/Resources/Public/Images/Icons/ext_icon.gif',
            )
        );
        $iconRegistry->registerIcon(
            'tt-news-article',
            BitmapIconProvider::class,
            array(
                'source' => 'EXT:tt_news/Resources/Public/Images/Icons/tt_news_article.gif',
            )
        );
        $iconRegistry->registerIcon(
            'tt-news-exturl',
            BitmapIconProvider::class,
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


    }

};

$boot();
unset($boot);

