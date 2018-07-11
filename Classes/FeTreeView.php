<?php

/*
 * Copyright notice
 *
 * (c) 2004-2018 Rupert Germann <rupi@gmx.li>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

namespace RG\TtNews;

/**
 * [Describe function...]
 *
 */
class FeTreeView extends Categorytree
{
    public $TCEforms_itemFormElName = '';
    public $TCEforms_nonSelectableItemsArray = [];

    /**
     * wraps the record titles in the tree with links or not depending on if they are in the
     * TCEforms_nonSelectableItemsArray.
     *
     * @param    string $title : the title
     * @param    array  $v     : an array with uid and title of the current item.
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
            $catSelLinkParams = $GLOBALS['TSFE']->id;
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

        /**
         * TODO: 27.11.2009
         *
         * this is a "hack" to prevent dropping the "L" parameter during ajax expand/collapse actions
         * --> find out why TSFE->linkVars is empty
         */
        $L = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L'));
        if ($L > 0 && !$GLOBALS['TSFE']->linkVars) {
            $GLOBALS['TSFE']->linkVars = '&L=' . $L;
        }

        if ($GLOBALS['TSFE']->sys_language_content && $row['uid']) {
            // get translations of category titles
            $catTitleArr = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $row['title_lang_ol']);
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
            $link = $this->tt_news_obj->pi_linkTP_keepPIvars($title, [
                'cat' => $row['uid'],
                'year' => ($piVars['year'] && $newsConf['catmenuWithArchiveParams'] ? $piVars['year'] : null),
                'month' => ($piVars['month'] && $newsConf['catmenuWithArchiveParams'] ? $piVars['month'] : null)
            ], $this->tt_news_obj->allowCaching, ($newsConf['dontUseBackPid'] ? 1 : 0), $catSelLinkParams);
        } else {
            $link = $this->tt_news_obj->pi_linkTP_keepPIvars($title, [
                'cat' => $row['uid'],
                'backPid' => null,
                'pointer' => null
            ], $this->tt_news_obj->allowCaching, ($newsConf['dontUseBackPid'] ? 1 : 0), $catSelLinkParams);
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
    public function getRootIcon($rec)
    {
        $lConf = &$this->tt_news_obj->conf['displayCatMenu.'];

        if ($lConf['catmenuNoRootIcon']) {
            return;
        }

        if ($lConf['catmenuRootIconFile']) {
            $iconConf['image.']['file'] = $lConf['catmenuIconPath'] . $lConf['catmenuRootIconFile'];
            $iconConf['image.']['file.'] = $lConf['catmenuRootIconFile.'];
            $icon = $GLOBALS['TSFE']->cObj->cObjGetSingle('IMAGE', $iconConf['image.']);
        }

        return $icon ? $icon : $this->wrapIcon('<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(
            $this->backPath,
                'gfx/i/_icon_website.gif',
            'width="18" height="16"'
        ) . ' alt="" />', $rec);
    }

    /**
     * Get icon for the row.
     * If $this->iconPath and $this->iconName is set, try to get icon based on those values.
     *
     * @param    array        Item row.
     *
     * @return    string        Image tag.
     */
    public function getIcon($row)
    {
        $lConf = &$this->tt_news_obj->conf['displayCatMenu.'];
        $catIconMode = intval($lConf['catmenuIconMode']);
        $icon = '';

        if ($this->iconPath && $this->iconName) {
            $icon = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(
                '',
                $this->iconPath . $this->iconName,
                    'width="18" height="16"'
            ) . ' alt="" />';
        } else {
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
        }

        if (!$icon && !$catIconMode) {
            $icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($this->table, $row, [
                'class' => 'c-recIcon'
            ]);
        }

        return $this->wrapIcon($icon, $row);
    }

    /**
     * Wrap the plus/minus icon in a link
     *
     * @param    string        HTML string to wrap, probably an image tag.
     * @param    string        Command for 'PM' get var
     * @param    [type]        $isExpand: ...
     *
     * @return    string        Link-wrapped input string
     * @access   private
     */
    public function PMiconATagWrap($icon, $cmd, $isExpand = true)
    {
        if ($this->thisScript && $this->expandable) {
            $newsConf = &$this->tt_news_obj->conf;
            if ($newsConf['catSelectorTargetPid']) {
                $catSelLinkParams = $newsConf['catSelectorTargetPid'];
            } else {
                $catSelLinkParams = $GLOBALS['TSFE']->id;
            }
            if ($this->useAjax) {
                // activate dynamic ajax-based tree
                $js = htmlspecialchars('categoryTree.load(\'' . $cmd . '\', ' . intval($isExpand) . ', this, \'' .
                    rawurlencode($catSelLinkParams) . '\', ' . $this->cObjUid . ', ' . intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L')) . ')');

                return '<a class="pm" onclick="' . $js . '">' . $icon . '</a>';
            } else {
                $anchor = '';
                $name = '';

                $aUrl = $this->tt_news_obj->pi_linkTP_keepPIvars_url(
                    [],
                    $this->tt_news_obj->allowCaching,
                    0,
                        $catSelLinkParams
                ) . '&PM=' . $cmd . $anchor;

                return '<a class="pm" href="' . htmlspecialchars($aUrl) . '"' . $name . '>' . $icon . '</a>';
            }
        } else {
            return $icon;
        }
    }

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    public function initializePositionSaving()
    {
        // Get stored tree structure:
        if ($this->FE_USER->user) {
            // a user is logged in
            $this->stored = unserialize($this->FE_USER->uc['tt_news'][$this->treeName]);
        } else {
            $this->stored = json_decode($_COOKIE[$this->treeName], true);
        }

        if (!is_array($this->stored)) {
            $this->stored = [];
        }

        // PM action
        // (If an plus/minus icon has been clicked, the PM GET var is sent and we must update the stored positions in the tree):
        // 0: mount key, 1: set/clear boolean, 2: item ID (cannot contain "_"), 3: treeName
        $PM = explode('_', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('PM'));

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
    public function savePosition()
    {
        if ($this->FE_USER->user) {
            $this->FE_USER->uc['tt_news'][$this->treeName] = serialize($this->stored);
            $this->FE_USER->writeUC();
        } else {
            setcookie($this->treeName, json_encode($this->stored));
        }
    }

    /**
     * [Describe function...]
     *
     * @param     [type]        $row: ...
     * @param     [type]        $titleLen: ...
     *
     * @return    [type]        ...
     */
    public function getTitleStr($row, $titleLen = 30)
    {
        return htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($row['title'], $titleLen));
    }
}
