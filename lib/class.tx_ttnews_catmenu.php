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
/**
 * class.tx_ttnews_catmenu.php
 *
 * renders the tt_news CATMENU content element - inspired by class.webpagetree.php which renders the pagetree in the TYPO3 BackEnd
 *
 * $Id$
 *
 * @author Rupert Germann <rupi@gmx.li>
 */

require_once(t3lib_extMgm::extPath('tt_news').'lib/class.tx_ttnews_categorytree.php');

class tx_ttnews_catmenu {
	var $titleLen = 60;
	var $treeObj;
	var $mode = false;


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$pObj: ...
	 * @return	[type]		...
	 */
	function init(&$pObj) {

		$lConf = $pObj->conf['displayCatMenu.'];
		$this->treeObj = t3lib_div::makeInstance('tx_ttnews_FEtreeview');
		$this->treeObj->tt_news_obj = &$pObj;
		$this->treeObj->category = $pObj->piVars_catSelection;
		$this->treeObj->table = 'tt_news_cat';
		$this->treeObj->init($pObj->SPaddWhere.$pObj->enableCatFields.$pObj->catlistWhere, $pObj->config['catOrderBy']);
		$this->treeObj->backPath = TYPO3_mainDir;
		$this->treeObj->parentField = 'parent_category';
		$this->treeObj->thisScript = 'index.php?eID=tt_news';
		$this->treeObj->cObjUid = intval($pObj->cObj->data['uid']);
		$this->treeObj->fieldArray = array('uid','title','title_lang_ol','description','image'); // those fields will be filled to the array $this->treeObj->tree
		$this->treeObj->ext_IconMode = '1'; // no context menu on icons

		$expandable = $lConf['expandable'];
		$mode = $lConf['mode']?$lConf['mode']:'tree';
		if ($mode == 'ajaxtree') {
			$expandable = true;
			$this->treeObj->useAjax = true;
		}

		$this->treeObj->expandAll = $lConf['expandAll'];
		$this->treeObj->expandable = $expandable;
		$this->treeObj->expandFirst = $lConf['expandFirst'];
		$this->treeObj->titleLen = $this->titleLen;

		$this->treeObj->getCatNewsCount = $lConf['showNewsCountForCategories'];
		$this->treeObj->newsSelConf = $pObj->getSelectConf('');
		$this->treeObj->title = $GLOBALS['TSFE']->sL('LLL:EXT:tt_news/pi/locallang.xml:catmenuHeader');

		$allcatArr = explode(',',$pObj->catExclusive);
		$selcatArr = explode(',',$pObj->actuallySelectedCategories);
		$subcatArr = array_diff($allcatArr,$selcatArr);

		// get all selected category records from the current storagePid which are not 'root' categories
		// and add them as tree mounts. Subcategories of selected categories will be excluded.
		$cMounts = array();
		$nonRootMounts = FALSE;
		foreach ($selcatArr as $catID) {
			$tmpR = $GLOBALS['TSFE']->sys_page->getRecordsByField('tt_news_cat','uid',$catID,$pObj->SPaddWhere.$pObj->enableCatFields.$pObj->catlistWhere);
			if (is_array($tmpR[0]) && !in_array($catID,$subcatArr)) {
				if ($tmpR[0]['parent_category'] > 0) {
					$nonRootMounts = TRUE;
				}
				$cMounts[] = $catID;
			}
		}
		if ($nonRootMounts) {
			$this->treeObj->MOUNTS = $cMounts;

		}





//debug();
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$params: ...
	 * @param	[type]		$ajaxObj: ...
	 * @return	[type]		...
	 */
	function ajaxExpandCollapse(&$params, &$ajaxObj) {

		$this->init($params['tt_newsObj']);
		$this->treeObj->FE_USER = &$params['feUserObj'];
		$tree = $this->treeObj->getBrowsableTree();
		if (!$this->treeObj->ajaxStatus) {
			$ajaxObj->setError($tree);
		} else	{
			$ajaxObj->addContent('tree', $tree);
		}
	}
}

/**
 * [Describe function...]
 *
 */
class tx_ttnews_FEtreeview extends tx_ttnews_categorytree {

	var $TCEforms_itemFormElName='';
	var $TCEforms_nonSelectableItemsArray=array();

