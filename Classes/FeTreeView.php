<?php
/**
 * Created by PhpStorm.
 * User: rupertgermann
 * Date: 08.10.17
 * Time: 17:59
 */

namespace RG\TtNews;


use RG\TtNews\Utility\IconFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Class FeTreeView
 *
 * @package RG\TtNews
 */
class FeTreeView extends Categorytree
{

    /**
     * @var string
     */
    public $TCEforms_itemFormElName = '';
    /**
     * @var array
     */
    public $TCEforms_nonSelectableItemsArray = array();
    /**
     * @var
     */
    public $backPath;
    /**
     * @var FrontendUserAuthentication
     */
    public $FE_USER;
    /**
     * @var
     */
    public $cObjUid;

    /**
     * wraps the record titles in the tree with links or not depending on if they are in the
     * TCEforms_nonSelectableItemsArray.
     *
     * @param    string $title : the title
     * @param           $row
     * @param int       $bank
     *
     * @return    string        the wrapped title
     */
    function wrapTitle($title, $row, $bank = 0)
    {
        $newsConf = &$this->tt_news_obj->conf;
        if ($newsConf['catSelectorTargetPid']) {
            $catSelLinkParams = $newsConf['catSelectorTargetPid'];
            if ($newsConf['itemLinkTarget']) {
                $catSelLinkParams .= ' ' . $newsConf['itemLinkTarget'];
            }
        } else {
            $catSelLinkParams = $GLOBALS['TSFE']->id;
        }


        if ($row['uid'] <= 0) {
            // catmenu Header
            return $this->tt_news_obj->pi_linkTP_keepPIvars($title, array(), $this->tt_news_obj->allowCaching, 1,
                $catSelLinkParams);
        }


        $L = intval(GeneralUtility::_GP('L'));
        if ($L > 0 && !$GLOBALS['TSFE']->linkVars) {
            $GLOBALS['TSFE']->linkVars = '&L=' . $L;
        }

        if ($GLOBALS['TSFE']->sys_language_content && $row['uid']) {
            // get translations of category titles
            $catTitleArr = GeneralUtility::trimExplode('|', $row['title_lang_ol']);
            $syslang = $GLOBALS['TSFE']->sys_language_content - 1;
            $title = $catTitleArr[$syslang] ? $catTitleArr[$syslang] : $title;
        }
        $piVars = &$this->tt_news_obj->piVars;
        $pTmp = $GLOBALS['TSFE']->ATagParams;
        if ($newsConf['displayCatMenu.']['insertDescrAsTitle']) {
            $GLOBALS['TSFE']->ATagParams = ($pTmp ? $pTmp . ' ' : '') . 'title="' . $row['description'] . '"';
        }

        if ($this->getCatNewsCount) {
            $title .= ' (' . $row['newsCount'] . ')';
        }

        if ($newsConf['useHRDates']) {
            $link = $this->tt_news_obj->pi_linkTP_keepPIvars($title, array(
                'cat' => $row['uid'],
                'year' => ($piVars['year'] && $newsConf['catmenuWithArchiveParams'] ? $piVars['year'] : null),
                'month' => ($piVars['month'] && $newsConf['catmenuWithArchiveParams'] ? $piVars['month'] : null)
            ), $this->tt_news_obj->allowCaching, ($newsConf['dontUseBackPid'] ? 1 : 0), $catSelLinkParams);
        } else {
            $link = $this->tt_news_obj->pi_linkTP_keepPIvars($title, array(
                'cat' => $row['uid'],
                'backPid' => null,
                'pointer' => null
            ), $this->tt_news_obj->allowCaching, ($newsConf['dontUseBackPid'] ? 1 : 0), $catSelLinkParams);
        }
        $GLOBALS['TSFE']->ATagParams = $pTmp;

        return $link;
    }


    /**
     * Returns the root icon for a tree/mountpoint (defaults to the globe)
     *
     * @param    array        Record for root.
     *
     * @return    string        Icon image tag.
     */
    function getRootIcon($rec)
    {
        $lConf = &$this->tt_news_obj->conf['displayCatMenu.'];
        $icon = '';

        if ($lConf['catmenuNoRootIcon']) {
            return '';
        }

        if ($lConf['catmenuRootIconFile']) {
            $iconConf['image.']['file'] = $lConf['catmenuIconPath'] . $lConf['catmenuRootIconFile'];
            $iconConf['image.']['file.'] = $lConf['catmenuRootIconFile.'];
            $icon = $GLOBALS['TSFE']->cObj->cObjGetSingle('IMAGE', $iconConf['image.']);
        }

        if (!$icon) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

            return $this->wrapIcon($iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render(), $rec);
        }

