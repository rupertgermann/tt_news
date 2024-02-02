<?php

namespace RG\TtNews\Helper;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2020 Rupert Germann <rupi@gmx.li>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use Doctrine\DBAL\DBALException;
use RG\TtNews\Database\Database;
use RG\TtNews\Plugin\TtNews;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * tt_news helper functions
 *
 *
 * @author     Rupert Germann <rupi@gmx.li>
 */
class Helpers
{
    /**
     * @var TtNews
     */
    public $pObj;

    public function __construct(&$pObj)
    {
        $this->pObj = &$pObj;
    }

    /**
     * checks for each field of a list of items if it exists in the tt_news table and returns the validated fields
     *
     * @param    string $fieldlist : a list of fields to ckeck
     *
     * @param           $existingFields
     *
     * @return    string        the list of validated fields
     */
    public function validateFields($fieldlist, $existingFields)
    {
        $checkedFields = [];
        $fArr = GeneralUtility::trimExplode(',', $fieldlist, 1);
        foreach ($fArr as $fN) {
            if (in_array($fN, $existingFields)) {
                $checkedFields[] = $fN;
            }
        }
        $checkedFieldlist = implode(',', $checkedFields);

        return $checkedFieldlist;
    }

    /**
     * Checks the visibility of a list of category-records
     *
     * @param    string $recordlist : comma seperated list of category uids
     *
     * @return    string        $clearedlist: the cleared list
     */
    public function checkRecords($recordlist)
    {
        $clearedlist = '';
        if ($recordlist) {
            $tempRecs = GeneralUtility::trimExplode(',', $recordlist, 1);
            // debug($temp);
            $newtemp = [];
            foreach ($tempRecs as $val) {
                if ($val === '0') {
                    $this->pObj->nocat = true;
                }
                $val = (int)$val;
                if ($val) {
                    $test = $GLOBALS['TSFE']->sys_page->checkRecord(
                        'tt_news_cat',
                        $val,
                        1
                    ); // test, if the record is visible
                    if ($test) {
                        $newtemp[] = $val;
                    }
                }
            }

            if (!count($newtemp)) {
                // select category 'null' if no visible category was found
                $newtemp[] = 'null';
            }
            $clearedlist = implode(',', $newtemp);
        }

        return $clearedlist;
    }

    /**
     * Searches the category rootline (up) for a single view pid. If nothing is found in the current
     * category, the single view pid of the parent categories is taken (recusivly).
     *
     * @param int $currentCategory : Uid of the current category
     *
     * @return int first found single view pid
     * @throws DBALException
     */
    public function getRecursiveCategorySinglePid($currentCategory)
    {
        $result = null;
        $res = Database::getInstance()->exec_SELECTquery(
            'uid,parent_category,single_pid',
            'tt_news_cat',
            'tt_news_cat.uid=' . $currentCategory . $this->pObj->SPaddWhere . $this->pObj->enableCatFields
        );
        $row = Database::getInstance()->sql_fetch_assoc($res);
        if ($row['single_pid'] > 0) {
            $result = $row['single_pid'];
        } elseif ($row['parent_category'] > 0) {
            $result = $this->getRecursiveCategorySinglePid($row['parent_category']);
        }

        return $result;
    }

    /**
     * extends a given list of categories by their subcategories. This function returns a nested array with
     * subcategories (the function getSubCategories() return only a commaseparated list of category UIDs)
     *
     * @param    string  $catlist : list of categories which will be extended by subcategories
     * @param    string  $fields  : list of fields for the query
     * @param    string  $addWhere      :
     * @param    int        $cc: counter to detect recursion in nested categories
     *
     * @return    array        all categories in a nested array
     * @throws DBALException
     */
    public function getSubCategoriesForMenu($catlist, $fields, $addWhere, $cc = 0)
    {
        $pcatArr = [];

        $from_table = 'tt_news_cat';
        $where_clause = 'tt_news_cat.parent_category IN (' . $catlist . ')' . $this->pObj->SPaddWhere . $this->pObj->enableCatFields;
        $orderBy = 'tt_news_cat.' . $this->pObj->config['catOrderBy'];

        $res = Database::getInstance()->exec_SELECTquery(
            $fields,
            $from_table,
            $where_clause,
            '',
            $orderBy
        );

        while (($row = Database::getInstance()->sql_fetch_assoc($res))) {
            $cc++;
            if ($cc > 10000) {
                /** @var TimeTracker $timeTracker */
                $timeTracker = GeneralUtility::makeInstance(TimeTracker::class);
                $timeTracker->setTSlogMessage('tt_news: one or more recursive categories where found');

                return $pcatArr;
            }
            $subcats = $this->getSubCategoriesForMenu($row['uid'], $fields, $addWhere, $cc);
            $pcatArr[] = is_array($subcats) ? array_merge($row, $subcats) : '';
        }

        return $pcatArr;
    }

