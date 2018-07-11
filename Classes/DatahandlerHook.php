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
 * Class 'tx_ttnews_tcemain' for the tt_news extension.
 *
 * $Id$
 *
 */
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class being included by TCEmain using a hook
 *
 */
class DatahandlerHook
{
    protected $SPaddWhere;
    protected $enableCatFields;

    /**
     * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a record is saved. We use it to disable
     * saving of the current record if it has categories assigned that are not allowed for the BE user.
     *
     * @param    array  $fieldArray : The field names and their values to be processed (passed by reference)
     * @param    string $table      : The table TCEmain is currently processing
     * @param    string $id         : The records id (if any)
     * @param    object $pObj       : Reference to the parent object (TCEmain)
     *
     * @return    void
     * @access public
     */
    public function processDatamap_preProcessFieldArray(&$fieldArray, $table, $id, &$pObj)
    {
        if ($table == 'tt_news_cat' && is_int($id)) {
            // prevent moving of categories into their rootline
            $newParent = intval($fieldArray['parent_category']);

            if ($newParent && GeneralUtility::inList(Div::getSubCategories(
                $id,
                    $this->SPaddWhere . $this->enableCatFields
            ), $newParent)) {
                $sourceRec = BackendUtility::getRecord($table, $id, 'title');
                $targetRec = BackendUtility::getRecord($table, $fieldArray['parent_category'], 'title');

                $messageString = "Attempt to move category '" . $sourceRec['title'] . "' ($id) to inside of its own rootline (at category '" . $targetRec['title'] . "' ($newParent)).";

                $pObj->log($table, $id, 2, 0, 1, "processDatamap: $messageString", 1);

                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $messageString,
                    'ERROR', // the header is optional
                    FlashMessage::ERROR,
                    // the severity is optional as well and defaults to \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                    true // optional, whether the message should be stored in the session or only in the \TYPO3\CMS\Core\Messaging\FlashMessageQueue object (default is FALSE)
                );
                $this->enqueueFlashMessage($message);

                // unset fieldArray to prevent saving of the record
                $fieldArray = [];

                return;
            }
        }

        if ($table == 'tt_news') {

            // copy "type" field in localized records
            if (!is_int($id) && $fieldArray['l18n_parent']) { // record is a new localization
                $rec = BackendUtility::getRecord(
                    $table,
                    $fieldArray['l18n_parent'],
                    'type'
                ); // get "type" from parent record
                $fieldArray['type'] = $rec['type']; // set type of current record
            }

            // check permissions of assigned categories
            if (!is_int($id) || $GLOBALS['BE_USER']->isAdmin()) {
                return;
            }

            $categories = [];
            $recID = (($fieldArray['l18n_parent'] > 0) ? $fieldArray['l18n_parent'] : $id);
            // get categories from the tt_news record in db
            $cRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'uid_foreign',
                'tt_news_cat_mm, tt_news_cat',
                'uid_foreign=uid AND deleted=0 AND uid_local=' . $recID
            );

