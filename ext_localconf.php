<?php

use RG\TtNews\Form\FormDataProvider;
use RG\TtNews\Hooks\DataHandlerHook;
use RG\TtNews\Plugin\TtNews;
use RG\TtNews\Routing\Aspect\ArchiveValueMapper;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

$boot = function () {
    /**
     * Register Datahandler hooks
     */

    // This hook is used to prevent saving of news or category records which have categories assigned that are not allowed for the current BE user.
    // The list of allowed categories can be set with 'tt_news_cat.allowedItems' in user/group TSconfig.
    // This check will be disabled until 'options.useListOfAllowedItems' (user/group TSconfig) is set to a value.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tt_news'] = DataHandlerHook::class;

    // This hook is used to prevent saving of a news record that has non-allowed categories assigned when a command is executed (modify,copy,move,delete...).
    // It checks if the record has an editlock. If true, nothing will not be saved.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['tt_news'] = DataHandlerHook::class;

    /**
     * Plugin & TypoScript Configuration
     */
    ExtensionManagementUtility::addTypoScriptSetup(trim('
        plugin.tt_news = USER
        plugin.tt_news {
            userFunc = ' . TtNews::class . '->main_news

            # validate some configuration values and display a message if errors have been found
            enableConfigValidation = 1
        }
    '));

    // Register the tt_news page title provider
    ExtensionManagementUtility::addTypoScriptSetup(trim('
        config.pageTitleProviders {
            ttnews {
                provider = RG\TtNews\PageTitle\TtNewsPageTitleProvider
                before = altPageTitle,record,seo
            }
        }
    '));

    // Default rendering definition for tt_news content elements
    ExtensionManagementUtility::addTypoScript(
        'tt_news',
        'setup',
        'tt_content.tt_news =< lib.contentElement
         tt_content.tt_news.templateName = Generic
         tt_content.tt_news.20 =< plugin.tt_news',
        'defaultContentRendering'
    );

    /**
     * Cache Configuration
     */
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache'] ??= [
        'backend' => Typo3DatabaseBackend::class,
        'frontend' => VariableFrontend::class,
        'groups' => ['pages', 'all'],
    ];

    /**
     * FormEngine & Routing
     */

    // Extends FormEngine data processing for tt_news specific record handling in the backend
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][FormDataProvider::class] = [
        'depends' => [
            DatabaseRowInitializeNew::class,
        ],
    ];

    // Add a dummy ValueMapper for the archive Aspect to get rid of the cHash in archive links
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['ArchiveValueMapper'] = ArchiveValueMapper::class;
};

$boot();
unset($boot);