    /**
     * divides the bodytext field of a news single view to pages and returns the part of the bodytext
     * that is choosen by piVars[$pointerName]
     *
     * @param    string $bodytext the text with 'pageBreakTokens' in it
     * @param    array  $lConf    config array for the single view
     *
     * @return    array        the current bodytext part wrapped with stdWrap
     */
    public function makeMultiPageSView($bodytext, $lConf)
    {
        $pointerName = $this->pObj->config['singleViewPointerName'];
        $pagenum = $this->pObj->piVars[$pointerName] ?: 0;
        $textArr = GeneralUtility::trimExplode(
            $this->pObj->config['pageBreakToken'],
            $bodytext,
            1
        );
        $pagecount = is_countable($textArr) ? count($textArr) : 0;
        $pagebrowser = '';
        // render a pagebrowser for the single view
        if ($pagecount > 1) {
            // configure pagebrowser vars
            $this->pObj->internal['res_count'] = $pagecount;
            $this->pObj->internal['results_at_a_time'] = 1;
            $this->pObj->internal['maxPages'] = $this->pObj->conf['pageBrowser.']['maxPages'];
            if (!$this->pObj->conf['pageBrowser.']['showPBrowserText']) {
                $this->pObj->LOCAL_LANG[$this->pObj->LLkey]['pi_list_browseresults_page'] = ' ';
            }
            $pbConf = $this->pObj->conf['singleViewPageBrowser.'];
            $markerArray = [];
            $markerArray = $this->pObj->getPagebrowserContent($markerArray, $pbConf, $pointerName);
            $pagebrowser = $markerArray['###BROWSE_LINKS###'];
        }

        return [
            $this->pObj->formatStr($this->pObj->local_cObj->stdWrap($textArr[$pagenum], $lConf['content_stdWrap.'])),
            $pagebrowser,
        ];
    }

    /**
     * Converts the piVars 'pS' and 'pL' to a human readable format which will be filled to
     * the piVars 'year' and 'month'.
     */
    public function convertDates()
    {
        //readable archivedates
        if (($this->pObj->piVars['year'] ?? false) || ($this->pObj->piVars['month'] ?? false)) {
            $this->pObj->arcExclusive = 1;
        }
        if (!($this->pObj->piVars['year'] ?? false) && ($this->pObj->piVars['pS'] ?? false)) {
            $this->pObj->piVars['year'] = date('Y', (int)($this->pObj->piVars['pS'] ?? 0));
        }
        if (!($this->pObj->piVars['month'] ?? false) && ($this->pObj->piVars['pS'] ?? false)) {
            $this->pObj->piVars['month'] = date('m', (int)($this->pObj->piVars['pS'] ?? 0));
        }
        if (!($this->pObj->piVars['day'] ?? false) && ($this->pObj->piVars['pS'] ?? false)) {
            $this->pObj->piVars['day'] = date('j', (int)($this->pObj->piVars['pS'] ?? 0));
        }
        if (($this->pObj->piVars['year'] ?? false) || ($this->pObj->piVars['month'] ?? false) || ($this->pObj->piVars['day'] ?? false)) {
            $mon = (int)(($this->pObj->piVars['month'] ?? false) ?: 1);
            $day = (int)(($this->pObj->piVars['day'] ?? false) ?: 1);

            $this->pObj->piVars['pS'] = mktime(0, 0, 0, $mon, $day, (int)($this->pObj->piVars['year'] ?? 0));

            switch ($this->pObj->config['archiveMode']) {
                case 'month':
                    $this->pObj->piVars['pL'] = mktime(
                        0,
                        0,
                        0,
                        $mon + 1,
                        1,
                        (int)($this->pObj->piVars['year'] ?? 0)
                    ) - (int)($this->pObj->piVars['pS'] ?? 0) - 1;
                    break;
                case 'quarter':
                    $this->pObj->piVars['pL'] = mktime(
                        0,
                        0,
                        0,
                        $mon + 3,
                        1,
                        (int)($this->pObj->piVars['year'] ?? 0)
                    ) - (int)($this->pObj->piVars['pS'] ?? 0) - 1;
                    break;
                case 'year':
                    $this->pObj->piVars['pL'] = mktime(
                        0,
                        0,
                        0,
                        1,
                        1,
                        (int)($this->pObj->piVars['year'] ?? 0) + 1
                    ) - (int)($this->pObj->piVars['pS'] ?? 0) - 1;
                    unset($this->pObj->piVars['month']);
                    break;
            }
        }
    }

