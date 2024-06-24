<?php

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * Copyright notice
 *
 *          (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
 *          All rights reserved
 *
 *          This script is part of the TYPO3 project. The TYPO3 project is
 *          free software; you can redistribute it and/or modify
 *          it under the terms of the GNU General Public License as published by
 *          the Free Software Foundation; either version 2 of the License, or
 *          (at your option) any later version.
 *
 *          The GNU General Public License can be found at
 *          http://www.gnu.org/copyleft/gpl.html.
 *          A copy is found in the textfile GPL.txt and important notices to the license
 *          from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *          This script is distributed in the hope that it will be useful,
 *          but WITHOUT ANY WARRANTY; without even the implied warranty of
 *          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *          GNU General Public License for more details.
 *
 *          This copyright notice MUST APPEAR in all copies of the script!
 */
/**
 * Creates a language-selector menu with three flags, an english, a danish and a german
 * flag for each language supported on the site.
 *
 *
 * $Id$
 *
 * @author Rupert Germann <rupi@gmx.li>
 */
/**
 * language menu that keeps the links vars from tt_news
 *
 * @param	[type]		$content: ...
 */
function user_languageMenu($content)
{
    $paramArray = [];
    // image files for deafultlanguage
    $defaultflag = ['on' => '<img src="media/uploads/flag_uk.gif" width="21" height="13" hspace="5" border="0" alt="" />', 'off' => '<img src="media/uploads/flag_uk_d.gif" width="21" height="13" hspace="5" border="0" alt="" />'];

    // image files for additional languages. the numbers are the uids of the system languages
    $flagimages = ['1' => ['on' => '<img src="media/uploads/flag_dk.gif" width="21" height="13" hspace="5" border="0" alt="" />', 'off' => '<img src="media/uploads/flag_dk_d.gif" width="21" height="13" hspace="5" border="0" alt="" />'], '2' => ['on' => '<img src="media/uploads/flag_de.gif" width="21" height="13" hspace="5" border="0" alt="" />', 'off' => '<img src="media/uploads/flag_de_d.gif" width="21" height="13" hspace="5" border="0" alt="" />']];
    // Pointer for the active language
    $pointer = '<img src="t3lib/gfx/content_client.gif" width="7" height="10" alt="" />';

    // First, select all pages_language_overlay records on the current page. Each represents a possibility for a language.
    $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages_language_overlay', 'pid=' . (int)($GLOBALS['TSFE']->id) . $GLOBALS['TSFE']->sys_page->enableFields('pages_language_overlay'), 'sys_language_uid');

    $langArr = [];
    while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
        $langArr[$row['sys_language_uid']] = $row['title'];
    }

    $queryString = explode('&', (string)GeneralUtility::implodeArrayForUrl('', $GLOBALS['_GET']));
    if ($queryString) {
        foreach ($queryString as $val) {
            $tmp = explode('=', (string)$val);
            $paramArray[$tmp[0]] = $val;
        }
        $excludeList = 'id,L,tx_ttnews[pointer]';
        foreach ($paramArray as $key => $val) {
            if (!$val || ($excludeList && GeneralUtility::inList($excludeList, $key))) {
                unset($paramArray[$key]);
            }
        }
        $tmpParams = implode('&', $paramArray);

        $newsAddParams = $tmpParams ? '&' . $tmpParams : '';
    }
    // unset the global linkVar "L" for the language menu because it's build new in this script
    $relevantParametersForCachingFromPageArguments = [];
    $pageArguments = $GLOBALS['REQUEST']->getAttribute('routing');
    $queryParams = $pageArguments->getDynamicArguments();
    if (!empty($queryParams) && ($pageArguments->getArguments()['cHash'] ?? false)) {
        $queryParams['id'] = $pageArguments->getPageId();
        $relevantParametersForCachingFromPageArguments = GeneralUtility::makeInstance(CacheHashCalculator::class)->getRelevantParameters(HttpUtility::buildQueryString($queryParams));
    }
    $linkVarsBak = $relevantParametersForCachingFromPageArguments;
    $tmplinkVars = GeneralUtility::trimExplode('&', $GLOBALS['TSFE']->linkVars);
    if ($tmplinkVars) {
        foreach ($tmplinkVars as $kl => $vl) {
            if (!$vl || preg_match('/L=[0-9]/', (string)$vl)) {
                unset($tmplinkVars[$kl]);
            }
        }
        $GLOBALS['TSFE']->linkVars = implode('&', $tmplinkVars);
    }

    $tmpLang = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id');

    $flags = [];
    // flag for the default language
    if ($GLOBALS['TSFE']->page['l18n_cfg'] == 1) { // = "Hide default Translation" is activated
        $flags[0] = $defaultflag['off'];
    } else {
        $flags[0] = ($tmpLang == 0 ? $pointer : '') . $GLOBALS['TSFE']->cObj->typolink($defaultflag['on'], ['parameter' => $GLOBALS['TSFE']->id . ' _top', 'additionalParams' => (!preg_match('/&L=[0-9]/', $newsAddParams) ? $newsAddParams . '&L=0' : $newsAddParams)]);
    }

    // flags for the additional language
    if (is_array($flagimages)) {
        foreach ($flagimages as $fk => $fv) {
            if ($langArr[$fk]) {
                $flags[$fk] = ($tmpLang == $fk ? $pointer : '') . $GLOBALS['TSFE']->cObj->typolink($fv['on'], ['parameter' => $GLOBALS['TSFE']->id . ' _top', 'additionalParams' => (!preg_match('/&L=[0-9]/', $newsAddParams) ? $newsAddParams . '&L=' . $fk : $newsAddParams)]);
            } else {
                $flags[$fk] = $fv['off'];
            }
        }
    }
    $content = '<div class="langmenu"><p>'
     . implode('', $flags) . '</p></div>';
    // restore link vars
    $GLOBALS['TSFE']->linkVars = $linkVarsBak;
    return $content;
}
