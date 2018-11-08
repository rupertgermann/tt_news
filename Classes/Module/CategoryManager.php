<?php
/**
 * Created by PhpStorm.
 * User: rupertgermann
 * Date: 04.11.18
 * Time: 15:50
 */

namespace RG\TtNews\Module;


use RG\TtNews\Categorytree;
use RG\TtNews\Utility\IconFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CategoryManager extends Categorytree
{

    var $TCEforms_itemFormElName = '';
    var $TCEforms_nonSelectableItemsArray = array();

    var $returnUrl;
    var $showEditIcons;
    var $pageID;
    var $storagePid;
    var $useStoragePid;
    var $mayUserEditCategories;
    var $LL;
    public $backPath;


    /**
     * [Describe function...]
     *
     * @param     [type]        $icon: ...
     * @param     [type]        $row: ...
     *
     * @return    [type]        ...
     */
    function wrapIcon($icon, $row)
    {
        $theIcon = $this->addTagAttributes($icon, $this->titleAttrib . '="' . $this->getTitleAttrib($row) . '"');

        if ($row['uid'] > 0 && !isset($row['doktype'])) {
            // no clickmenu for pages
            $theIcon = BackendUtility::wrapClickMenuOnIcon($theIcon, 'tt_news_cat_CM',
                $row['uid'], 0, '&bank=' . $this->bank);
            $theIcon = '<span class="dragIcon" id="dragIconID_' . $row['uid'] . '">' . $theIcon . '</span>';
        } else {
            $theIcon = '<span class="dragIcon" id="dragIconID_0">' . $theIcon . '</span>';
        }

        return $theIcon;
    }

    /**
     * wraps the record titles in the tree with links or not depending on if they are in the
     * TCEforms_nonSelectableItemsArray.
     *
     * @param    string $title : the title
     * @param    array  $v     : an array with uid and title of the current item.
     *
     * @return    string        the wrapped title
     */
    function wrapTitle($title, $v, $bank = 0)
    {

        // TODO: language overlay


        if ($v['uid'] > 0) {
            $hrefTitle = htmlentities('[id=' . $v['uid'] . '] ' . $v['description']);
            $out = '<a href="#" class="filter-category" data-category="' . $v['uid'] . '" data-target="ttnewslist" data-pid="' . intval($this->pageID) . '" title="' . $hrefTitle . '">' . $title . '</a>';

            // Wrap title in a drag/drop span.
            $out = '<span class="dragTitle" id="dragTitleID_' . $v['uid'] . '">' . $out . '</span>';
            if ($this->showEditIcons) {
                $out .= $this->makeControl('tt_news_cat', $v);
            }
        } else {
            $grsp = '';
            if ($this->storagePid != $this->pageID) {
                $grsp = ' GRSP';
            }
            if ($this->useStoragePid) {
                $pidLbl = sprintf($this->getLanguageService()->sL('LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.treeSelect.pageTitleSuffix'),
                    $this->storagePid . $grsp);
            } else {
                $pidLbl = $this->getLanguageService()->sL('LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.treeSelect.pageTitleSuffixNoGrsp');

            }
            $pidLbl = ' <span class="typo3-dimmed"><em>' . $pidLbl . '</em></span>';
            $hrefTitle = $this->getLanguageService()->sL('LLL:EXT:tt_news/Classes/Module/locallang.xml:showAllResetSel');

            $out = '<span class="dragTitle" id="dragTitleID_0">
						<a href="' . BackendUtility::getModuleUrl('web_txttnewsM1') . '&id=' . $this->pageID . '" title="' . $hrefTitle . '">' . $title . '</a>
					</span>' . $pidLbl;
        }

        return $out;
    }

    /**
     * Creates the control panel for a single record in the listing.
     *
     * @param    string        The table
     * @param    array         The record for which to make the control panel.
     *
     * @return    string        HTML table with the control panel (unless disabled)
     */
    function makeControl($table, $row)
    {
        global $TCA;

        // Initialize:
        $cells = array();
        // "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
        if ($this->mayUserEditCategories) {
            $params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
            $cells[] = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params,
                    $this->backPath, $this->returnUrl)) . '">' .
                '<img' . IconFactory::skinImg('edit2' . (!$TCA[$table]['ctrl']['readOnly'] ? '' : '_d') . '.gif',
                    'width="11" height="12"') . ' title="' . $this->getLanguageService()->getLLL('edit', $this->LL) . '" alt="" />' .
                '</a>';
        }

        // "Hide/Unhide" links:
        $hiddenField = $TCA[$table]['ctrl']['enablecolumns']['disabled'];
        if ($this->mayUserEditCategories && $hiddenField && $TCA[$table]['columns'][$hiddenField] &&
            (!$TCA[$table]['columns'][$hiddenField]['exclude'] || $this->getBackendUser()->check('non_exclude_fields',
                    $table . ':' . $hiddenField))
        ) {
            /**
             * @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory
             */
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            if ($row[$hiddenField]) {
                $params = '&data[' . $table . '][' . $row['uid'] . '][' . $hiddenField . ']=0';
                $cells[] = '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $this->issueCommand($params,
                            $this->returnUrl) . '\');') . '">' .
                    $iconFactory->getIcon('actions-edit-unhide',Icon::SIZE_SMALL)->render() .
                    '</a>';
            } else {
                $params = '&data[' . $table . '][' . $row['uid'] . '][' . $hiddenField . ']=1';
                $cells[] = '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $this->issueCommand($params,
                            $this->returnUrl) . '\');') . '">' .
                    $iconFactory->getIcon('actions-edit-hide',Icon::SIZE_SMALL)->render() .
                    '</a>';
            }
        }

        return '
				<!-- CONTROL PANEL: ' . $table . ':' . $row['uid'] . ' -->
				<span style="padding:0 0 0 7px;">' . implode('', $cells) . '</span>';
    }

    /**
     * [Describe function...]
     *
     * @param    [type]        $params: ...
     * @param    [type]        $rUrl: ...
     *
     * @return   string
     */
    function issueCommand($params, $rUrl = ''): string
    {
        $rUrl = $rUrl ?: GeneralUtility::getIndpEnv('REQUEST_URI');

        $urlParameters = GeneralUtility::explodeUrl2Array($params, true);
        $urlParameters['prErr'] = '1';
        $urlParameters['uPT'] = '1';

        $url = BackendUtility::getModuleUrl('tce_db', $urlParameters);
        $url .= '&redirect=' . ($rUrl == -1 ? "'+T3_THIS_LOCATION+'" : rawurlencode($rUrl));

        return $url;
    }


}
