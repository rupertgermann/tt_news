<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2007 Rupert Germann <rupi@gmx.li>
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
* renders the CATMENU content element - extends class t3lib_treeview to change some methods.
*
* $Id: class.tx_ttnews_catmenu.php 4750 2007-01-25 20:46:23Z rupertgermann $
*
* @author Rupert Germann <rupi@gmx.li>
*/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   56: class tx_ttnews_catmenu extends t3lib_treeview
 *   68:     function wrapTitle($title,$v)
 *  108:     function getBrowsableTree()
 *  180:     function getTree($uid, $depth=999, $depthData='',$blankLineCode='')
 *  256:     function printTree($treeArr='')
 *  282:     function getRootIcon($rec)
 *  302:     function getIcon($row)
 *  344:     function PMicon($row,$a,$c,$nextCount,$exp)
 *
 * TOTAL FUNCTIONS: 7
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
require_once(PATH_t3lib.'class.t3lib_treeview.php');

class tx_ttnews_catmenu extends t3lib_treeview {

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
		$newsConfig = &$this->tt_news_obj->config;
		$catSelLinkParams = ($newsConf['catSelectorTargetPid']?($newsConfig['itemLinkTarget']?$newsConf['catSelectorTargetPid'].' '.$newsConfig['itemLinkTarget']:$newsConf['catSelectorTargetPid']):$GLOBALS['TSFE']->id);

