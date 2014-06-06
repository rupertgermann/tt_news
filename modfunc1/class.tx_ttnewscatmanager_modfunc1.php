<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2009 Rupert Germann <rupi@gmx.li>
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
 * Module 'category manager' for the 'tt_news' extension.
 *
 * $Id: class.tx_ttnews_tcemain.php 4277 2006-12-09 17:04:52Z rupertgermann $
 * @author    Rupert Germann <rupi@gmx.li>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   64: class tx_ttnewscatmanager_modfunc1 extends t3lib_extobjbase
 *   73:     function main()
 *  135:     function renderCatTree($cmd='')
 *  191:     function sendResponse($cmd)
 *
 *
 *  236: class tx_ttnewscatmanager_treeView extends t3lib_treeview
 *  248:     function wrapTitle($title,$v)
 *  279:     function makeControl($table,$row)
 *  341:     function includeLocalLang()
 *  357:     function PM_ATagWrap($icon,$cmd,$bMark='')
 *  377:     function wrapIcon($icon,&$row)
 *
 * TOTAL FUNCTIONS: 8
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_t3lib.'class.t3lib_extobjbase.php');
require_once(PATH_t3lib.'class.t3lib_treeview.php');
require_once(t3lib_extMgm::extPath('tt_news').'lib/class.tx_ttnews_div.php');

/**
 * Module extension (addition to function menu) 'category manager' for the 'tt_news' extension.
 *
 * @author	Rupert Germann <rupi@gmx.li>
 * @package	TYPO3
 * @subpackage	tt_news
 */
class tx_ttnewscatmanager_modfunc1 extends t3lib_extobjbase {



	/**
	 * Main method of the module
	 *
	 * @return	HTML
	 */
	function main()	{
		if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']) { // get tt_news extConf array
			$this->confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);
		}

		if ($this->confArr['useStoragePid']) {
			$id = intval($this->pObj->id);
			$catRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid','tt_news_cat','pid='.$id.' AND deleted=0');
			if (empty($catRows)) {
				return 'No categories found. PLease select a page with tt_news categories on it.';
			}
		}

		$this->useXajax = t3lib_extMgm::isLoaded('xajax');
		if ($this->useXajax) {
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']) {
				define ('XAJAX_DEFAULT_CHAR_ENCODING', $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);
			} else {
				define ('XAJAX_DEFAULT_CHAR_ENCODING', 'iso-8859-15');
			}

			require_once (t3lib_extMgm::extPath('xajax') . 'class.tx_xajax.php');
			$this->xajax = t3lib_div::makeInstance('tx_xajax');
			$this->xajax->setWrapperPrefix('tx_ttnews_');
			$this->xajax->registerFunction(array('sendResponse',&$this,'sendResponse'));
			$this->pObj->doc->JScode .= $this->xajax->getJavascript($GLOBALS['BACK_PATH'].'../'.t3lib_extMgm::siteRelPath('xajax'));
			$this->xajax->processRequests();
		}

			// Drag and Drop code is added:
		$DDparts = $this->pObj->doc->getDragDropCode('tt_news_cat');
		// ignore the $DDparts[1] for now
		$this->pObj->doc->JScode .= $DDparts[0];
		$this->pObj->doc->postCode .= $DDparts[2];

		if (!is_object($this->divObj)) {
			$this->divObj = t3lib_div::makeInstance('tx_ttnews_div');
		}

		$theOutput.=$this->pObj->doc->spacer(5);
		$theOutput.=$this->pObj->doc->section($GLOBALS['LANG']->getLL('title'),'',0,1);
		$theOutput.=$this->pObj->doc->spacer(5);

		if ($this->useXajax) {
			$showHideAll = '<span id="showHide"><span onclick="tx_ttnews_sendResponse(\'show\');" style="cursor:pointer;">show all</span></span>';
			$theOutput .= $showHideAll;
		}

		$theOutput .= $this->pObj->doc->spacer(5);
		$theOutput .= '<span id="tt_news_cat_tree">'.$this->renderCatTree().'<span>';

		return $theOutput;
	}



	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$cmd: ...
	 * @return	[type]		...
	 */
	function renderCatTree($cmd='')    {
		global $BE_USER;

		if ($this->confArr['useStoragePid']) {
			$TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig($this->table,$this->row);
			$this->storagePid = intval(t3lib_div::_GP('id'));
			$SPaddWhere = ' AND tt_news_cat.pid=' . $this->storagePid;
		}

		if (!is_object($treeViewObj)) {
			$treeViewObj = t3lib_div::makeInstance('tx_ttnewscatmanager_treeView');
		}

		$treeOrderBy = $this->confArr['treeOrderBy']?$this->confArr['treeOrderBy']:'uid';

		$treeViewObj->treeName = $this->table.'_tree';
		$treeViewObj->table = 'tt_news_cat';
		$treeViewObj->init($SPaddWhere.$catlistWhere,$treeOrderBy);
		$treeViewObj->backPath = $this->pObj->doc->backPath;
		$treeViewObj->parentField = 'parent_category';



		$treeViewObj->expandAll = ($this->useXajax?($cmd == 'show'?1:0):1);
		$treeViewObj->expandFirst = ($this->useXajax?0:1);

		$treeViewObj->fieldArray = array('uid','title','description','hidden','starttime','endtime','fe_group'); // those fields will be filled to the array $treeViewObj->tree
		$treeViewObj->useXajax = $this->useXajax;

		$this->perms_clause = $BE_USER->getPagePermsClause(1);
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->pObj->doc->id,$this->perms_clause);
		$treeViewObj->calcPerms = $BE_USER->calcPerms($this->pageinfo);

		$treeViewObj->includeLocalLang();

		if ($this->includeList) {
			$treeViewObj->MOUNTS = t3lib_div::intExplode(',',$this->includeList);
		}

		$treeViewObj->title = 'news categories';
