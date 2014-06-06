<?php

/**
 * $Id: ext_localconf.php 60835 2012-04-18 10:54:35Z rupi $
 */

if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

/**
* Register hooks in TCEmain:
*/

	// this hook is used to prevent saving of news or category records which have categories assigned that are not allowed for the current BE user.
	// The list of allowed categories can be set with 'tt_news_cat.allowedItems' in user/group TSconfig.
	// This check will be disabled until 'options.useListOfAllowedItems' (user/group TSconfig) is set to a value.
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tt_news'] = 'EXT:tt_news/lib/class.tx_ttnews_tcemain.php:tx_ttnews_tcemain';

	// this hook is used to prevent saving of a news record that has non-allowed categories assigned when a command is executed (modify,copy,move,delete...).
	// it checks if the record has an editlock. If true, nothing will not be saved.
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['tt_news'] = 'EXT:tt_news/lib/class.tx_ttnews_tcemain.php:tx_ttnews_tcemain_cmdmap';



$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);

if (t3lib_extMgm::isLoaded('version')) {
	// If the extension "version" is loaded, this line adds the code VERSION_PREVIEW to the "what_to_display" section in the tt_news content element
	$TYPO3_CONF_VARS['EXTCONF']['tt_news']['what_to_display'][] = array('Preview of non-public article versions (VERSION_PREVIEW)', 'VERSION_PREVIEW');
}

// Page module hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['9']['tt_news'] = 'EXT:tt_news/lib/class.tx_ttnews_cms_layout.php:tx_ttnews_cms_layout->getExtensionSummary';

// Fix for template file name created with older versions
$TYPO3_CONF_VARS['SC_OPTIONS']['tce']['formevals']['tx_ttnews_templateeval'] = 'EXT:tt_news/lib/class.tx_ttnews_templateeval.php';


// register Ajax scripts
$TYPO3_CONF_VARS['FE']['eID_include']['tt_news'] = 'EXT:tt_news/pi/fe_index.php';
$TYPO3_CONF_VARS['BE']['AJAX']['txttnewsM1::expandCollapse'] = t3lib_extMgm::extPath('tt_news').'mod1/index.php:tx_ttnews_module1->ajaxExpandCollapse';
$TYPO3_CONF_VARS['BE']['AJAX']['txttnewsM1::loadList'] = t3lib_extMgm::extPath('tt_news').'mod1/index.php:tx_ttnews_module1->ajaxLoadList';
$TYPO3_CONF_VARS['BE']['AJAX']['tceFormsCategoryTree::expandCollapse'] = t3lib_extMgm::extPath('tt_news').'lib/class.tx_ttnews_TCAform_selectTree.php:tx_ttnews_TCAform_selectTree->ajaxExpandCollapse';


if (function_exists('t3lib_utility_VersionNumber::convertVersionNumberToInteger')) {
	$t3version = t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version);
} else {
    $t3version = t3lib_div::int_from_ver(TYPO3_version);
}

if ($t3version < 4006000) {
	// caching framework configuration
	if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache'])) {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache'] = array(
			'frontend' => 't3lib_cache_frontend_StringFrontend',
			'backend' => 't3lib_cache_backend_DbBackend',
			'options' => array(
				'cacheTable' => 'tt_news_cache',
				'tagsTable' => 'tt_news_cache_tags'
			)
		);
	}
} else {
	// caching framework configuration
	if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache'])) {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache'] = array(
			'frontend' => 't3lib_cache_frontend_StringFrontend',
			'backend' => 't3lib_cache_backend_DbBackend',
			'options' => array()
		);
	}
}
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache']['frontend'] = 't3lib_cache_frontend_VariableFrontend';

// register news cache table for "clear all caches"
if ($confArr['cachingMode']=='normal') {
	$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearAllCache_additionalTables']['tt_news_cache'] = 'tt_news_cache';
}


?>