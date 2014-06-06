<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2002-2004 Kasper Skårhøj (kasper@typo3.com)
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
* [CLASS/FUNCTION INDEX of SCRIPT]
*
*
*
*   52: class ext_update
*   59:     function main()
*  174:     function access()
*  188:     function query($fields)
*
*/
 
 
 
/**
* Class for updating tt_news category relations.
*
* @author  Stig Nørgaard Jepsen <stig@8602.dk>
* @package TYPO3
* @subpackage tt_news
*/
class ext_update {
	 
	/**
	* Main function, returning the HTML content of the module
	*
	* @return string  HTML
	*/
	function main() {
		$query_cat = $this->query('categoryrelations');
		$query_flex = $this->query('flexforms');
		$res_cat = mysql(TYPO3_db, $query_cat);
		$res_flex = mysql(TYPO3_db, $query_flex);
		echo mysql_error();
		 

		 
		 
		if ($res_cat) {
			$count_cat = mysql_num_rows($res_cat);
		}
		if ($count_flex) {
			$count_flex = mysql_num_rows($res_flex);
		}
		$count_flex = mysql_num_rows($res_flex);
		 
		if (!t3lib_div::GPvar('do_update')) {
			$onClick = "document.location='".t3lib_div::linkThisScript(array('do_update' => 1))."'; return false;";
			if ($count_cat) {
				$returnthis = '<b>There are found '.$count_cat.' newsitem(s) with category relations which should be reassigned.</b><br><br>';
			}
			if ($count_flex) {
				$returnthis .= ($returnthis?'<b>There are also found ':'<b>There are found ');
				$returnthis .= $count_flex.' tt_news content elements which have to be updated.</b><br>
					(NOTICE: This creates FlexForm-data based on the CODE-field. One thing to know is that if you have a CODE-field like this "LIST/4/,LATEST/1/", the category selection will from now on be 4 AND 1 for both LIST and LATEST. If this is a problem, you should use more than one content element)';
			}
			$returnthis .= '<br><br><br><b>Do you want to perform the action now?</b><br>(This action will not change your old data in the tt_news or tt_content table. So even if you perform this action, you will still be able to downgrade to an earlier version of tt_news retaining the old category relations and CODE field data.)
				<br><br><form action=""><input type="submit" value="DO IT" onclick="'.htmlspecialchars($onClick).'"></form>';
			return $returnthis;
		} elseif($count_cat OR $count_flex) {
			if ($count_cat) {
				while ($row = mysql_fetch_assoc($res_cat)) {
					$insertQ = t3lib_BEfunc::DBcompileInsert('tt_news_cat_mm', array('uid_local' => $row['uid'], 'uid_foreign' => $row['category'], 'sorting' => 1));
					#t3lib_div::debug($insertQ);
					$res2 = mysql(TYPO3_db, $insertQ);
				}
				$returndoupdate = $count_cat.' ROW(s) inserted.<br><br>';
			}
			if ($count_flex) {
				while ($row = mysql_fetch_assoc($res_flex)) {
					unset($what_to_display, $categories_to_display, $archive, $selection_mode, $theCode, $cat, $aFlag);
					$codes = t3lib_div::trimExplode(',', $row['select_key'], 1);
					if (!count($codes)) $codes = array('');
						while (list(, $theCode) = each($codes)) {
						list($theCode, $cat, $archive) = explode('/', $theCode);
						$what_to_display[] = (string)strtoupper(trim($theCode));
						if (substr($cat, 0, 2) == '0;') {
							$selection_mode = -1;
							$cat = substr($cat, 2);
						}
						$categories_to_display .= ($categories_to_display?';'.$cat:$cat);
					}
					if (!$selection_mode)$selection_mode = (!$categories_to_display?0:1);
					if ($categories_to_display) {
						$categories_to_display = t3lib_div::trimExplode(';', $categories_to_display, 1);
						$categories_to_display = array_unique($categories_to_display);
						$categories_to_display = (implode(',', $categories_to_display));
					}
					$archive = ($archive?$archive:-1);
					 
					// get pages (startingpoint) and recursive fields
					$pages = $row['pages'];
					$recursive = $row['recursive'];
					 
					$xml = trim('<?xml version="1.0" encoding="iso-8859-1" standalone="yes" ?>
						<T3FlexForms>
						<meta type="array">
						<currentSheetId>sDEF</currentSheetId>
						</meta>
						<data type="array">
						<sDEF type="array">
						<lDEF type="array">
						<what_to_display type="array">
						<vDEF>'.implode($what_to_display, ', ').'</vDEF>
						</what_to_display>
						<categoryMode type="array">
						<vDEF>'.$selection_mode.'</vDEF>
						</categoryMode>
						<categorySelection type="array">
						<vDEF>'.$categories_to_display.'</vDEF>
						</categorySelection>
						<archive type="array">
						<vDEF>'.$archive.'</vDEF>
						</archive>
						<pages type="array">
						<vDEF>'.$pages.'</vDEF>
						</pages>
						<recursive type="array">
						<vDEF>'.$recursive.'</vDEF>
						</recursive>
						</lDEF>
						</sDEF>
						</data>
						</T3FlexForms>');
					$updateRecord['pi_flexform'] = $xml;
					$updateQ = t3lib_BEfunc::DBcompileUpdate('tt_content', 'uid='.intval($row['uid']), $updateRecord);
					$res = mysql(TYPO3_db, $updateQ);
				}
				$returndoupdate .= $count_flex.' ROW(s) updated.<br><br>';
			}
			return $returndoupdate;
		}
	}
	 
	/**
	* Checks how many rows are found and returns true if there are any
	*
	* @return boolean
	*/
	function access($what = 'all') {
		if ($what = 'all') {
			$res = mysql(TYPO3_db, $query = $this->query('categoryrelations'));
			if ($res && mysql_num_rows($res)) {
				return 1;
			} else {
				$res = mysql(TYPO3_db, $query = $this->query('flexforms'));
				return ($res && mysql_num_rows($res) ? 1 : 0);
			}
		}
	}
	 
	/**
	* Creates query finding all tt_news elements which has a category relation in tt_news table not replicated in tt_news_cat_mm
	*
	* @return string  Full query
	*/
	function query($updatewhat) {
		if ($updatewhat == 'categoryrelations') {
			$query = 'SELECT tt_news.uid,category,tt_news_cat_mm.uid_foreign, max(category = uid_foreign) as testit
				FROM tt_news LEFT JOIN tt_news_cat_mm ON tt_news.uid = tt_news_cat_mm.uid_local
				GROUP BY uid HAVING (testit !=1 OR ISNULL(testit)) AND category AND NOT tt_news_cat_mm.uid_foreign';
			return $query;
		} elseif($updatewhat == 'flexforms') {
			$query = 'SELECT * FROM tt_content WHERE
				CType="list" AND
				list_type="9" AND
				pi_flexform=""';
			return $query;
		}
	}
}
 
 
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.ext_update.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.ext_update.php']);
}
 
?>
