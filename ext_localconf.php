<?php
defined('TYPO3_MODE') or die();

$boot = function () {
    /**
     * Register Datahandler hooks:
     */
    // this hook is used to prevent saving of news or category records which have categories assigned that are not allowed for the current BE user.
    // The list of allowed categories can be set with 'tt_news_cat.allowedItems' in user/group TSconfig.
    // This check will be disabled until 'options.useListOfAllowedItems' (user/group TSconfig) is set to a value.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tt_news'] =
        \RG\TtNews\Hooks\DataHandlerHook::class;

    // this hook is used to prevent saving of a news record that has non-allowed categories assigned when a command is executed (modify,copy,move,delete...).
    // it checks if the record has an editlock. If true, nothing will not be saved.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['tt_news'] =
        \RG\TtNews\Hooks\DataHandlerHook::class;

    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
      plugin.tt_news = USER
      plugin.tt_news {
        userFunc = ' . \RG\TtNews\Plugin\TtNews::class . '->main_news
    
        # validate some configuration values and display a message if errors have been found
        enableConfigValidation = 1
      }
    ');

    // add default rendering for pi_layout plugin
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'tt_news',
        'setup',
        'tt_content.list.20.9 =< plugin.tt_news',
        'defaultContentRendering'
    );
    if (TYPO3_MODE === 'BE') {
        // Apply PageTSconfig
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:tt_news/Configuration/PageTS/PageTs.ts">'
        );

    // Page module hook
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['9']['tt_news'] = \RG\TtNews\Hooks\PageModuleHook::class . '->getExtensionSummary';
    }


    if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache'] = array(
            'backend' => TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
            'frontend' => TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class
        );
    }

    // register news cache table for "clear all caches"
    $GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearAllCache_additionalTables']['tt_news_cache'] = 'tt_news_cache';


    // in order to make "direct Preview links" for tt_news work again in TYPO3 >= 6, unset pageNotFoundOnCHashError if a BE_USER is logged in
    $configuredCookieName = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName']);
    if (empty($configuredCookieName)) {
        $configuredCookieName = 'be_typo_user';
    }
    if ($_COOKIE[$configuredCookieName]) {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError'] = 0;
    }

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\RG\TtNews\Form\FormDataProvider::class] = array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
        )
    );

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['migrateImagesToFal'] = \RG\TtNews\Update\migrateImagesToFal::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['migrateFileAttachmentsToFal'] = \RG\TtNews\Update\migrateFileAttachmentsToFal::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['tt_news_populateslugs'] = \RG\TtNews\Update\PopulateNewsSlugs::class;

    // add a dummy ValueMapper for the archive Aspect to get rid of the cHash in archive links
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['ArchiveValueMapper'] = RG\TtNews\Routing\Aspect\ArchiveValueMapper::class;

};

$boot();
unset($boot);