// 		if ($cmd == 'hide') {
// 			unset($treeViewObj->stored);
// 			$treeViewObj->savePosition();
// 		}
			// render tree html
		$treeContent = $treeViewObj->getBrowsableTree();


		return $treeContent;
	}
	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$cmd: ...
	 * @return	[type]		...
	 */
	function sendResponse($cmd) 	{
		if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']) { // get tt_news extConf array
			$this->confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);
		}
		if (!is_object($this->divObj)) {
			$this->divObj = t3lib_div::makeInstance('tx_ttnews_div');
		}



		$objResponse = new tx_xajax_response();

		$this->debug = array();
		if ($cmd == 'show') {
			$showhideLink = '<span onclick="tx_ttnews_sendResponse(\'hide\');" style="cursor:pointer;">hide all</span>';
		} else {
			$showhideLink = '<span onclick="tx_ttnews_sendResponse(\'show\');" style="cursor:pointer;">show all</span>';
		}
		if ($cmd == 'show' || $cmd == 'hide') {
			$content = $this->renderCatTree($cmd);
		} else {
			t3lib_div::_GETset($cmd,'PM');
			$content = $this->renderCatTree();
		}

// 		$content .= '<div id="debug-tree">debug</div>';
		$objResponse->addAssign('tt_news_cat_tree', 'innerHTML', $content);

// 		$objResponse->addAssign('debug-tree', 'innerHTML', t3lib_div::view_array($this->debug));

		$objResponse->addAssign('showHide', 'innerHTML', $showhideLink);

		//return the XML response
		return $objResponse->getXML();
	}
}





	/**
	 * extend class t3lib_treeview to change some functions.
	 *
	 */
class tx_ttnewscatmanager_treeView extends t3lib_treeview {

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
// 		debug($v);
		if($v['uid']>0) {
			$hrefTitle = htmlentities('[id='.$v['uid'].'] '.$v['description']);
			$params='&edit[tt_news_cat]['.$v['uid'].']=edit';
			$aOnClick = htmlspecialchars(t3lib_BEfunc::editOnClick($params,$this->backPath));

			$out = '<a href="#" onclick="'.$aOnClick.'" title="'.$hrefTitle.'">'.$title.'</a>';
				// Wrap title in a drag/drop span.
			$spanOnDrag = htmlspecialchars('return dragElement("'.$v['uid'].'")');
			$spanOnDrop = htmlspecialchars('return dropElement("'.$v['uid'].'")');
			$out = '<span id="dragTitleID_'.$v['uid'].'" ondragstart="'.$spanOnDrag.'" onmousedown="'.$spanOnDrag.'" onmouseup="'.$spanOnDrop.'">'.$out.'</span>'.$this->makeControl('tt_news_cat',$v);
		} else {
			$spanOnDrop = htmlspecialchars('return dropElement("0")');
			$out = '<span id="dragTitleID_0" onmouseup="'.$spanOnDrop.'">'.$title.'</span>';
		}
		return $out;
	}






	/**
	 * Creates the control panel for a single record in the listing.
	 *
	 * @param	string		The table
	 * @param	array		The record for which to make the control panel.
	 * @return	string		HTML table with the control panel (unless disabled)
	 */
	function makeControl($table,$row)	{
		global $TCA, $LANG, $SOBE;
		if ($this->dontShowClipControlPanels)	return '';

			// Initialize:
		t3lib_div::loadTCA($table);
		$cells=array();

			// This expresses the edit permissions for this particular element:
		$permsEdit = ($table!='pages' && ($this->calcPerms&16));

			// "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
		if ($permsEdit)	{
			$params='&edit['.$table.']['.$row['uid'].']=edit';
			$cells[]='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$this->backPath)).'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/edit2'.(!$TCA[$table]['ctrl']['readOnly']?'':'_d').'.gif','width="11" height="12"').' title="'.$LANG->getLLL('edit',$this->LL).'" alt="" />'.
					'</a>';
		}


// 			// "Info": (All records)
// 		$cells[]='<a href="#" onclick="'.htmlspecialchars('top.launchView(\''.$table.'\', \''.$row['uid'].'\'); return false;').'">'.
// 				'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/zoom2.gif','width="12" height="12"').' title="'.$LANG->getLLL('showInfo',$this->LL).'" alt="" />'.
// 				'</a>';

			// "Hide/Unhide" links:
		$hiddenField = $TCA[$table]['ctrl']['enablecolumns']['disabled'];
		if ($permsEdit && $hiddenField && $TCA[$table]['columns'][$hiddenField] && (!$TCA[$table]['columns'][$hiddenField]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields',$table.':'.$hiddenField)))	{
			if ($row[$hiddenField])	{
				$params='&data['.$table.']['.$row['uid'].']['.$hiddenField.']=0';
				$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$SOBE->doc->issueCommand($params).'\');').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_unhide.gif','width="11" height="10"').' title="'.$LANG->getLLL('unHide',$this->LL).'" alt="" />'.
						'</a>';
			} else {
				$params='&data['.$table.']['.$row['uid'].']['.$hiddenField.']=1';
				$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$SOBE->doc->issueCommand($params).'\');').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_hide.gif','width="11" height="10"').' title="'.$LANG->getLLL('hide',$this->LL).'" alt="" />'.
						'</a>';
			}
		}

			// "Delete" link:
