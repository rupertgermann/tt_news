<?php
/**
 * Created by PhpStorm.
 * User: rupertgermann
 * Date: 08.10.17
 * Time: 17:59
 */

namespace RG\TtNews\Tree;

use RG\TtNews\Utility\IconFactory;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * Class FeTreeView
 */
class FeTreeView extends Categorytree
{
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
    public $treeName;

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
    public function wrapTitle($title, $row, $bank = 0)
    {
        $newsConf = &$this->tt_news_obj->conf;
        if ($newsConf['catSelectorTargetPid']) {
            $catSelLinkParams = $newsConf['catSelectorTargetPid'];
            if ($newsConf['itemLinkTarget']) {
                $catSelLinkParams .= ' ' . $newsConf['itemLinkTarget'];
            }
        } else {
            $relevantParametersForCachingFromPageArguments = [];
            $pageArguments = $GLOBALS['REQUEST']->getAttribute('routing');
            $queryParams = $pageArguments->getDynamicArguments();
            if (!empty($queryParams) && ($pageArguments->getArguments()['cHash'] ?? false)) {
                $queryParams['id'] = $pageArguments->getPageId();
                $relevantParametersForCachingFromPageArguments = GeneralUtility::makeInstance(CacheHashCalculator::class)->getRelevantParameters(HttpUtility::buildQueryString($queryParams));
            }
            $catSelLinkParams = $relevantParametersForCachingFromPageArguments;
        }

        if ($row['uid'] <= 0) {
            // catmenu Header
            return $this->tt_news_obj->pi_linkTP_keepPIvars(
                $title,
                [],
                $this->tt_news_obj->allowCaching,
                1,
                $catSelLinkParams
            );
        }

        $L = (int)(GeneralUtility::_GP('L'));
        if ($L > 0 && !$GLOBALS['TSFE']->linkVars) {
            $GLOBALS['TSFE']->linkVars = '&L=' . $L;
        }

        if (GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'contentId') && $row['uid']) {
            // get translations of category titles
            $catTitleArr = GeneralUtility::trimExplode('|', $row['title_lang_ol']);
            $syslang = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'contentId') - 1;
            $title = $catTitleArr[$syslang] ?: $title;
        }
        $piVars = &$this->tt_news_obj->piVars;
        $pTmp = $GLOBALS['TSFE']->config['config']['ATagParams'] ?? '';
        if ($newsConf['displayCatMenu.']['insertDescrAsTitle']) {
            $GLOBALS['TSFE']->config['config']['ATagParams'] = ($pTmp ? $pTmp . ' ' : '') . 'title="' . $row['description'] . '"';
        }

        if ($this->getCatNewsCount) {
            $title .= ' (' . $row['newsCount'] . ')';
        }

        if ($newsConf['useHRDates']) {
            $link = $this->tt_news_obj->pi_linkTP_keepPIvars($title, [
                'cat' => $row['uid'],
                'year' => ($piVars['year'] && $newsConf['catmenuWithArchiveParams'] ? $piVars['year'] : null),
                'month' => ($piVars['month'] && $newsConf['catmenuWithArchiveParams'] ? $piVars['month'] : null),
            ], $this->tt_news_obj->allowCaching, ($newsConf['dontUseBackPid'] ? 1 : 0), $catSelLinkParams);
        } else {
            $link = $this->tt_news_obj->pi_linkTP_keepPIvars($title, [
                'cat' => $row['uid'],
                'backPid' => null,
                'pointer' => null,
            ], $this->tt_news_obj->allowCaching, ($newsConf['dontUseBackPid'] ? 1 : 0), $catSelLinkParams);
        }
        $GLOBALS['TSFE']->config['config']['ATagParams'] = $pTmp;

        return $link;
    }

    /**
     * Returns the root icon for a tree/mountpoint (defaults to the globe)
     *
     * @param    array        Record for root.
     *
     * @return    string        Icon image tag.
     */
    public function getRootIcon($rec)
    {
        $iconConf = [];
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
            $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
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
    public function getIcon($row): string
    {
        $iconConf = [];
        $lConf = &$this->tt_news_obj->conf['displayCatMenu.'];
        $catIconMode = (int)($lConf['catmenuIconMode']);
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
            $icon = '<img' . IconFactory::skinImg('tt_news_cat.gif', 'width="18" height="16"') . ' alt="" />';
        }

        return $this->wrapIcon($icon, $row);
    }

    /**
     * Generate the plus/minus icon for the browsable tree.
     *
     * @param    array          record for the entry
     * @param    int        The current entry number
     * @param    int        The total number of entries. If equal to $a, a "bottom" element is returned.
     * @param    int        The number of sub-elements to the current element.
     * @param    bool        The element was expanded to render subelements if this flag is set.
     *
     * @return    string        Image tag with the plus/minus icon.
     */
    public function PMicon($row, $a, $c, $nextCount, $exp)
    {
        if ($this->expandable) {
            $PM = $nextCount ? ($exp ? 'minus' : 'plus') : 'join';
        } else {
            $PM = 'join';
        }

        $BTM = ($a == $c) ? 'bottom' : '';
        /**
         * @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory
         */
        $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
        $icon = $iconFactory->getIcon('ttnews-gfx-ol-' . $PM . $BTM, Icon::SIZE_SMALL)->render();

        if ($nextCount) {
            $cmd = $this->bank . '_' . ($exp ? '0_' : '1_') . $row['uid'] . '_' . $this->treeName;
            $icon = $this->PMiconATagWrap($icon, $cmd, !$exp);
        }

        return $icon;
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
    public function PMiconATagWrap($icon, $cmd, $isExpand = true)
    {
        if ($this->thisScript && $this->expandable) {
            $newsConf = &$this->tt_news_obj->conf;
            if ($newsConf['catSelectorTargetPid']) {
                $catSelLinkParams = $newsConf['catSelectorTargetPid'];
            } else {
                $relevantParametersForCachingFromPageArguments = [];
                $pageArguments = $GLOBALS['REQUEST']->getAttribute('routing');
                $queryParams = $pageArguments->getDynamicArguments();
                if (!empty($queryParams) && ($pageArguments->getArguments()['cHash'] ?? false)) {
                    $queryParams['id'] = $pageArguments->getPageId();
                    $relevantParametersForCachingFromPageArguments = GeneralUtility::makeInstance(CacheHashCalculator::class)->getRelevantParameters(HttpUtility::buildQueryString($queryParams));
                }
                $catSelLinkParams = $relevantParametersForCachingFromPageArguments;
            }
            if ($this->useAjax) {
                $icon = '<a class="pm pmiconatag"
                        data-params="' . $cmd . '"
                        data-isexpand="' . (int)$isExpand . '"
                        data-pid="' . rawurlencode((string)$catSelLinkParams) . '"
                        data-cobjuid="' . $this->cObjUid . '"
                        data-L="' . (int)(GeneralUtility::_GP('L')) . '">' . $icon . '</a>';
            } else {
                $anchor = '';
                $name = '';

                $aUrl = $this->tt_news_obj->pi_linkTP_keepPIvars_url(
                    [],
                    $this->tt_news_obj->allowCaching,
                    0,
                    $catSelLinkParams
                ) . '&PM=' . $cmd . $anchor;

                $icon = '<a class="pm" href="' . htmlspecialchars($aUrl) . '" ' . $name . '>' . $icon . '</a>';
            }
        }

        return $icon;
    }

    public function initializePositionSaving()
    {
        // Get stored tree structure:
        if ($this->FE_USER->user) {
            // a user is logged in
            $this->stored = json_decode((string)$this->FE_USER->uc['tt_news'][$this->treeName], true, 512, JSON_THROW_ON_ERROR);
        } else {
            $this->stored = json_decode(($_COOKIE[$this->treeName] ?? ''), true, 512, JSON_THROW_ON_ERROR);
        }

        if (!is_array($this->stored)) {
            $this->stored = [];
        }

        // PM action
        // (If a plus/minus icon has been clicked, the PM GET var is sent and we
        // must update the stored positions in the tree):
        // 0: mount key, 1: set/clear boolean, 2: item ID (cannot contain "_"), 3: treeName
        $PM = explode('_', (string)GeneralUtility::_GP('PM'));
        if (count($PM) === 4 && $PM[3] == $this->treeName) {
            if (isset($this->MOUNTS[$PM[0]])) {
                // set
                if ($PM[1]) {
                    $this->stored[$PM[0]][$PM[2]] = 1;
                    $this->savePosition();
                } else {
                    unset($this->stored[$PM[0]][$PM[2]]);
                    $this->savePosition();
                }
            }
        }
    }

    /**
     * Saves the content of ->stored (keeps track of expanded positions in the tree)
     * $this->treeName will be used as key for BE_USER->uc[] to store it in
     */
    public function savePosition()
    {
        if ($this->FE_USER->user) {
            $this->FE_USER->uc['tt_news'][$this->treeName] = json_encode($this->stored, JSON_THROW_ON_ERROR);
            $this->FE_USER->writeUC();
        } else {
            setcookie($this->treeName, json_encode($this->stored, JSON_THROW_ON_ERROR));
        }
    }

    /**
     * @param array $row
     * @param int   $titleLen
     *
     * @return string
     */
    public function getTitleStr($row, $titleLen = 30)
    {
        return htmlspecialchars((string)GeneralUtility::fixed_lgd_cs($row['title'], $titleLen));
    }
}