		if($v['uid']>0) {
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
			if ($newsConf['useHRDates']) {
				$link = $this->tt_news_obj->pi_linkTP_keepPIvars($title, array(
					'cat' => $v['uid'],
					'year' => ($piVars['year']?$piVars['year']:null),
					'month' => ($piVars['month']?$piVars['month']:null)
					), $this->tt_news_obj->allowCaching, ($newsConf['dontUseBackPid']?1:0), $catSelLinkParams);
			} else {
				$link = $this->tt_news_obj->pi_linkTP_keepPIvars($title, array('cat' => $v['uid'], 'backPid' => null, 'pointer' => null), $this->tt_news_obj->allowCaching, ($newsConf['dontUseBackPid']?1:0), $catSelLinkParams);
			}
			$GLOBALS['TSFE']->ATagParams = $pTmp;
			return $link ;

		} else { // catmenu Header
			return $this->tt_news_obj->pi_linkTP_keepPIvars($title, array(), $this->tt_news_obj->allowCaching, 1, $catSelLinkParams);
		}
	}

	/**
	 * Will create and return the HTML code for a browsable tree
	 * Is based on the mounts found in the internal array ->MOUNTS (set in the constructor)
	 *
	 * @return	string		HTML code for the browsable tree
	 */
	function getBrowsableTree()	{

			// Get stored tree structure AND updating it if needed according to incoming PM GET var.
		$this->initializePositionSaving();

			// Init done:
		$titleLen=intval($this->BE_USER->uc['titleLen']);
		$treeArr=array();

			// Traverse mounts:
		$cc = 0;
// 			debug($this->MOUNTS,'$this->MOUNTS '.__FUNCTION__.' '.__CLASS__);
		foreach($this->MOUNTS as $idx => $uid)	{

				// Set first:
			$this->bank=$idx;
			$isOpen = $this->stored[$idx][$uid] || $this->expandFirst;

				// Save ids while resetting everything else.
			$curIds = $this->ids;
			$this->reset();
			$this->ids = $curIds;

				// Preparing rootRec for the mount
			if ($uid)	{
				if($cc) { // don't accumulate $firstHtml if we're listing multiple MOUNTS
					$firstHtml = '';
				}
				$cc++;
				$rootRec = $this->getRecord($uid);
				$firstHtml.=$this->getIcon($rootRec);
			} else {
					// Artificial record for the tree root, id=0
				$rootRec = $this->getRootRecord($uid);
				$firstHtml.=$this->getRootIcon($rootRec);
			}

// 			debug($firstHtml,'$firstHtml bank:'.$idx.' '.__FUNCTION__.' '.__CLASS__);

			if (is_array($rootRec))	{
					// Add the root of the mount to ->tree
				$this->tree[]=array('HTML'=>$firstHtml,'row'=>$rootRec,'bank'=>$this->bank);

					// If the mount is expanded, go down:
				if ($isOpen)	{
						// Set depth:
							$depthD = '';
					if ($this->addSelfId)	$this->ids[] = $uid;
					$this->getTree($uid,999,$depthD);
				}

					// Add tree:
				$treeArr=array_merge($treeArr,$this->tree);
			}

		}
// 					debug($treeArr,'$treeArr '.__FUNCTION__.' '.__CLASS__);

		return $this->printTree($treeArr);
	}



	/**
	 * Fetches the data for the tree
	 *
	 * @param	integer		item id for which to select subitems (parent id)
	 * @param	integer		Max depth (recursivity limit)
	 * @param	string		HTML-code prefix for recursive calls.
	 * @param	string		? (internal)
	 * @return	integer		The count of items on the level
	 */
	function getTree($uid, $depth=999, $depthData='',$blankLineCode='')	{

			// Buffer for id hierarchy is reset:
		$this->buffer_idH=array();
			// Init vars
		$depth=intval($depth);
		$HTML='';
		$a=0;

		$res = $this->getDataInit($uid);
		$c = $this->getDataCount($res);
		$crazyRecursionLimiter = 999;

			// Traverse the records:
		while ($crazyRecursionLimiter>0 && $row = $this->getDataNext($res))	{
			$a++;
			$crazyRecursionLimiter--;

			$newID =$row['uid'];
			$this->tree[]=array();		// Reserve space.
			end($this->tree);
			$treeKey = key($this->tree);	// Get the key for this space
			$LN = ($a==$c)?'blank':'line';

				// If records should be accumulated, do so
			if ($this->setRecs)	{
				$this->recs[$row['uid']] = $row;
			}

				// Accumulate the id of the element in the internal arrays
			$this->ids[]=$idH[$row['uid']]['uid']=$row['uid'];
			$this->ids_hierarchy[$depth][]=$row['uid'];

				// Make a recursive call to the next level
			if ($depth>1 && $this->expandNext($newID) && !$row['php_tree_stop'])	{

				$theIcon = $depthData.'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/'.$LN.'.gif','width="18" height="16"').' alt="" />';
				$nextCount=$this->getTree(
						$newID,
						$depth-1,
						$this->makeHTML?$theIcon:'',
						$blankLineCode.','.$LN
					);
				if (count($this->buffer_idH))	$idH[$row['uid']]['subrow']=$this->buffer_idH;
				$exp=1;	// Set "did expand" flag
			} else {
				$nextCount=$this->getCount($newID);
				$exp=0;	// Clear "did expand" flag
			}

				// Set HTML-icons, if any:
			if ($this->makeHTML)	{
				$HTML = $depthData.$this->PMicon($row,$a,$c,$nextCount,$exp);
				$HTML.=$this->wrapStop($this->getIcon($row),$row);
			}

				// Finally, add the row/HTML content to the ->tree array in the reserved key.
			$this->tree[$treeKey] = Array(
				'row'=>$row,
				'HTML'=>$HTML,
				'invertedDepth'=>$depth,
				'blankLineCode'=>$blankLineCode,
				'bank' => $this->bank
			);
		}
		$this->getDataFree($res);
		$this->buffer_idH=$idH;
		return $c;
	}

	/**
	 * Compiles the HTML code for displaying the structure found inside the ->tree array
	 *
	 * @param	array		"tree-array" - if blank string, the internal ->tree array is used.
	 * @return	string		The HTML code for the tree
	 */
	function printTree($treeArr='')	{

		$lConf = &$this->tt_news_obj->conf['displayCatMenu.'];
		$titleLen=intval($this->BE_USER->uc['titleLen']);
		if (!is_array($treeArr))	$treeArr=$this->tree;
		$out='

		';
		foreach($treeArr as $k => $v)	{
			$idAttr = htmlspecialchars($this->domIdPrefix.$this->getId($v['row']).'_'.$v['bank']);
			if ($v['row']['uid']) {
				if (!$lConf['catmenuItem_NO_stdWrap.']) { $lConf['catmenuItem_NO_stdWrap.']['wrap'] = '<div>|</div>'; }
				if (!$lConf['catmenuItem_ACT_stdWrap.']) { $lConf['catmenuItem_ACT_stdWrap.']['wrap'] = '<div style="font-weight:bold;">|</div>'; }
				$out.= $this->tt_news_obj->local_cObj->stdWrap($v['HTML'].$this->wrapTitle($this->getTitleStr($v['row'],$titleLen),$v['row'],$v['bank']), $lConf['catmenuItem_'.($this->tt_news_obj->piVars['cat']==$v['row']['uid']?'ACT':'NO').'_stdWrap.']);
			} else { // root item
				$out.= $this->tt_news_obj->local_cObj->stdWrap($v['HTML'].$this->wrapTitle($this->getTitleStr($v['row'],$titleLen),$v['row'],$v['bank']), $lConf['catmenuHeader_stdWrap.']);
			}
		}
		return $out;
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
	 * Generate the plus/minus icon for the browsable tree.
	 *
	 * @param	array		record for the entry
	 * @param	integer		The current entry number
	 * @param	integer		The total number of entries. If equal to $a, a "bottom" element is returned.
	 * @param	integer		The number of sub-elements to the current element.
	 * @param	boolean		The element was expanded to render subelements if this flag is set.
	 * @return	string		Image tag with the plus/minus icon.
	 * @access private
	 * @see t3lib_pageTree::PMicon()
	 */
	function PMicon($row,$a,$c,$nextCount,$exp)	{
		$PM = /*$nextCount ? ($exp?'minus':'plus') : */'join';
		$BTM = ($a==$c)?'bottom':'';
		$lConf = &$this->tt_news_obj->conf['displayCatMenu.'];
		if ($lConf['catmenuIconPath'] && $lConf['catmenuIconMode'] == 2)  {
			$iconConf['image.']['file'] = $lConf['catmenuIconPath'].$PM.$BTM.'.gif';
			$icon = $GLOBALS['TSFE']->cObj->IMAGE($iconConf['image.']);
		}
		 else {
			$icon = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/'.$PM.$BTM.'.gif','width="18" height="16"').' alt="" />';
		}
		return $icon;
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.tx_ttnews_catmenu.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.tx_ttnews_catmenu.php']);
}
?>