// 		if (
// 			($table=='pages' && ($localCalcPerms&4)) || ($table!='pages' && ($this->calcPerms&16))
// 			)	{
// 			$params='&cmd['.$table.']['.$row['uid'].'][delete]=1';
// 			$cells[]='<a href="#" onclick="'.htmlspecialchars('if (confirm('.$LANG->JScharCode($LANG->getLLL('deleteWarning',$this->LL).t3lib_BEfunc::referenceCount($table,$row['uid'],' (There are %s reference(s) to this record!)')).')) {jumpToUrl(\''.$SOBE->doc->issueCommand($params).'\');} return false;').'">'.
// 					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/garbage.gif','width="11" height="12"').' title="'.$LANG->getLLL('delete',$this->LL).'" alt="" />'.
// 					'</a>';
// 		}


		return '
											<!-- CONTROL PANEL: '.$table.':'.$row['uid'].' -->
											<span style="padding:0 0 0 7px;">'.implode('',$cells).'</span>';
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function includeLocalLang()	{
		$llFile = t3lib_extMgm::extPath('tt_news').'modfunc1/locallang.xml';
		$this->LL = t3lib_div::readLLXMLfile($llFile, $GLOBALS['LANG']->lang);
	}



	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param	string		HTML string to wrap, probably an image tag.
	 * @param	string		Command for 'PM' get var
	 * @param	boolean		If set, the link will have a anchor point (=$bMark) and a name attribute (=$bMark)
	 * @return	string		Link-wrapped input string
	 * @access private
	 */
	function PM_ATagWrap($icon,$cmd,$bMark='')	{
		if ($this->useXajax) {
			$cmdParts = explode('_',$cmd);
			$title = 'collapse';
			if ($cmdParts[1] == '1') {
				$title = 'expand';
			}
			return '<span onclick="tx_ttnews_sendResponse(\''.$cmd.'\');" style="cursor:pointer;" title="'.$title.'">'.$icon.'</span>';
		} else {
			return parent::PM_ATagWrap($icon,$cmd,$bMark);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$icon: ...
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function wrapIcon($icon,&$row)	{

			// Add title attribute to input icon tag
		$theIcon = $this->addTagAttributes($icon, $this->titleAttrib.'="'.$this->getTitleAttrib($row).'"');

		if($row['uid']>0) {
			$theIcon = $GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon($theIcon,'tt_news_cat_CM',$row['uid'],0,'&bank='.$this->bank);

			$spanOnDrag = htmlspecialchars('return dragElement("'.$row['uid'].'")');
			$spanOnDrop = htmlspecialchars('return dropElement("'.$row['uid'].'")');
			$theIcon = '<span id="dragIconID_'.$row['uid'].'" ondragstart="'.$spanOnDrag.'" onmousedown="'.$spanOnDrag.'" onmouseup="'.$spanOnDrop.'">'.$theIcon.'</span>';
		} else {
			$spanOnDrop = htmlspecialchars('return dropElement("0")');
			$theIcon = '<span id="dragIconID_0" onmouseup="'.$spanOnDrop.'">'.$theIcon.'</span>';
		}

		return $theIcon;
	}
}







if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/modfunc1/class.tx_ttnewscatmanager_modfunc1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/modfunc1/class.tx_ttnewscatmanager_modfunc1.php']);
}

?>