	/**
	 * wraps the record titles in the tree with links or not depending on if they are in the TCEforms_nonSelectableItemsArray.
	 *
	 * @param	string		$title: the title
	 * @param	array		$v: an array with uid and title of the current item.
	 * @return	string		the wrapped title
	 */
	function wrapTitle($title,$v)	{



		$newsConf = &$this->tt_news_obj->conf;
		if ($newsConf['catSelectorTargetPid']) {
			$catSelLinkParams = $newsConf['catSelectorTargetPid'];
			if ($newsConf['itemLinkTarget']) {
				$catSelLinkParams .= ' '.$newsConf['itemLinkTarget'];
			}
		} else {
			$catSelLinkParams = $GLOBALS['TSFE']->id;
		}


		if($v['uid']>0) {


			/**
			 * TODO: 27.11.2009
			 *
			 * this is a "hack" to prevent dropping the "L" parameter during ajax expand/collapse actions
			 * --> find out why TSFE->linkVars is empty
			 */


			$L = intval(t3lib_div::_GP('L'));
			if ($L > 0 && !$GLOBALS['TSFE']->linkVars) {
				$GLOBALS['TSFE']->linkVars = '&L='.$L;
			}

			if ($GLOBALS['TSFE']->sys_language_content && $v['uid']) {
				// get translations of category titles
				$catTitleArr = t3lib_div::trimExplode('|', $v['title_lang_ol']);
				$syslang = $GLOBALS['TSFE']->sys_language_content-1;
				$title = $catTitleArr[$syslang]?$catTitleArr[$syslang]:$title;
			}
			$piVars = &$this->tt_news_obj->piVars;
			$pTmp = $GLOBALS['TSFE']->ATagParams;
			if ($newsConf['displayCatMenu.']['insertDescrAsTitle']) {
				$GLOBALS['TSFE']->ATagParams = ($pTmp?$pTmp.' ':'').'title="'.$v['description'].'"';
			}

			if ($this->getCatNewsCount) {
				$title .= ' (' . $v['newsCount'] . ')';
			}

			if ($newsConf['useHRDates']) {
				$link = $this->tt_news_obj->pi_linkTP_keepPIvars($title, array(
					'cat' => $v['uid'],
					'year' => ($piVars['year']&&$newsConf['catmenuWithArchiveParams']?$piVars['year']:null),
					'month' => ($piVars['month']&&$newsConf['catmenuWithArchiveParams']?$piVars['month']:null)
				), $this->tt_news_obj->allowCaching, ($newsConf['dontUseBackPid']?1:0), $catSelLinkParams);
			} else {
				$link = $this->tt_news_obj->pi_linkTP_keepPIvars($title, array(
					'cat' => $v['uid'],
					'backPid' => null,
					'pointer' => null
				), $this->tt_news_obj->allowCaching, ($newsConf['dontUseBackPid']?1:0), $catSelLinkParams);
			}
			$GLOBALS['TSFE']->ATagParams = $pTmp;

			return $link ;

		} else { // catmenu Header
			return $this->tt_news_obj->pi_linkTP_keepPIvars($title, array(), $this->tt_news_obj->allowCaching, 1, $catSelLinkParams);
		}
	}






	/**
	 * Returns the root icon for a tree/mountpoint (defaults to the globe)
	 *
	 * @param	array		Record for root.
	 * @return	string		Icon image tag.
	 */
	function getRootIcon($rec) {
		$lConf = &$this->tt_news_obj->conf['displayCatMenu.'];
		if ($lConf['catmenuNoRootIcon']) { return; }
		if ($lConf['catmenuRootIconFile'])  {
			$iconConf['image.']['file'] = $lConf['catmenuIconPath'].$lConf['catmenuRootIconFile'];
			$iconConf['image.']['file.'] = $lConf['catmenuRootIconFile.'];
			$icon = $GLOBALS['TSFE']->cObj->IMAGE($iconConf['image.']);
		}
		return $icon?$icon:$this->wrapIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/i/_icon_website.gif','width="18" height="16"').' alt="" />',$rec);
	}



