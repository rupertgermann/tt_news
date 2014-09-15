<?php
/**
 * Created by JetBrains PhpStorm.
 * User: rupert.germann
 * Date: 19.10.13
 * Time: 18:50
 * To change this template use File | Settings | File Templates.
 */

class ux_Tx_Workspaces_Controller_PreviewController extends Tx_Workspaces_Controller_PreviewController {

	/**
	 * Basically makes sure that the workspace preview is rendered.
	 * The preview itself consists of three frames, so there are
	 * only the frames-urls we've to generate here
	 *
	 * @return void
	 */
	public function indexAction() {
		// @todo language doesn't always come throught the L parameter
		// @todo Evaluate how the intval() call can be used with Extbase validators/filters
		$language = intval(t3lib_div::_GP('L'));

		$controller = t3lib_div::makeInstance('Tx_Workspaces_Controller_ReviewController', TRUE);
		/** @var $uriBuilder Tx_Extbase_MVC_Web_Routing_UriBuilder */
		$uriBuilder = $this->objectManager->create('Tx_Extbase_MVC_Web_Routing_UriBuilder');

		$wsSettingsPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . 'typo3/';
		$wsSettingsUri = $uriBuilder->uriFor('singleIndex', array(), 'Tx_Workspaces_Controller_ReviewController', 'workspaces', 'web_workspacesworkspaces');
		$wsSettingsParams = '&tx_workspaces_web_workspacesworkspaces[controller]=Review';
		$wsSettingsUrl = $wsSettingsPath . $wsSettingsUri . $wsSettingsParams;

		$viewDomain = t3lib_BEfunc::getViewDomain($this->pageId);
		$wsBaseUrl =  $viewDomain . '/index.php?id=' . $this->pageId . '&L=' . $language;


		$get = $_GET['tx_ttnews'];
		$wsNewsId = intval($get['tt_news']);
		$liveNewsId = intval($get['t3ver_oid']);
		$newsPid = intval($get['pid']);
		if($wsNewsId>0) {
			$wsAddParams = '&tx_ttnews[tt_news]='.$wsNewsId;
		}
		if($liveNewsId>0) {
			$liveAddParams = '&tx_ttnews[tt_news]='.$liveNewsId;
		}
		if ($newsPid > 0) {
			$wsSettingsUrl .= '&id='.$newsPid;
		}

		// @todo - handle new pages here
		// branchpoints are not handled anymore because this feature is not supposed anymore
		if (tx_Workspaces_Service_Workspaces::isNewPage($this->pageId)) {
			$wsNewPageUri = $uriBuilder->uriFor('newPage', array(), 'Tx_Workspaces_Controller_PreviewController', 'workspaces', 'web_workspacesworkspaces');
			$wsNewPageParams = '&tx_workspaces_web_workspacesworkspaces[controller]=Preview';
			$this->view->assign('liveUrl', $wsSettingsPath . $wsNewPageUri . $wsNewPageParams);
		} else {

			$this->view->assign('liveUrl', $wsBaseUrl . '&ADMCMD_noBeUser=1' . $liveAddParams);
		}

		$this->view->assign('wsUrl', $wsBaseUrl . '&ADMCMD_view=1&ADMCMD_editIcons=1&ADMCMD_previewWS=' . $GLOBALS['BE_USER']->workspace . $wsAddParams);
		$this->view->assign('wsSettingsUrl', $wsSettingsUrl);
		$this->view->assign('backendDomain', t3lib_div::getIndpEnv('TYPO3_HOST_ONLY'));
		$GLOBALS['BE_USER']->setAndSaveSessionData('workspaces.backend_domain', t3lib_div::getIndpEnv('TYPO3_HOST_ONLY'));
		$this->pageRenderer->addJsInlineCode("workspaces.preview.lll" , 'TYPO3.LLL.Workspaces = {
			visualPreview: ' . t3lib_div::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.visualPreview', true)) . ',
			listView: ' . t3lib_div::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.listView', true)) . ',
			livePreview: ' . t3lib_div::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.livePreview', true)) . ',
			livePreviewDetail: ' . t3lib_div::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.livePreviewDetail', true)) . ',
			workspacePreview: ' . t3lib_div::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.workspacePreview', true)) . ',
			workspacePreviewDetail: ' . t3lib_div::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.workspacePreviewDetail', true)) . ',
			modeSlider: ' . t3lib_div::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.modeSlider', true)) . ',
			modeVbox: ' . t3lib_div::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.modeVbox', true)) . ',
			modeHbox: ' . t3lib_div::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.modeHbox', true)) . '
		};');

		$resourcePath = t3lib_extMgm::extRelPath('workspaces') . 'Resources/Public/';
		$this->pageRenderer->addJsFile($resourcePath . 'JavaScript/preview.js');
	}
}