    /**
     * inserts pagebreaks after a certain amount of words
     *
     * @param    string  $text              text which can contain manully inserted 'pageBreakTokens'
     * @param    int $firstPageWordCrop amount of words in the subheader (short). The length of the first page will
     *                                      be reduced by that amount of words added to the value of
     *                                      $this->conf['cropWordsFromFirstPage'].
     *
     * @return    string        the processed text
     */
    public function insertPagebreaks($text, $firstPageWordCrop)
    {
        $text = str_replace(['</p>'], ['</p>' . chr(10)], $text);
        $paragraphToken = chr(10);

        $paragraphs = explode($paragraphToken, $text); // get paragraphs
        $wtmp = [];
        $firstPageCrop = $firstPageWordCrop + (int)($this->pObj->conf['cropWordsFromFirstPage']);
        $cc = 0; // wordcount
        $isfirst = true; // first paragraph
        foreach ($paragraphs as $k => $p) {
            if (trim($paragraphs[$k + 1]) == '&nbsp;') {
                unset($paragraphs[$k + 1]);
            }
            if (!isset($paragraphs[$k + 2])) {
                if ($paragraphs[$k + 1] && strlen($paragraphs[$k + 1]) < 20) {
                    $p .= $paragraphs[$k + 1];
                }
            }
            if (!isset($paragraphs[$k + 1]) && strlen($p) < 20) { // last paragraph shorter than 20 chars was already added to previous paragraph
                continue;
            }

            $words = explode(' ', $p); // get words
            $pArr = [];
            $break = false;

            foreach ($words as $w) {
                $fpc = ($isfirst && !$this->pObj->conf['subheaderOnAllSViewPages'] ? $firstPageCrop : 0);
                $wc = $this->pObj->config['maxWordsInSingleView'] - $fpc;
                if (strpos($w, (string)$this->pObj->config['pageBreakToken'])) { // manually inserted pagebreaks, unset counter
                    $cc = 0;
                    $pArr[] = $w;
                    $isfirst = false;
                } elseif ($cc >= MathUtility::forceIntegerInRange(
                    $wc,
                    0,
                    $this->pObj->config['maxWordsInSingleView']
                )) { // more words than maxWordsInSingleView
                    if (GeneralUtility::inList('.,!,?', substr($w, -1))) {
                        if ($this->pObj->conf['useParagraphAsPagebreak']) { // break at paragraph
                            $break = true;
                            $pArr[] = $w;
                            //							$pArr[] = '<b> '.$cc.' </b>';
                        } else { // break at dot and ? and !
                            $pArr[] = $w . $this->pObj->config['pageBreakToken'];
                            //							$pArr[] = '<b> '.$cc.' </b>';
                        }
                        $cc = 0;
                    } else {
                        $pArr[] = $w;
                    }
                    $isfirst = false;
                } else {
                    $pArr[] = $w;
                }
                $cc++;
            }
            if ($break) { // add break at end of current paragraph
                array_push($pArr, $this->pObj->config['pageBreakToken']);
            }
            $wtmp[] = implode(' ', $pArr);
        }
        $processedText = implode(chr(10), $wtmp);

        return $processedText;
    }

    /**
     * returns an error message if some important settings are missing (template file, singlePid, pidList, code)
     *
     * @return    string        the error message
     */
    public function displayErrors()
    {
        $msg = '';
        if (count($this->pObj->errors) >= 2) {
            $msg = '--> Did you include the static TypoScript template (\'News settings\') for tt_news?';
        }

        return '<div style="border:2px solid red; padding:10px; margin:10px;"><img src="typo3conf/ext/tt_news/Resources/Public/Images/Icons/warning.png"  alt=""/>
				<strong>plugin.tt_news ERROR:</strong><br />' . implode(
            '<br /> ',
            $this->pObj->errors
        ) . '<br />' . $msg . '</div>';
    }

    /**
     * cleans the content for rss feeds. removes '&nbsp;' and '?;' (dont't know if the scond one matters in real-life).
     * The rest of the cleaning/character-conversion is done by the stdWrap functions htmlspecialchars,stripHtml and
     * csconv. For details see http://typo3.org/documentation/document-library/doc_core_tsref/stdWrap/
     *
     * @param    string $str : input string to clean
     *
     * @return    string        the cleaned string
     */
    public function cleanXML($str)
    {
        $cleanedStr = preg_replace(['/&nbsp;/', '/&;/', '/</', '/>/'], [' ', '&amp;;', '&lt;', '&gt;'], $str);

        return $cleanedStr;
    }

    /**
     * Generates the date format needed for Atom feeds
     * see: http://www.w3.org/TR/NOTE-datetime (same as ISO 8601)
     * in php5 it would be so easy: date('c', $row['datetime']);
     *
     * @param    int $datetime the datetime value to be converted to w3c format
     *
     * @return    string        datetime in w3c format
     */
    public function getW3cDate($datetime)
    {
        $offset = date('Z', $datetime) / 3600;
        if ($offset < 0) {
            $offset *= -1;
            if ($offset < 10) {
                $offset = '0' . $offset;
            }
            $offset = '-' . $offset;
        } elseif ($offset == 0) {
            $offset = '+00';
        } elseif ($offset < 10) {
            $offset = '+0' . $offset;
        } else {
            $offset = '+' . $offset;
        }

        return date('Y-m-d\TH:i:s', $datetime) . $offset . ':00';
    }
}