	/**
	 * Get icon for the row.
	 * If $this->iconPath and $this->iconName is set, try to get icon based on those values.
	 *
	 * @param	array		Item row.
	 * @return	string		Image tag.
	 */
	function getIcon($row) {
		$lConf = &$this->tt_news_obj->conf['displayCatMenu.'];
		$catIconMode = intval($lConf['catmenuIconMode']);
		if ($this->iconPath && $this->iconName) {
			$icon = '<img'.t3lib_iconWorks::skinImg('',$this->iconPath.$this->iconName,'width="18" height="16"').' alt="" />';
		} else  {
			switch($catIconMode) {
				case 1: // icon from cat db-record
					if($row['image']) {
						$iconConf['image.']['file'] = 'uploads/pics/'.$row['image'];
					}
					break;
				case 2: // own icons
					$iconConf['image.']['file'] = $lConf['catmenuIconPath'].$lConf['catmenuIconFile'];
					break;
				case -1: // no icons
					$iconConf['image.']['file'] = '';
					break;
			}
			if ($iconConf['image.']['file']) {
				$iconConf['image.']['file.'] = $lConf['catmenuIconFile.'];
				$icon = $GLOBALS['TSFE']->cObj->IMAGE($iconConf['image.']);
			}
		}
		if (!$icon && !$catIconMode) {
			$icon = t3lib_iconWorks::getIconImage($this->table,$row,$this->backPath,' class="c-recIcon"');
		}
		return $this->wrapIcon($icon,$row);
	}





	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param	string		HTML string to wrap, probably an image tag.
	 * @param	string		Command for 'PM' get var
	 * @param	[type]		$isExpand: ...
	 * @return	string		Link-wrapped input string
	 * @access private
	 */
	function PMiconATagWrap($icon, $cmd, $isExpand = true)	{
		if ($this->thisScript && $this->expandable) {
			$newsConf = &$this->tt_news_obj->conf;
			if ($newsConf['catSelectorTargetPid']) {
				$catSelLinkParams = $newsConf['catSelectorTargetPid'];
//				if ($newsConf['itemLinkTarget']) {
//					$catSelLinkParams .= ' '.$newsConf['itemLinkTarget'];
//				}
			} else {
				$catSelLinkParams = $GLOBALS['TSFE']->id;
			}
			if ($this->useAjax) {
				// activate dynamic ajax-based tree
				$js = htmlspecialchars('categoryTree.load(\'' . $cmd . '\', ' . intval($isExpand) . ', this, \'' .
					rawurlencode($catSelLinkParams) . '\', ' . $this->cObjUid . ', ' . intval(t3lib_div::_GP('L')) . ')');
				return '<a class="pm" onclick="'.$js.'">'.$icon.'</a>';
			} else {
//
//				$bMark=($this->bank.'_'.$uid);
//				if ($bMark)	{
//					$anchor = '#'.$bMark;
//					$name=' name="'.$bMark.'"';
//				}
				$anchor = '';
				$name = '';
//				debug(t3lib_div::getIndpEnv('TYPO3_SITE_SCRIPT'));

				$aUrl = $this->tt_news_obj->pi_linkTP_keepPIvars_url(array(), $this->tt_news_obj->allowCaching, 0, $catSelLinkParams).'&PM='.$cmd.$anchor;
				return '<a class="pm" href="'.htmlspecialchars($aUrl).'"'.$name.'>'.$icon.'</a>';

			}

		} else {
			return $icon;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function initializePositionSaving()	{
		// Get stored tree structure:
		if ($this->FE_USER->user) { // a user is logged in
			$this->stored = unserialize($this->FE_USER->uc['tt_news'][$this->treeName]);
		} else {
			$this->stored = unserialize($_COOKIE[$this->treeName]);
		}
		if (!is_array($this->stored)) { $this->stored = array(); }
//		debug($this->stored);

//debug($_COOKIE, ' ('.__CLASS__.'::'.__FUNCTION__.')', __LINE__, __FILE__, 3);

		// PM action
		// (If an plus/minus icon has been clicked, the PM GET var is sent and we must update the stored positions in the tree):
		$PM = explode('_',t3lib_div::_GP('PM'));	// 0: mount key, 1: set/clear boolean, 2: item ID (cannot contain "_"), 3: treeName
//		debug($PM,'$PM');

		if (count($PM)==4 && $PM[3]==$this->treeName)	{
			if (isset($this->MOUNTS[$PM[0]]))	{
				if ($PM[1])	{	// set
					$this->stored[$PM[0]][$PM[2]]=1;
					$this->savePosition();
				} else {	// clear
					unset($this->stored[$PM[0]][$PM[2]]);
					$this->savePosition();
				}
			}
		}
	}

	/**
	 * Saves the content of ->stored (keeps track of expanded positions in the tree)
	 * $this->treeName will be used as key for BE_USER->uc[] to store it in
	 *
	 * @return	void
	 * @access private
	 */
	function savePosition()	{
		if ($this->FE_USER->user) {
			$this->FE_USER->uc['tt_news'][$this->treeName] = serialize($this->stored);
			$this->FE_USER->writeUC();
		} else {
			setcookie($this->treeName, serialize($this->stored));
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$row: ...
	 * @param	[type]		$titleLen: ...
	 * @return	[type]		...
	 */
	function getTitleStr($row,$titleLen=30)	{
		$title = htmlspecialchars(t3lib_div::fixed_lgd_cs($row['title'],$titleLen));

		return $title;
	}


}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_catmenu.php'])    {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_catmenu.php']);
}
?>