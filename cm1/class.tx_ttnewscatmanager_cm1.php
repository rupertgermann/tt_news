<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2009 Rupert Germann <rupi@gmx.li>
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

/**
 * Additional items for the clickmenu.
 *
 * $Id: class.tx_ttnewscatmanager_cm1.php 26878 2009-11-24 10:17:01Z rupi $
 *
 * @author  Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage tt_news
 */
class tx_ttnewscatmanager_cm1 {



	function main(&$backRef,$menuItems,$tableID,$srcId)	{
		$this->includeLocalLang();
		$this->backRef = &$backRef;










		/**
		 * TODO
		 *
		 * FIXME
		 * backpath will not work in global installations
		 */
		if (($tableID == 'dragDrop_tt_news_cat' || $tableID == 'tt_news_cat_CM') && $srcId) {
			$table = 'tt_news_cat';

			$rec = t3lib_BEfunc::getRecordWSOL($table,$srcId);
			// fetch page record to get editing permissions
			$lCP = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::getRecord('pages',$rec['pid']));
			$doEdit = $lCP&16;

//print_r( array($lCP));

			if ($doEdit && $tableID == 'dragDrop_tt_news_cat') {
				$this->backRef->backPath = '../../../';
				$dstId = intval(t3lib_div::_GP('dstId'));
				$menuItems['moveinto'] = $this->dragDrop_moveCategory($srcId,$dstId);
				$menuItems['copyinto'] = $this->dragDrop_copyCategory($srcId,$dstId);
			}

			if ($tableID == 'tt_news_cat_CM') {

				$this->backRef->backPath = '../../../../typo3/';



				$menuItems = array();
				if ($doEdit) {
					$menuItems['edit'] = $this->DB_edit($table,$srcId);
					$menuItems['new'] = $this->DB_new($table,$rec);
					$menuItems['newsub'] = $this->DB_new($table,$rec,true);
				}

				$menuItems['info'] = $backRef->DB_info($table,$srcId);

				if ($doEdit) {
					$menuItems['hide'] = $this->DB_hideUnhide($table,$rec,'hidden');
					$elInfo = array(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle('tt_news_cat',$rec),$GLOBALS['BE_USER']->uc['titleLen']));
					$menuItems['spacer2'] = 'spacer';
					$menuItems['delete'] = $this->DB_delete($table,$srcId,$elInfo);
				}
			}
		}



		return $menuItems;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$backRef: ...
	 * @return	[type]		...
	 */
	function DB_edit($table,$uid)	{
		$loc='top.content.list_frame';
		$editOnClick='if('.$loc.'){'.$loc.".location.href=top.TS.PATH_typo3+'alt_doc.php?returnUrl='+top.rawurlencode(".
			$this->backRef->frameLocation($loc.'.document').")+'&edit[".$table."][".$uid."]=edit';}";

		return $this->backRef->linkItem(
			$this->backRef->label('edit'),
			$this->backRef->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backRef->PH_backPath,'gfx/edit2.gif','width="11" height="12"').' alt="" />'),
			$editOnClick.'return hideCM();'
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$backRef: ...
	 * @return	[type]		...
	 */
	function DB_new($table,$rec,$newsub=false)	{
		$editOnClick='';
		$loc='top.content.list_frame';

		if ($newsub) {
			$parent = $rec['uid'];
		} else {
			$parent = $rec['parent_category'];
		}

		$editOnClick='if('.$loc.'){'.$loc.".location.href=top.TS.PATH_typo3+'".
				"alt_doc.php?returnUrl='+top.rawurlencode(".$this->backRef->frameLocation($loc.'.document').")+'&edit[".$table."][".$rec['pid']."]=new".
				($parent?'&defVals['.$table.'][parent_category]='.$parent:'').'\';}';
		$lkey = 'new';
		if ($newsub) {
			$lkey = 'newsub';
		}

		return $this->backRef->linkItem(
			$GLOBALS['LANG']->getLLL($lkey,$this->LL),
			$this->backRef->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backRef->PH_backPath,'gfx/new_el.gif','width="11" height="12"').' alt="" />'),
			$editOnClick.'return hideCM();'
		);
	}


	/**
	 * Adding CM element for hide/unhide of the input record
	 *
	 * @param	string		Table name
	 * @param	array		Record array
	 * @param	string		Name of the hide field
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_hideUnhide($table,$rec,$hideField)	{
		return $this->DB_changeFlag($table, $rec, $hideField, $this->backRef->label(($rec[$hideField]?'un':'').'hide'), 'hide');
	}

	/**
	 * Adding CM element for a flag field of the input record
	 *
	 * @param	string		Table name
	 * @param	array		Record array
	 * @param	string		Name of the flag field
	 * @param	string		Menu item Title
	 * @param	string		Name of the item used for icons and labels
	 * @param	string		Icon path relative to typo3/ folder
	 * @return	array		Item array, element in $menuItems
	 */
	function DB_changeFlag($table, $rec, $flagField, $title, $name)    {
		$uid = $rec['_ORIG_uid'] ? $rec['_ORIG_uid'] : $rec['uid'];
		$editOnClick='';
		$loc='top.content.list_frame';
		$editOnClick='if('.$loc.'){'.$loc.".location.href=top.TS.PATH_typo3+'tce_db.php?redirect='+top.rawurlencode(".$this->backRef->frameLocation($loc.'.document').")+'".
			"&data[".$table.']['.$uid.']['.$flagField.']='.($rec[$flagField]?0:1).'&prErr=1&vC='.$GLOBALS['BE_USER']->veriCode()."';hideCM();}";

		return $this->backRef->linkItem(
			$title,
			$this->backRef->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backRef->PH_backPath,'gfx/button_'.($rec[$flagField]?'un':'').$name.'.gif','width="11" height="10"').' alt="" />'),
			$editOnClick.'return false;',
			1
		);
	}

	/**
	 * Adding CM element for Delete
	 *
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @param	array		Label for including in the confirmation message, EXT:lang/locallang_core.php:mess.delete
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_delete($table,$uid,$elInfo)	{
		$loc='top.content.list_frame';
		if($GLOBALS['BE_USER']->jsConfirmation(4))	{
			$conf = "confirm(".$GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.delete'),$elInfo[0]).
				t3lib_BEfunc::referenceCount($table,$uid,' (There are %s reference(s) to this record!)')).")";
		} else {
			$conf = '1==1';
		}
		$editOnClick='if('.$loc." && ".$conf." ){".$loc.".location.href=top.TS.PATH_typo3+'tce_db.php?redirect='+top.rawurlencode(".
			$this->backRef->frameLocation($loc.'.document').")+'".
			"&cmd[".$table.']['.$uid.'][DDdelete]=1&prErr=1&vC='.$GLOBALS['BE_USER']->veriCode()."';hideCM();}";

		return $this->backRef->linkItem(
			$GLOBALS['LANG']->getLLL('delete',$this->LL),
			$this->backRef->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backRef->PH_backPath,'gfx/garbage.gif','width="11" height="12"').' alt="" />'),
			$editOnClick.'return false;'
		);
	}


	/**
	 * Adding CM element for moving categories from a drag & drop action
	 *
	 * @param	integer		source UID code for the record to modify
	 * @param	integer		destination UID code for the record to modify
	 * @param	[type]		$backRef: ...
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function dragDrop_moveCategory($srcUid,$dstUid)	{
		$loc='top.content.list_frame';
		$editOnClick='if('.$loc.'){'.$loc.'.document.location=top.TS.PATH_typo3+"tce_db.php?redirect="+top.rawurlencode('.$this->backRef->frameLocation($loc.'.document').')+"'.
			'&data[tt_news_cat]['.$srcUid.'][parent_category]='.$dstUid.'&prErr=1&vC='.$GLOBALS['BE_USER']->veriCode().'";hideCM();}';

		return $this->backRef->linkItem(
			$GLOBALS['LANG']->getLLL('moveCategoryinto',$this->LL),
			$this->backRef->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backRef->PH_backPath,t3lib_extMgm::extRelPath('tt_news').'res/tt_news_cat.gif','width="18" height="16"').' alt="" />'),
			$editOnClick.'return false;',
			0
		);
	}
	/**
	 * Adding CM element for Copying categories Into/After from a drag & drop action
	 *
	 * @param	integer		source UID code for the record to modify
	 * @param	integer		destination UID code for the record to modify
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function dragDrop_copyCategory($srcUid,$dstUid)	{
		$loc='top.content.list_frame';
		$editOnClick='if('.$loc.'){'.$loc.'.document.location=top.TS.PATH_typo3+"tce_db.php?redirect="+top.rawurlencode('.$this->backRef->frameLocation($loc.'.document').')+"'.
			'&cmd[tt_news_cat]['.$srcUid.'][DDcopy]='.$dstUid.'&prErr=1&vC='.$GLOBALS['BE_USER']->veriCode().'";hideCM();}';

		return $this->backRef->linkItem(
			$GLOBALS['LANG']->getLLL('copyCategoryinto',$this->LL),
			$this->backRef->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backRef->PH_backPath,t3lib_extMgm::extRelPath('tt_news').'res/tt_news_cat.gif','width="18" height="16"').' alt="" />'),
			$editOnClick.'return false;',
			0
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function includeLocalLang()	{
		$llFile = t3lib_extMgm::extPath('tt_news').'cm1/locallang.xml';
		$this->LL = t3lib_div::readLLXMLfile($llFile, $GLOBALS['LANG']->lang);
	}
}



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tt_news/cm1/class.tx_ttnewscatmanager_cm1.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tt_news/cm1/class.tx_ttnewscatmanager_cm1.php"]);
}

?>