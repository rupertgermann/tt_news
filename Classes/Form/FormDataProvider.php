<?php

namespace RG\TtNews\Form;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;


/**
 * Fill the news records with default values
 * taken from the News extension by Georg Ringer
 */
class FormDataProvider implements FormDataProviderInterface
{

    /**
     * @param array $result
     *
     * @return array
     */
    public function addData(array $result)
    {
        if ($result['command'] !== 'new' || $result['tableName'] !== 'tt_news') {
            return $result;
        }

        $result['databaseRow']['datetime'] = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');

        return $result;
    }

}