        return $icon;
    }


    /**
     * Get icon for the row.
     *
     * @param    array $row Item row.
     *
     * @return    string        Image tag.
     */
    function getIcon($row)
    {
        $lConf = &$this->tt_news_obj->conf['displayCatMenu.'];
        $catIconMode = intval($lConf['catmenuIconMode']);
        $icon = '';

        switch ($catIconMode) {
            // icon from cat db-record
            case 1:
                if ($row['image']) {
                    $iconConf['image.']['file'] = 'uploads/pics/' . $row['image'];
                }
                break;
            // own icons
            case 2:
                $iconConf['image.']['file'] = $lConf['catmenuIconPath'] . $lConf['catmenuIconFile'];
                break;
            // no icons (-1, nothing)
            default:
                $iconConf['image.']['file'] = '';
                break;
        }

        if ($iconConf['image.']['file']) {
            $iconConf['image.']['file.'] = $lConf['catmenuIconFile.'];
            $icon = $GLOBALS['TSFE']->cObj->cObjGetSingle('IMAGE', $iconConf['image.']);
        }

        if (!$icon && !$catIconMode) {
            $icon = '<img' . IconFactory::skinImg('tt_news_cat.gif', 'width="18" height="16"') . ' alt="" />';;
        }

        return $this->wrapIcon($icon, $row);
    }


    /**
     * Wrap the plus/minus icon in a link
     *
     * @param      $icon
     * @param      $cmd
     * @param bool $isExpand
     *
     * @return    string        Link-wrapped input string
     */
    function PMiconATagWrap($icon, $cmd, $isExpand = true)
    {
        if ($this->thisScript && $this->expandable) {
            $newsConf = &$this->tt_news_obj->conf;
            if ($newsConf['catSelectorTargetPid']) {
                $catSelLinkParams = $newsConf['catSelectorTargetPid'];
            } else {
                $catSelLinkParams = $GLOBALS['TSFE']->id;
            }
            if ($this->useAjax) {

                $icon = '<a class="pm pmiconatag" 
                        data-params="' . $cmd . '" 
                        data-isexpand="' . intval($isExpand) . '" 
                        data-pid="' . rawurlencode($catSelLinkParams) . '" 
                        data-cobjuid="' . $this->cObjUid . '" 
                        data-L="' . intval(GeneralUtility::_GP('L')) . '">' . $icon . '</a>';
            } else {
                $anchor = '';
                $name = '';

                $aUrl = $this->tt_news_obj->pi_linkTP_keepPIvars_url(array(), $this->tt_news_obj->allowCaching, 0,
                        $catSelLinkParams) . '&PM=' . $cmd . $anchor;

                $icon = '<a class="pm" href="' . htmlspecialchars($aUrl) . '" ' . $name . '>' . $icon . '</a>';
            }

        }

        return $icon;
    }

    /**
     *
     */
    function initializePositionSaving()
    {
        // Get stored tree structure:
        if ($this->FE_USER->user) {
            // a user is logged in
            $this->stored = unserialize($this->FE_USER->uc['tt_news'][$this->treeName]);
        } else {
            $this->stored = json_decode($_COOKIE[$this->treeName], true);
        }

        if (!is_array($this->stored)) {
            $this->stored = array();
        }

        // PM action
        // (If an plus/minus icon has been clicked, the PM GET var is sent and we must update the stored positions in the tree):
        // 0: mount key, 1: set/clear boolean, 2: item ID (cannot contain "_"), 3: treeName
        $PM = explode('_', GeneralUtility::_GP('PM'));

        if (count($PM) == 4 && $PM[3] == $this->treeName && isset($this->MOUNTS[$PM[0]])) {
            if ($PM[1]) {
                // set
                $this->stored[$PM[0]][$PM[2]] = 1;
                $this->savePosition();
            } else {
                // clear
                unset($this->stored[$PM[0]][$PM[2]]);
                $this->savePosition();
            }
        }
    }

    /**
     * Saves the content of ->stored (keeps track of expanded positions in the tree)
     * $this->treeName will be used as key for BE_USER->uc[] to store it in
     *
     * @return    void
     * @access private
     */
    function savePosition()
    {
        if ($this->FE_USER->user) {
            $this->FE_USER->uc['tt_news'][$this->treeName] = serialize($this->stored);
            $this->FE_USER->writeUC();
        } else {
            setcookie($this->treeName, json_encode($this->stored));
        }
    }

    /**
     * @param array $row
     * @param int   $titleLen
     *
     * @return string
     */
    function getTitleStr($row, $titleLen = 30)
    {
        return htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['title'], $titleLen));
    }
}
