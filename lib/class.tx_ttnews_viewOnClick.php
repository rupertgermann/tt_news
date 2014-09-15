<?php
/**
 * Created by JetBrains PhpStorm.
 * User: rupert.germann
 * Date: 19.10.13
 * Time: 16:02
 * To change this template use File | Settings | File Templates.
 */

class tx_ttnews_viewOnClick {
	public function preProcess(&$pageUid, $backPath, $rootLine, $anchorSection, &$viewScript, &$additionalGetVars, $switchFocus) {
		$newsdata = $_REQUEST['data']['tt_news'];
		$isNews = is_array($newsdata);
		if($isNews) {
			$newsID = intval(key($newsdata));

			if ($isNews && $newsID > 0) {
				if ($GLOBALS['BE_USER']->workspace !== 0) {
					// if we are in a workspace, find the record's live uid
					$rec = t3lib_BEfunc::getRecord('tt_news', $newsID);
					$origRec = t3lib_BEfunc::getRecord('tt_news', $rec['t3ver_oid']);
				}

				$pagesTSC = t3lib_BEfunc::getPagesTSconfig($pageUid); // get page TSconfig
				if ($pagesTSC['tx_ttnews.']['singlePid']) {
					$additionalGetVars .= '&id='.$pagesTSC['tx_ttnews.']['singlePid'].'&tx_ttnews[tt_news]='.$newsID.'&no_cache=1';
					if ($GLOBALS['BE_USER']->workspace !== 0) {
						$additionalGetVars .= '&tx_ttnews[t3ver_oid]='.$rec['t3ver_oid'];
						$additionalGetVars .= '&tx_ttnews[pid]='.$origRec['pid'];
					}
				}
			}

		}
	}
}