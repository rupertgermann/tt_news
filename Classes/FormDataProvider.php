<?php
namespace RG\TtNews;
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */



/**
 * Fill the news records with default values
 * taken from the News extension by Georg Ringer
 */
class FormDataProvider implements \TYPO3\CMS\Backend\Form\FormDataProviderInterface
{

    /**
     * @param array $result
     * @return array
     */
    public function addData(array $result) {
        if ($result['command'] !== 'new' || $result['tableName'] !== 'tt_news') {
            return $result;
        }

        $result['databaseRow']['datetime'] = $GLOBALS['EXEC_TIME'];

        return $result;
    }

}