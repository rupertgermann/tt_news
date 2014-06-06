<?php

class ux_tx_cms_layout extends tx_cms_layout {
    function getTable_tt_news($id)	{
	
	global $TCA;

		$this->initializeLanguages();

			// Initialize:
		$RTE = $GLOBALS['BE_USER']->isRTE();
		$lMarg=1;
		$showHidden = $this->tt_contentConfig['showHidden']?'':t3lib_BEfunc::BEenableFields('tt_news');


			// Select display mode:
		if (!$this->tt_contentConfig['single'])	{		// MULTIPLE column display mode, side by side:

				// Setting language list:
			$langList = $this->tt_contentConfig['sys_language_uid'];
			if ($this->tt_contentConfig['languageMode'])	{
				if ($this->tt_contentConfig['languageColsPointer'])	{
					$langList='0,'.$this->tt_contentConfig['languageColsPointer'];
				} else {
					$langList=implode(',',array_keys($this->tt_contentConfig['languageCols']));
				}
				$languageColumn = array();
			}
			$langListArr = explode(',',$langList);
			$defLanguageCount = array();
			$defLangBinding = array();

				// For EACH languages... :
			foreach($langListArr as $lP)	{	// If NOT languageMode, then we'll only be through this once.
				$showLanguage = $this->defLangBinding && $lP==0 ? ' AND sys_language_uid IN (0,-1)' : ' AND sys_language_uid='.$lP;
				$cList = explode(',',$this->tt_contentConfig['cols']);
				$content = array();
				$head = array();

					// For EACH column, render the content into a variable:
				#foreach($cList as $key)	{
				$key = 0;
					if (!$lP) $defLanguageCount[$key] = array();

						// Select content elements from this column/language:
					$queryParts = $this->makeQueryArray('tt_news', $id, $showHidden.$showLanguage);
					$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);

						// Traverse any selected elements and render their display code:
					$rowArr = $this->getResult($result);

					foreach($rowArr as $row)	{
						$singleElementHTML = '';
						if (!$lP) $defLanguageCount[$key][] = $row['uid'];

						$editUidList.= $row['uid'].',';
						
						
						$singleElementHTML.= $this->tt_news_drawHeader($row,$this->tt_contentConfig['showInfo']?15:5, $this->defLangBinding && $lP>0, TRUE);


						$isRTE = $RTE && $this->isRTEforField('tt_news',$row,'bodytext');
						$singleElementHTML.= $this->tt_content_drawItem($row,$isRTE);

						if ($this->defLangBinding && $this->tt_contentConfig['languageMode'])	{
							$defLangBinding[$key][$lP][$row[($lP ? 'l18n_parent' : 'uid')]] = $singleElementHTML;
						} else {
							$content[$key].= $singleElementHTML;
						}
					}

		
					$editUidList = '';
				#}

					// For EACH column, fit the rendered content into a table cell:
				$out='';
				foreach($cList as $k => $key)	{
					if (!$k)	{
						$out.= '
							<td><img src="clear.gif" width="'.$lMarg.'" height="1" alt="" /></td>';
					} else {
						$out.= '
							<td><img src="clear.gif" width="4" height="1" alt="" /></td>
							<td bgcolor="black"><img src="clear.gif" width="1" height="1" alt="" /></td>
							<td><img src="clear.gif" width="4" height="1" alt="" /></td>';
					}
					$out.= '
							<td valign="top">'.$head[$key].$content[$key].'</td>';

						// Storing content for use if languageMode is set:
					if ($this->tt_contentConfig['languageMode'])	{
						$languageColumn[$key][$lP] = $head[$key].$content[$key];

					}
				}

					// Wrap the cells into a table row:
				$out = '
					<table border="0" cellpadding="0" cellspacing="0" width="480" class="typo3-page-cols">
						<tr>'.$out.'
						</tr>
					</table>';

					// CSH:
				$out.= t3lib_BEfunc::cshItem($this->descrTable,'columns_multi',$GLOBALS['BACK_PATH']);
			}

				// If language mode, then make another presentation:
				// Notice that THIS presentation will override the value of $out! But it needs the code above to execute since $languageColumn is filled with content we need!
			if ($this->tt_contentConfig['languageMode'])	{

					// Get language selector:
				#$languageSelector = $this->languageSelector($id);

					// Reset out - we will make new content here:
				$out='';
					// Separator between language columns (black thin line)
				$midSep = '
						<td><img src="clear.gif" width="4" height="1" alt="" /></td>
						<td bgcolor="black"><img src="clear.gif" width="1" height="1" alt="" /></td>
						<td><img src="clear.gif" width="4" height="1" alt="" /></td>';

					// Traverse languages found on the page and build up the table displaying them side by side:
				$cCont=array();
				$sCont=array();
				foreach($langListArr as $lP)	{

						// Header:
					$cCont[$lP]='
						<td valign="top" align="center" class="bgColor6"><strong>'.htmlspecialchars($this->tt_contentConfig['languageCols'][$lP]).'</strong></td>';

						// "View page" icon is added:
					$viewLink = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($this->id,$this->backPath,t3lib_BEfunc::BEgetRootLine($this->id),'','','&L='.$lP)).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/zoom.gif','width="12" height="12"').' class="absmiddle" title="" alt="" />'.
						'</a>';

						// Language overlay page header:
					if ($lP)	{

						list($lpRecord) = t3lib_BEfunc::getRecordsByField('pages_language_overlay','pid',$id,'AND sys_language_uid='.intval($lP));
						$params='&edit[pages_language_overlay]['.$lpRecord['uid'].']=edit&overrideVals[pages_language_overlay][sys_language_uid]='.$lP;
						$lPLabel = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon(t3lib_iconWorks::getIconImage('pages_language_overlay',$lpRecord,$this->backPath,'  class="absmiddle"'),'pages_language_overlay',$lpRecord['uid']).
							$viewLink.
							($GLOBALS['BE_USER']->check('tables_modify','pages_language_overlay') ? '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$this->backPath)).'">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/edit2.gif','width="11" height="12"').' title="'.$GLOBALS['LANG']->getLL('edit',1).'" class="absmiddle" alt="" />'.
							'</a>' : '').
							htmlspecialchars(t3lib_div::fixed_lgd_cs($lpRecord['title'],20));
					} else {
						$lPLabel = $viewLink;
					}
					$sCont[$lP]='
						<td nowrap="nowrap">'.$lPLabel.'</td>';
				}
					// Add headers:
				$out.='
					<tr>'.implode($midSep,$cCont).'
					</tr>';
				$out.='
					<tr>'.implode($midSep,$sCont).'
					</tr>';
#debug (array($cCont,$sCont,$languageColumn));
					// Traverse previously built content for the columns:
				foreach($languageColumn as $cKey => $cCont)	{
					$out.='
					<tr>
						<td valign="top">'.implode('</td>'.$midSep.'
						<td valign="top">',$cCont).'</td>
					</tr>';


				}

					// Finally, wrap it all in a table and add the language selector on top of it:
				$out = $languageSelector.'
					<table border="0" cellpadding="0" cellspacing="0" width="480" class="typo3-page-langMode">
						'.$out.'
					</table>';

					// CSH:
				$out.= t3lib_BEfunc::cshItem($this->descrTable,'language_list',$GLOBALS['BACK_PATH']);
			}
}


			// Return content:
		return $out;
	}
	
	
	
		/**
		 * ux_tx_cms_layout::renderText()
		 * 
		 * @param $input
		 * @return 
		 **/
		function renderText($input)	{
		$input = strip_tags($input);
		$input = t3lib_div::fixed_lgd_cs($input,100);
		return nl2br(htmlspecialchars(trim($this->wordWrapper($input))));
	}
	
	
	
		/**
		 * ux_tx_cms_layout::linkEditContent()
		 * 
		 * @param $str
		 * @param $row
		 * @return 
		 **/
		function linkEditContent($str,$row)	{
		$addButton='';
		$onClick = '';

		if ($this->doEdit)	{
				// Setting onclick action for content link:
			$onClick=t3lib_BEfunc::editOnClick('&edit[tt_news]['.$row['uid'].']=edit',$this->backPath);
		}
			// Return link
		return $onClick ? '<a href="#" onclick="'.htmlspecialchars($onClick).'" title="'.$GLOBALS['LANG']->getLL('edit',1).'">'.$str.'</a>'.$addButton : $str;
	}
	
	/**
	 * ux_tx_cms_layout::tt_news_drawHeader()
	 * 
	 * @param $row
	 * @param integer $space
	 * @param boolean $disableMoveAndNewButtons
	 * @param boolean $langMode
	 * @return 
	 **/
	function tt_news_drawHeader($row,$space=0,$disableMoveAndNewButtons=FALSE,$langMode=FALSE)	{
		global $TCA;

			// Load full table description:
		t3lib_div::loadTCA('tt_news');

			// Get record locking status:
		if ($lockInfo=t3lib_BEfunc::isRecordLocked('tt_news',$row['uid']))	{
			$lockIcon='<a href="#" onclick="'.htmlspecialchars('alert('.$GLOBALS['LANG']->JScharCode($lockInfo['msg']).');return false;').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/recordlock_warning3.gif','width="17" height="12"').' title="'.htmlspecialchars($lockInfo['msg']).'" alt="" />'.
						'</a>';
		} else $lockIcon='';

			// Create header with icon/lock-icon/title:
		$header = $this->getIcon('tt_news',$row).
				$lockIcon.
				($langMode ? $this->languageFlag($row['sys_language_uid']) : '').
				'&nbsp;<b><br />'.htmlspecialchars($row['title']).'</b>';#'&nbsp;<b>'.htmlspecialchars($this->CType_labels[$row['CType']]).'</b>';
		$out = '
					<tr>
						<td class="bgColor4">'.$header.'</td>
					</tr>';

			// If show info is set...;
		#if ($this->tt_contentConfig['showInfo'])	{

				// Get processed values:
			$info = Array();
			#$this->getProcessedValue('tt_news','hidden,starttime,endtime,fe_group,datetime,archivedate,title,short,image',$row,$info);

				// Render control panel for the element:
			if ($this->tt_contentConfig['showCommands'] && $this->doEdit)	{

					// Start control cell:
				$out.= '
					<!-- Control Panel -->
					<tr>
						<td class="bgColor5">';

					// Edit content element:
				$params='&edit[tt_news]['.$this->tt_contentData['nextThree'][$row['uid']].']=edit';
				$out.='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$this->backPath)).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/edit2.gif','width="11" height="12"').' title="'.htmlspecialchars($this->nextThree>1?sprintf($GLOBALS['LANG']->getLL('nextThree'),$this->nextThree):$GLOBALS['LANG']->getLL('edit')).'" alt="" />'.
						'</a>';

				if (!$disableMoveAndNewButtons)	{
						// New content element:
					if ($this->option_newWizard)	{
						$onClick="document.location='db_new_content_el.php?id=".$row['pid'].'&sys_language_uid='.$row['sys_language_uid'].'&colPos='.$row['colPos'].'&uid_pid='.(-$row['uid']).'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))."';";
					} else {
						$params='&edit[tt_news]['.(-$row['uid']).']=new';
						$onClick = t3lib_BEfunc::editOnClick($params,$this->backPath);
					}
					$out.='<a href="#" onclick="'.htmlspecialchars($onClick).'">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/new_record.gif','width="16" height="12"').' title="'.$GLOBALS['LANG']->getLL('newAfter',1).'" alt="" />'.
							'</a>';

						// Move element up:
					if ($this->tt_contentData['prev'][$row['uid']])	{
						$params='&cmd[tt_news]['.$row['uid'].'][move]='.$this->tt_contentData['prev'][$row['uid']];
						$out.='<a href="'.htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params)).'">'.
								'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_up.gif','width="11" height="10"').' title="'.$GLOBALS['LANG']->getLL('moveUp',1).'" alt="" />'.
								'</a>';
					} else {
						$out.='<img src="clear.gif" '.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_up.gif','width="11" height="10"',2).' alt="" />';
					}
						// Move element down:
					if ($this->tt_contentData['next'][$row['uid']])	{
						$params='&cmd[tt_news]['.$row['uid'].'][move]='.$this->tt_contentData['next'][$row['uid']];
						$out.='<a href="'.htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params)).'">'.
								'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_down.gif','width="11" height="10"').' title="'.$GLOBALS['LANG']->getLL('moveDown',1).'" alt="" />'.
								'</a>';
					} else {
						$out.='<img src="clear.gif" '.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_down.gif','width="11" height="10"',2).' alt="" />';
					}
				}

					// Hide element:
				$hiddenField = $TCA['tt_news']['ctrl']['enablecolumns']['disabled'];
				if ($hiddenField && $TCA['tt_news']['columns'][$hiddenField] && (!$TCA['tt_news']['columns'][$hiddenField]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields','tt_content:'.$hiddenField)))	{
					if ($row[$hiddenField])	{
						$params='&data[tt_news]['.$row['uid'].']['.$hiddenField.']=0';
						$out.='<a href="'.htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params)).'">'.
								'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_unhide.gif','width="11" height="10"').' title="'.$GLOBALS['LANG']->getLL('unHide',1).'" alt="" />'.
								'</a>';
					} else {
						$params='&data[tt_news]['.$row['uid'].']['.$hiddenField.']=1';
						$out.='<a href="'.htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params)).'">'.
								'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_hide.gif','width="11" height="10"').' title="'.$GLOBALS['LANG']->getLL('hide',1).'" alt="" />'.
								'</a>';
					}
				}

					// Delete
				$params='&cmd[tt_news]['.$row['uid'].'][delete]=1';
				$out.='<a href="'.htmlspecialchars($GLOBALS['SOBE']->doc->issueCommand($params)).'" onclick="'.htmlspecialchars('return confirm('.$GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('deleteWarning')).');').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/garbage.gif','width="11" height="12"').' title="'.$GLOBALS['LANG']->getLL('deleteItem',1).'" alt="" />'.
						'</a>';

					// End cell:
				$out.= '
						</td>
					</tr>';
			}

				// Display info from records fields:
			if (count($info))	{
				$out.= '
					<tr>
						<td class="bgColor4-20">'.implode('<br />',$info).'</td>
					</tr>';
			}
		#}
			// Wrap the whole header in a table:
		return '
				<table border="0" cellpadding="0" cellspacing="0" class="typo3-page-ceHeader">'.($space?'
					<tr>
						<td><img src="clear.gif" height="'.$space.'" alt="" /></td>
					</tr>':'').
					$out.'
				</table>';
	}
	
	
	
	
	
	
	
	} #end of class
	
	
	
	
	
	
	
	


?>