<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2004 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  (c) 2004-2009 Rupert Germann (rupi@gmx.li)
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
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
/**
 * Class that adds an entry to the "create new contentelement" wizard.
 *
 * $Id$
 *
 * @author Rupert Germann <rupi@gmx.li>
 */


/**
 * Class that adds the wizard icon.
 *
 * @author Rupert Germann <rupi@gmx.li>
 */
class TtNewsWizicon
{

    /**
     * Adds the newloginbox wizard icon
     *
     * @param    array        Input array with wizard items for plugins
     *
     * @return    array        Modified input array, having the item for newloginbox added.
     */
    function proc($wizardItems)
    {
        global $LANG;

        $LL = $this->includeLocalLang();

        $wizardItems['plugins_tx_ttnews_pi'] = array(
            'icon' => TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tt_news') . 'pi/ce_wiz.gif',
            'title' => $LANG->getLLL('pi_title', $LL),
            'description' => $LANG->getLLL('pi_plus_wiz_description', $LL),
            'params' => '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=9'
        );

        return $wizardItems;
    }

    /**
     * Includes the locallang file for the 'tt_news' extension
     *
     * @return    array        The LOCAL_LANG array
     */
    public function includeLocalLang()
    {
        $llFile = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tt_news') . 'Resources/Private/Language/locallang.xml';
        $parser = new \TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser();

        return $parser->getParsedData($llFile, $GLOBALS['LANG']->lang);
    }
}



