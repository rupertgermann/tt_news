<?php
if (!defined("TYPO3_MODE")) {
    die ("Access denied.");
}

/**
 * Register hooks in TCEmain:
 */

// this hook is used to prevent saving of news or category records which have categories assigned that are not allowed for the current BE user.
// The list of allowed categories can be set with 'tt_news_cat.allowedItems' in user/group TSconfig.
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tt_news'] = \RG\TtNews\DatahandlerHook::class;

// this hook is used to prevent saving of a news record that has non-allowed categories assigned when a command is executed (modify,copy,move,delete...).
// it checks if the record has an editlock. If true, nothing will not be saved.
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['tt_news'] = \RG\TtNews\DatahandlerHook::class;

$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);
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

// Page module hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['9']['tt_news'] = \RG\TtNews\Hooks\PageModuleHook::class . '->getExtensionSummary';


// register Ajax scripts
$TYPO3_CONF_VARS['FE']['eID_include']['tt_news'] = 'EXT:tt_news/pi/fe_index.php';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
    'txttnewsM1::expandCollapse',
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tt_news') . 'mod1/index.php:tx_ttnews_module1->ajaxExpandCollapse',
    false
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
    'txttnewsM1::loadList',
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tt_news') . 'mod1/index.php:tx_ttnews_module1->ajaxLoadList',
    false
);

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache'] = array(
        'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend',
        'frontend' => 'TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend',
    );
}

// register news cache table for "clear all caches"
if ($confArr['cachingMode'] == 'normal') {
    $GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearAllCache_additionalTables']['tt_news_cache'] = 'tt_news_cache';
}

// in order to make "direct Preview links" for tt_news work again in TYPO3 >= 6, unset pageNotFoundOnCHashError if a BE_USER is logged in
// todo: check if still needed
$configuredCookieName = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName']);
if (empty($configuredCookieName)) {
    $configuredCookieName = 'be_typo_user';
}
if ($_COOKIE[$configuredCookieName]) {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError'] = 0;
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\RG\TtNews\FormDataProvider::class] = array(
	'depends' => array(
		\TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
	)
);
