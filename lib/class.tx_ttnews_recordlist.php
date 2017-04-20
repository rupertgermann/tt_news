<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2009 Rupert Germann <rupi@gmx.li>
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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * generates the list view for the 'news admin' module
 *
 * $Id$
 *
 * @author	Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage tt_news
 */




	/**
	 * [Describe function...]
	 *
	 */
class tx_ttnews_recordlist extends \TYPO3\CMS\Backend\View\PageLayoutView {


	var $newRecPid;
	var $singlePid;
	var $selectedCategories;
	var $category;
	var $excludeCats;
	var $includeCats;
	var $isAdmin;
	var $current_sys_language;
	var $showOnlyEditable;
	var $pidList;
	var $editablePagesList;
	var $lTSprop;
	var $pObj;
	var $searchFields;


	/**********************************
	 *
	 * Generic listing of items
	 *
	 **********************************/

	protected function setTotalItemsGateway($queryArray)
	{
	    if (\version_compare(TYPO3_version, '8', '<')) {
	        $this->setTotalItems($queryArray);
	    } else {
	        // implement the old way
	        $this->totalItems = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', $queryArray['FROM'], $queryArray['WHERE']);
	    }
	}

	/**
	 * Creates a standard list of elements from a table.
	 *
	 * @param	string		Table name
	 * @param	integer		Page id.
	 * @param	string		Comma list of fields to display
	 * @param	boolean		If true, icon is shown
	 * @param	string		Additional WHERE-clauses.
	 * @return	string		HTML table
	 */
	function makeOrdinaryList($table, $id, $fList, $icon=0, $addWhere='')	{
			// Initialize:
		$out = '';
		$queryParts = $this->makeQueryArray($table, $id, $addWhere);
		$this->setTotalItemsGateway($queryParts);
		$this->eCounter = 0;

			// Make query for records if there were any records found in the count operation:
		if ($this->totalItems)	{
			$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
		}

			// If records were found, render the list:
		if (!$this->totalItems) {
            return $out;
        }

            // Set fields
        $this->fieldArray = explode(',',$fList);

            // Header line is drawn
        $theData = array();
        $theData = $this->headerFields($this->fieldArray,$table,$theData);
        if ($this->doEdit)	{
            $newRecIcon = $this->getNewRecordButton($table);
        }

        $out.= $this->addelement(1,$newRecIcon,$theData,' class="c-headLineTable"');

        $checkCategories = false;
        if (count($this->includeCats) || count($this->excludeCats)) {
            $checkCategories = true;
        }

            // Render Items
        $this->eCounter = $this->firstElementNumber;
        while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)))	{
            \TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL($table, $row);

            if (!is_array($row)) {
                continue;
            }

            $Nrow = array();
            $NrowIcon = '';
            $noEdit = $this->checkRecordPerms($row,$checkCategories);

                // Setting icons/edit links:
            if ($icon)	{
                $NrowIcon = $this->getIcon($table,$row,$noEdit);
            }

            if (!$noEdit)	{
                $params = '&edit['.$table.']['.$row['uid'].']=edit';
                $NrowIcon .= '<a href="#" onclick="'.htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params,$this->backPath,$this->returnUrl)).'">'.
                                '<img'.\WMDB\TtNews\Utility\IconUtility::skinImg($this->backPath,'gfx/edit2.gif','width="11" height="12"').' title="'.$GLOBALS['LANG']->getLL('edit',1).'" alt="" />'.
                                '</a>';
            } else {
                $NrowIcon .= $this->noEditIcon($noEdit);
            }

                // Get values:
            $Nrow = $this->dataFields($this->fieldArray,$table,$row,$Nrow,$noEdit);
            $tdparams = $this->eCounter%2 ? ' class="bgColor4"' : ' class="bgColor4-20"';
            $out.= $this->addelement(1,$NrowIcon,$Nrow,$tdparams);
            $this->eCounter++;
        }

            // Wrap it all in a table:
        $out='
            <table border="0" cellpadding="1" cellspacing="1" class="typo3-dblist">
                '.$out.'
            </table>';

                    // Record navigation is added to the beginning and end of the table if in single table mode
            if ($table) {
                $pageNavigation = $this->renderListNavigation();
                $out = $pageNavigation . $out . $pageNavigation;
            }

        return $out;
	}
	/**
	 * Creates a page browser for tables with many records
	 * (copied from class.db_list_extra_inc)
	 *
	 * @return	string	Navigation HTML
	 *
	 * @author	Dmitry Pikhno <dpi@goldenplanet.com>
	 * @author	Christian Kuhn <lolli@schwarzbu.ch>
	 */
	function renderListNavigation() {
		$totalPages = ceil($this->totalItems / $this->iLimit);

		$content = '';

			// Show page selector if not all records fit into one page
		if ($totalPages > 1) {
			$first = $previous = $next = $last = '';
			$listURL = $this->listURL('', $this->table);

				// 1 = first page
			$currentPage = floor(($this->firstElementNumber + 1) / $this->iLimit) + 1;

				// Compile first, previous, next, last and refresh buttons
			if ($currentPage > 1) {
				$labelFirst = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/mod1/locallang.xml:first');

				$first = '<a href="' . $listURL . '&pointer=0">
					<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg('', TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tt_news') . 'res/gfx/control_first.gif')
					. 'alt="' . $labelFirst . '" title="' . $labelFirst . '" />
				</a>';
			} else {
				$first = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg('', TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tt_news') . 'res/gfx/control_first_disabled.gif') . 'alt="" title="" />';
			}

			if (($currentPage - 1) > 0) {
				$labelPrevious = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/mod1/locallang.xml:previous');

				$previous = '<a href="' . $listURL . '&pointer=' . (($currentPage - 2) * $this->iLimit) . '">
					<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg('', TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tt_news') . 'res/gfx/control_previous.gif')
					. 'alt="' . $labelPrevious . '" title="' . $labelPrevious . '" />
					</a>';
			} else {
				$previous = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg('', TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tt_news') . 'res/gfx/control_previous_disabled.gif') . 'alt="" title="" />';
			}

			if (($currentPage + 1) <= $totalPages) {
				$labelNext = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/mod1/locallang.xml:next');

				$next = '<a href="' . $listURL . '&pointer=' . (($currentPage) * $this->iLimit) . '">
					<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg('', TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tt_news') . 'res/gfx/control_next.gif')
					. 'alt="' . $labelNext . '" title="' . $labelNext . '" />
					</a>';
			} else {
				$next = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg('', TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tt_news') . 'res/gfx/control_next_disabled.gif') . 'alt="" title="" />';
			}

			if ($currentPage != $totalPages) {
				$labelLast = $GLOBALS['LANG']->sL('LLL:EXT:tt_news/mod1/locallang.xml:last');

				$last = '<a href="' . $listURL . '&pointer=' . (($totalPages - 1) * $this->iLimit) . '">
					<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg('', TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tt_news') . 'res/gfx/control_last.gif')
					. 'alt="' . $labelLast . '" title="' . $labelLast . '" />
					</a>';
			} else {
				$last = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg('', TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tt_news') . 'res/gfx/control_last_disabled.gif') . 'alt="" title="" />';
			}

			$pageIndicator = sprintf($GLOBALS['LANG']->sL('LLL:EXT:tt_news/mod1/locallang.xml:pageIndicator'), $currentPage, $totalPages);

			if ($this->totalItems > ($this->firstElementNumber + $this->iLimit)) {
				$lastElementNumber = $this->firstElementNumber + $this->iLimit;
			} else {
				$lastElementNumber = $this->totalItems;
			}
			$rangeIndicator = '<span class="pageIndicator">'
				. sprintf($GLOBALS['LANG']->sL('LLL:EXT:tt_news/mod1/locallang.xml:rangeIndicator'), $this->firstElementNumber + 1, $lastElementNumber, $this->totalItems)
				. '</span>';

			$content .= '<div class="ttnewsadmin-pagination">'
				. $first . $previous
				. '&nbsp;'
				. $rangeIndicator . '&nbsp;('
				. $pageIndicator . ')&nbsp;'
				. $next . $last
				. '</div>';
		} // end of if pages > 1

		$data = Array();
		$titleColumn = $this->fieldArray[0];
		$data[$titleColumn] = $content;

		return ($this->addElement(1, '', $data));
	}

	/**
	 * Adds content to all data fields in $out array
	 *
	 * @param	array		Array of fields to display. Each field name has a special feature which is that the field name can be specified as more field names. Eg. "field1,field2;field3". Field 2 and 3 will be shown in the same cell of the table separated by <br /> while field1 will have its own cell.
	 * @param	string		Table name
	 * @param	array		Record array
	 * @param	array		Array to which the data is added
	 * @param	[type]		$noEdit: ...
	 * @return	array		$out array returned after processing.
	 * @see makeOrdinaryList()
	 */
	function dataFields($fieldArr,$table,$row,$out=array(),$noEdit=FALSE)	{
		global $TCA;

			// Check table validity:
		if (!$TCA[$table]) {
            return $out;
        }

        $thumbsCol = $TCA[$table]['ctrl']['thumbnail'];
        $url = GeneralUtility::getIndpEnv('TYPO3_SITE_URL').'index.php';
        $thumbsize = $this->lTSprop['imageSize'];

        // Traverse fields:
        foreach($fieldArr as $fieldName)	{

            if ($TCA[$table]['columns'][$fieldName])	{
                // Each field has its own cell (if configured in TCA)
                if ($fieldName==$thumbsCol)	{
                    // If the column is a thumbnail column:
                    if ($this->thumbs) {
                        $val = \TYPO3\CMS\Backend\Utility\BackendUtility::thumbCode($row,$table,$fieldName,$this->backPath,$this->thumbScript,NULL,0,'',$thumbsize);
                    } else {
                        $val = str_replace(',',', ',basename($row[$fieldName]));
                    }
                } else {
                    // ... otherwise just render the output:
                    $val = nl2br(htmlspecialchars(trim(GeneralUtility::fixed_lgd_cs(\TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue($table,$fieldName,$row[$fieldName],0,0,0,$row['uid']),250))));

                    if ($this->lTSprop['clickTitleMode'] == 'view' && $this->singlePid) {
                        $val = $this->linkSingleView($url,$val,$row['uid']);
                    } elseif ($this->lTSprop['clickTitleMode'] == 'edit' && !$noEdit) {
                        $params = '&edit['.$table.']['.$row['uid'].']=edit';
                        $lTitle = ' title="'.$GLOBALS['LANG']->getLL('edit',1).'"';
                        $val = '<a href="#" onclick="'.htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params,$this->backPath,$this->returnUrl)).'"'.$lTitle.'>'.$val.'</a>';
                    }
                }
                $out[$fieldName] = $val;
            } else {
                // Each field is separated by <br /> and shown in the same cell (If not a TCA field, then explode the field name with ";" and check each value there as a TCA configured field)
                $theFields = explode(';',$fieldName);

                // Traverse fields, separated by ";" (displayed in a single cell).
                foreach($theFields as $fName2)	{
                    if ($TCA[$table]['columns'][$fName2])	{
                         $out[$fieldName].= '<b>'.$GLOBALS['LANG']->sL($TCA[$table]['columns'][$fName2]['label'],1).'</b>'.
                                            '&nbsp;&nbsp;'.
                                            htmlspecialchars(GeneralUtility::fixed_lgd_cs(\TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue($table,$fName2,$row[$fName2],0,0,0,$row['uid']),25)).
                                            '<br />';
                    }
                }
            }

            // If no value, add a nbsp.
            if (!$out[$fieldName])	{
                $out[$fieldName]='&nbsp;';
            }

            // Wrap in dimmed-span tags if record is "disabled"
            if ($this->isDisabled($table,$row))	{
                $out[$fieldName] = $GLOBALS['TBE_TEMPLATE']->dfw($out[$fieldName]);
            }
        }

		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$url: ...
	 * @param	[type]		$val: ...
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function linkSingleView($url, $val, $uid) {
		$params = array(
				'id' => $this->singlePid,
				'tx_ttnews[tt_news]' => $uid,
				'no_cache' => 1);
		$linkedurl = GeneralUtility::linkThisUrl($url,$params);
		$onclick = 'openFePreview(\''.htmlspecialchars($linkedurl).'\');';
		$lTitle = $GLOBALS['LANG']->getLL('openFePreview',1);

        return '<a href="#" onclick="'.$onclick.'" title="'.$lTitle.'">'.$val.'</a>';
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$withLabel: ...
	 * @return	[type]		...
	 */
	function getNewRecordButton($table, $withLabel=false) {
		if ($this->category) {
			$addP = '&defVals['.$table.'][category]='.$this->category;
			$addLbl = 'InCategory';
		}

		$params = '&edit['.$table.']['.$this->newRecPid.']=new'.$addP;
		$onclick = htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params,$this->backPath,$this->returnUrl));

        return '<a href="#" onclick="'.$onclick.'">'.
            \WMDB\TtNews\Utility\IconUtility::getIconByIdentifier('actions-document-new').
			($withLabel?$GLOBALS['LANG']->getLL('createArticle'.$addLbl):'').
			'</a>';
	}



	/**
	 * Creates the icon image tag for record from table and wraps it in a link which will trigger the click menu.
	 *
	 * @param	string		Table name
	 * @param	array		Record array
	 * @param	string		Record title (NOT USED)
	 * @return	string		HTML for the icon
	 */
	function getIcon($table,$row,$noEdit = '')	{
			// Initialization
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $iconImg = $iconFactory->getIconForRecord('tt_news', $row, Icon::SIZE_SMALL)->render();

		$this->counter++;

		$disableList = '';
		if ($noEdit) {
			$disableList = '+info,copy';
		}

			// The icon with link
		return \TYPO3\CMS\Backend\Utility\BackendUtility::wrapClickMenuOnIcon($iconImg,$table,$row['uid'],'','',$disableList);
    }

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$row: ...
	 * @param	[type]		$checkCategories: ...
	 * @return	[type]		...
	 */
	function checkRecordPerms(&$row,$checkCategories)	{
		$noEdit = 1;

        if (!$this->pObj->checkPageAccess($row['pid'])) {
            return $noEdit;
        }

        // user is allowed to edit page content
        $noEdit = 0;
        if (!$checkCategories) {
            return $noEdit;
        }

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery (
            'tt_news_cat_mm.*',
            'tt_news_cat_mm, tt_news_cat',
            'tt_news_cat_mm.uid_foreign=tt_news_cat.uid AND tt_news_cat.deleted=0 AND tt_news_cat_mm.uid_local='.$row['uid']);

        while (($mmrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
            if (!in_array($mmrow['uid_foreign'],$this->includeCats) || in_array($mmrow['uid_foreign'],$this->excludeCats)) {
                $noEdit = 2;
                break;
            }
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $noEdit;
	}

	/**
	 * Returns icon for "no-edit" of a record.
	 * Basically, the point is to signal that this record could have had an edit link if the circumstances were right. A placeholder for the regular edit icon...
	 *
	 * @param	string		Label key from LOCAL_LANG
	 * @return	string		IMG tag for icon.
	 */
	function noEditIcon($reason = 'noEditItems')	{
		switch ($reason) {
			case 1:
				$label = $GLOBALS['LANG']->getLL('noEditPagePerms',1);
			break;

			case 2:
				$label = $GLOBALS['LANG']->getLL('noEditCategories',1);
			break;
            default:
                $label = '';
                break;
		}

		$img = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tt_news').'res/noedit_'.$reason.'.gif';
		return '<img'.\TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath,$img).' title="'.$label.'" alt="" />';
    }


	/**
	 * Header fields made for the listing of records
	 *
	 * @param	array		Field names
	 * @param	string		The table name
	 * @param	array		Array to which the headers are added.
	 * @return	array		$out returned after addition of the header fields.
	 * @see makeOrdinaryList()
	 */
	function headerFields($fieldArr,$table,$out=array())	{
		global $TCA;


		foreach($fieldArr as $fieldName)	{
			$ll = $GLOBALS['LANG']->sL($TCA[$table]['columns'][$fieldName]['label'],1);
			$out[$fieldName] = '<strong>'.($ll?$this->addSortLink($ll,$fieldName,$table):'&nbsp;').'</strong>';
		}

		return $out;
	}

	/**
	 * Creates a sort-by link on the input string ($code).
	 * It will automatically detect if sorting should be ascending or descending depending on $this->sortRev.
	 * Also some fields will not be possible to sort (including if single-table-view is disabled).
	 *
	 * @param	string		The string to link (text)
	 * @param	string		The fieldname represented by the title ($code)
	 * @param	string		Table name
	 * @return	string		Linked $code variable
	 */
	function addSortLink($code,$field,$table)	{

			// Certain circumstances just return string right away (no links):
		if ($field=='_CONTROL_' || $field=='_LOCALIZATION_' || $field=='_CLIPBOARD_' || $field=='_REF_' || $this->disableSingleTableView)	{
            return $code;
        }

			// If "_PATH_" (showing record path) is selected, force sorting by pid field (will at least group the records!)
		if ($field=='_PATH_')	{
            $field=pid;
        }

			//	 Create the sort link:
		$sortUrl = $this->listURL('',FALSE,'sortField,sortRev').'&sortField='.$field.'&sortRev='.($this->sortRev || ($this->sortField!=$field)?0:1);
		$sortArrow = ($this->sortField==$field?'<img'.\TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath,'gfx/red'.($this->sortRev?'up':'down').'.gif','width="7" height="4"').' alt="" />':'');

			// Return linked field:
		return '<a href="'.htmlspecialchars($sortUrl).'">'.$code.
				$sortArrow.
				'</a>';
	}

	/**
	 * Creates the URL to this script, including all relevant GPvars
	 * Fixed GPvars are id, table, imagemode, returlUrl, search_field, search_levels and showLimit
	 * The GPvars "sortField" and "sortRev" are also included UNLESS they are found in the $exclList variable.
	 *
	 * @param	string		Alternative id value. Enter blank string for the current id ($this->id)
	 * @param	string		Tablename to display. Enter "-1" for the current table.
	 * @param	string		Commalist of fields NOT to include ("sortField" or "sortRev")
	 * @return	string		URL
	 */
	function listURL($altId='',$table='',$exclList='')	{
		return \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_txttnewsM1').
			'&id='.(strcmp($altId,'')?$altId:$this->id).
			($this->thumbs?'&showThumbs='.$this->thumbs:'').
			($this->searchString?'&search_field='.rawurlencode($this->searchString):'').
			($this->searchLevels?'&searchLevels='.rawurlencode($this->searchLevels):'').
			($this->showLimit?'&showLimit='.rawurlencode($this->showLimit):'').
			($this->firstElementNumber?'&pointer='.rawurlencode($this->firstElementNumber):'').
			((!$exclList || !GeneralUtility::inList($exclList,'sortField')) && $this->sortField?'&sortField='.rawurlencode($this->sortField):'').
			((!$exclList || !GeneralUtility::inList($exclList,'sortRev')) && $this->sortRev?'&sortRev='.rawurlencode($this->sortRev):'').
			($this->category?'&category='.$this->category:'');
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$addWhere: ...
	 * @param	[type]		$fieldList: ...
	 * @return	[type]		...
	 */
	function makeQueryArray($table, $id, $addWhere="",$fieldList='')	{
		global $TCA;
		if (!$fieldList) {
			$fieldList = $table.'.*';
		}

			// Set ORDER BY:
		$orderBy = ($TCA[$table]['ctrl']['sortby']) ? 'ORDER BY '.$TCA[$table]['ctrl']['sortby'] : $TCA[$table]['ctrl']['default_sortby'];
		if ($this->sortField && in_array($this->sortField,$this->makeFieldList($table,1)))	{
            $orderBy = 'ORDER BY '.$this->sortField;

            if ($this->sortRev) {
                $orderBy .= ' DESC';
            }
		}

			// Set LIMIT:
		$limit = $this->iLimit ? ($this->firstElementNumber ? $this->firstElementNumber.',' : '').($this->iLimit) : '';

		// fix for 6.2
		$TCA[$table]['ctrl']['searchFields'] = $this->searchFields;

			// Adding search constraints:
		$search = $this->makeSearchString($table);


		if ($this->selectedCategories) {
			$mmTable = 'tt_news_cat_mm';
			$fieldList = 'DISTINCT '.$table.'.uid, '.$fieldList;
			$leftjoin = ' LEFT JOIN '.$mmTable.' AS mm1 ON '.$table.'.uid=mm1.uid_local';
		}
		$catWhere = '';
		if ($this->selectedCategories) {
			$catWhere .= ' AND mm1.uid_foreign IN ('.$this->selectedCategories.')';
		} elseif ($this->lTSprop['noListWithoutCatSelection'] && !$this->isAdmin) {
			$addWhere .= ' AND 1=0';
		}

		if ($this->searchLevels == -1) {
			$this->pidSelect = '';
		}
		$ps = ($this->pidSelect?$this->pidSelect.' AND ':'');
		if ($this->isAdmin) {
			$this->pidSelect = $ps.'1=1';
		} else {
			if ($this->showOnlyEditable) {
				$this->pidSelect = $ps.$table.'.pid IN ('.$this->editablePagesList.')';
			} else {
				$this->pidSelect = $ps.$table.'.pid IN ('.$this->pidList.')';
			}
		}
		$addWhere .= ' AND '.$table.'.sys_language_uid='.$this->current_sys_language;

		// Compiling query array:
		$queryParts = array(
			'SELECT' => $fieldList,
			'FROM' => $table.$leftjoin,
			'WHERE' => $this->pidSelect.' AND '.$table.'.pid > 0'.
						\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table).
						\TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause($table).
						' '.$addWhere.
						' '.$search.$catWhere,
			'GROUPBY' => '',//$table.'.uid',
			'ORDERBY' => $GLOBALS['TYPO3_DB']->stripOrderBy($orderBy),
			'LIMIT' => $limit
		);

		if (!$this->isAdmin && ($this->selectedCategories || !$this->lTSprop['noListWithoutCatSelection']) && $this->showOnlyEditable) {
			$queryParts = $this->ckeckDisallowedCategories($queryParts);
		}

		// Return query:
		return $queryParts;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$queryParts: ...
	 * @return	[type]		...
	 */
	function ckeckDisallowedCategories($queryParts) {
		if (count($this->excludeCats) || count($this->includeCats)) {
			// if showOnlyEditable is set, we check for each found record if it has any disallowed category assigned
			$tmpLimit = $queryParts['LIMIT'];
			unset($queryParts['LIMIT']);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
			$results = array();

            while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$results[$row['uid']] = $row['uid'];
			}

			array_unique($results);
			foreach ($results as $uid) {
				$currentCats = $this->getCategories($uid);
				foreach ($currentCats as $cat) {
					if (!in_array($cat,$this->includeCats) || in_array($cat,$this->excludeCats)) {
						unset($results[$uid]);
                        // break after one disallowed category was found
                        break;
					}
				}
			}

			$matchlist = implode(',',$results);
			if ($matchlist) {
				$queryParts['WHERE'] .= ' AND tt_news.uid IN ('.$matchlist.')';
			} else {
				$queryParts['WHERE'] .= ' AND tt_news.uid IN (0)';
			}

			$queryParts['LIMIT'] = $tmpLimit;
		}

		return $queryParts;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function getCategories($uid) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'tt_news_cat.uid',
					'tt_news_cat LEFT JOIN tt_news_cat_mm AS mm ON tt_news_cat.uid=mm.uid_foreign',
					'tt_news_cat.deleted=0 AND mm.uid_local='.$uid);

		$categories = array();
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$categories[] = $row['uid'];
		}

		return $categories;
	}





}