            while (($cRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($cRes))) {
                $categories[] = $cRow['uid_foreign'];
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($cRes);

            $notAllowedItems = [];

            $allowedItems = $GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.allowedItems');
            $allowedItems = $allowedItems ? GeneralUtility::intExplode(',', $allowedItems) : Div::getAllowedTreeIDs();

            $wantedCategories = GeneralUtility::intExplode(',', $fieldArray['category']);

            foreach (array_unique(array_merge($categories, $wantedCategories)) as $k) {
                $categoryId = intval($k, 10);
                if (!in_array($categoryId, $allowedItems)) {
                    $notAllowedItems[] = $categoryId;
                }
            }

            if ($notAllowedItems[0]) {
                $messageString = 'Attempt to modify a record from table tt_news without permission. Reason: The record has one or more categories assigned that are not defined in your BE usergroup (Not allowed: ' . implode($notAllowedItems) . ').';

                $pObj->log($table, $id, 2, 0, 1, "processDatamap: $messageString", 1);

                // unset fieldArray to prevent saving of the record
                $fieldArray = [];

                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $messageString,
                    'ERROR', // the header is optional
                    FlashMessage::ERROR,
                    // the severity is optional as well and defaults to \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                    true // optional, whether the message should be stored in the session or only in the \TYPO3\CMS\Core\Messaging\FlashMessageQueue object (default is FALSE)
                );

                $this->enqueueFlashMessage($message);
            }
        }
    }

    protected function enqueueFlashMessage($message)
    {
        /** @var $flashMessageService FlashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        /** @var $defaultFlashMessageQueue FlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($message);
    }

    public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $pObj)
    {
        if ($table != 'tt_news') {
            return;
        }

        // direct preview
        if (!is_numeric($id)) {
            $id = $pObj->substNEWwithIDs[$id];
        }

        if (isset($GLOBALS['_POST']['_savedokview_x']) && !$fieldArray['type'] && !$GLOBALS['BE_USER']->workspace) {
            // if "savedokview" has been pressed and current article has "type" 0 (= normal news article)
            // and the beUser works in the LIVE workspace open current record in single view
            $pagesTSC = BackendUtility::getPagesTSconfig($GLOBALS['_POST']['popViewId']); // get page TSconfig

            if ($pagesTSC['tx_ttnews.']['singlePid']) {
                $GLOBALS['_POST']['popViewId_addParams'] = ($fieldArray['sys_language_uid'] > 0 ?
                        '&L=' . $fieldArray['sys_language_uid'] : '') . '&no_cache=1&tx_ttnews[tt_news]=' . $id;
                $GLOBALS['_POST']['popViewId'] = $pagesTSC['tx_ttnews.']['singlePid'];
            }
        }
    }

    /**
     * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a command was executed
     * (copy,move,delete...). For tt_news it is used to disable saving of the current record if it has an editlock or
     * if it has categories assigned that are not allowed for the current BE user.
     *
     * @param    string $command : The TCEmain command, fx. 'delete'
     * @param    string $table   : The table TCEmain is currently processing
     * @param    string $id      : The records id (if any)
     * @param    array  $value   : The new value of the field which has been changed
     * @param    object $pObj    : Reference to the parent object (TCEmain)
     *
     * @return    void
     * @access public
     */
    public function processCmdmap_preProcess($command, &$table, &$id, $value, &$pObj)
    {
        if ($table == 'tt_news' && !$GLOBALS['BE_USER']->isAdmin()) {
            $rec = BackendUtility::getRecord($table, $id, 'editlock'); // get record to check if it has an editlock
            if ($rec['editlock']) {
                $pObj->log(
                    $table,
                    $id,
                    2,
                    0,
                    1,
                    'processCmdmap [editlock]: Attempt to ' . $command . " a record from table '%s' which is locked by an 'editlock' (= record can only be edited by admins).",
                    1,
                    [$table]
                );
                // unset table to prevent saving
                $table = '';

                return;
            }

            if (!is_int($id)) {
                return;
            }

            // get categories from the (untranslated) record in db
            $res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
                'tt_news_cat.uid',
                'tt_news',
                'tt_news_cat_mm',
                'tt_news_cat',
                ' AND tt_news_cat.deleted=0 AND tt_news_cat_mm.uid_local=' . (is_int($id) ? $id : 0) . BackendUtility::BEenableFields('tt_news_cat')
            );
            $categories = [];
            while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
                $categories[] = $row['uid'];
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);

            $notAllowedItems = [];

            $allowedItems = $GLOBALS['BE_USER']->getTSConfigVal('tt_newsPerms.tt_news_cat.allowedItems');
            $allowedItems = $allowedItems ? GeneralUtility::intExplode(',', $allowedItems) : Div::getAllowedTreeIDs();

            foreach ($categories as $k) {
                $categoryId = intval($k, 10);
                if (!in_array($categoryId, $allowedItems)) {
                    $notAllowedItems[] = $categoryId;
                }
            }

            if ($notAllowedItems[0]) {
                $messageString = 'Attempt to ' . $command . ' a record from table tt_news without permission. Reason: The record has one or more categories assigned that are not defined in your BE usergroup (Not allowed: ' . implode($notAllowedItems) . ').';

                $pObj->log($table, $id, 2, 0, 1, "processCmdmap: $messageString", 1);

                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $messageString,
                    'ERROR', // the header is optional
                    FlashMessage::ERROR,
                    // the severity is optional as well and defaults to \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                    true // optional, whether the message should be stored in the session or only in the \TYPO3\CMS\Core\Messaging\FlashMessageQueue object (default is FALSE)
                );

                $this->enqueueFlashMessage($message);

                $table = ''; // unset table to prevent saving
            }
        }
    }

    /**
     * @param             $command
     * @param             $table
     * @param             $srcId
     * @param             $destId
     * @param DataHandler $pObj
     */
    public function processCmdmap_postProcess($command, $table, $srcId, $destId, &$pObj)
    {

        // copy records recursively from Drag&Drop in the category manager
        if ($table == 'tt_news_cat' && $command == 'DDcopy') {
            $srcRec = BackendUtility::getRecordWSOL('tt_news_cat', $srcId);
            $overrideValues = ['parent_category' => $destId, 'hidden' => 1];
            $newRecID = $pObj->copyRecord($table, $srcId, $srcRec['pid'], 1, $overrideValues);
            $CPtable = $this->int_recordTreeInfo([], $srcId, 99, $newRecID, $table, $pObj);

            foreach ($CPtable as $recUid => $recParent) {
                $newParent = $pObj->copyMappingArray[$table][$recParent];
                if (isset($newParent)) {
                    $overrideValues = ['parent_category' => $newParent, 'hidden' => 1];
                    $pObj->copyRecord($table, $recUid, $srcRec['pid'], 1, $overrideValues);
                } else {
                    $pObj->log($table, $srcId, 5, 0, 1, 'Something went wrong during copying branch');
                    break;
                }
            }
        }
        // delete records recursively from Context Menu in the category manager
        if ($table == 'tt_news_cat' && $command == 'DDdelete') {
            $pObj->deleteRecord($table, $srcId, false);
            $CPtable = $this->int_recordTreeInfo([], $srcId, 99, $srcId, $table, $pObj);

            foreach ($CPtable as $recUid => $p) {
                if (isset($recUid)) {
                    $pObj->deleteRecord($table, $recUid, false);
                } else {
                    $pObj->log($table, $recUid, 5, 0, 1, 'Something went wrong during deleting branch');
                    break;
                }
            }
        }
    }

    /**
     * @param             $CPtable
     * @param             $srcId
     * @param             $counter
     * @param             $rootID
     * @param             $table
     * @param DataHandler $pObj
     *
     * @return mixed
     */
    public function int_recordTreeInfo($CPtable, $srcId, $counter, $rootID, $table, &$pObj)
    {
        if (!$counter) {
            return $CPtable;
        }

        $addW = !$pObj->admin ? ' AND ' . $pObj->BE_USER->getPagePermsClause($pObj->pMap['show']) : '';
        $mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'uid',
            $table,
            'parent_category=' . intval($srcId) . $pObj->deleteClause($table) . $addW,
            '',
            ''
        );

        while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres))) {
            if ($row['uid'] == $rootID) {
                continue;
            }

            $CPtable[$row['uid']] = $srcId;
            if ($counter - 1) { // If the uid is NOT the rootID of the copyaction and if we are supposed to walk further down
                $CPtable = $this->int_recordTreeInfo($CPtable, $row['uid'], $counter - 1, $rootID, $table, $pObj);
            }
        }

        $GLOBALS['TYPO3_DB']->sql_free_result($mres);

        return $CPtable;
    }
}
