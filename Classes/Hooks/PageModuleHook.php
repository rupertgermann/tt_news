<?php

namespace RG\TtNews\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Dmitry Dulepov <dmitry@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Hook to display verbose information about pi1 plugin in Web>Page module
 *
 * @author        Dmitry Dulepov <dmitry@typo3.org>
 * @package       TYPO3
 * @subpackage    tx_tt_news
 */
class PageModuleHook
{
    /**
     * Returns information about this extension's pi1 plugin
     *
     * @param    array  $params Parameters to the hook
     * @param    object $pObj   A reference to calling object
     *
     * @return    string        Information about pi1 plugin
     */
    function getExtensionSummary($params, &$pObj)
    {
        $result = '';
        if ($params['row']['list_type'] == 9) {
            $data = GeneralUtility::xml2array($params['row']['pi_flexform']);
            if (is_array($data) && $data['data']['sDEF']['lDEF']['what_to_display']['vDEF']) {
                $result = sprintf($GLOBALS['LANG']->sL('LLL:EXT:tt_news/Resources/Private/Language/locallang.xml:cms_layout.mode'),
                    $data['data']['sDEF']['lDEF']['what_to_display']['vDEF']);
            }
            if (!$result) {
                $result = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/Resources/Private/Language/locallang.xml:cms_layout.not_configured');
            }
        }

        return $result;
    }
}


