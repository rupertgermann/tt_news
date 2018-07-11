<?php

/*
 * Copyright notice
 *
 * (c) 2004-2018 Rupert Germann <rupi@gmx.li>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

namespace RG\TtNews;

/**
 * Function for adding new display items to tt_news in the backend
 *
 * $Id$
 *
 */

/**
 * 'itemsProcFunc' for the 'tt_news' extension.
 *
 */
class ItemsProcFunc
{
    /**
     * insert 'codes', found in the ['what_to_display'] array to the selector in the BE.
     *
     * @param    array $config : extension configuration array
     *
     * @return    array        $config array with extra codes merged in
     */
    public function user_insertExtraCodes($config)
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['what_to_display'])) {
            $config['items'] = array_merge(
                $config['items'],
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['what_to_display']
            );
        }

        return $config;
    }
}
