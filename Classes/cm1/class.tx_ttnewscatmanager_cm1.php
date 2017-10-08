<?php
namespace RG\TtNews\cm1;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2009 Rupert Germann <rupi@gmx.li>
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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Additional items for the clickmenu.
 *
 * $Id$
 *
 * @author  Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage tt_news
 */
class tx_ttnewscatmanager_cm1
{

    /**
     * @var \TYPO3\CMS\Backend\ClickMenu\ClickMenu
     */
    protected $backRef;
    /**
     * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected $beUser;
    /**
     * @var \TYPO3\CMS\Lang\LanguageService
     */
    protected $LANG;
    protected $LL;

    function main(&$backRef, $menuItems, $tableID, $srcId)
    {
        $this->backRef = $backRef;
        $this->beUser = $GLOBALS['BE_USER'];
        $this->LANG = $GLOBALS['LANG'];
        $this->includeLocalLang();

        if (($tableID == 'dragDrop_tt_news_cat' || $tableID == 'tt_news_cat_CM') && $srcId) {
            $table = 'tt_news_cat';

            $rec = BackendUtility::getRecordWSOL($table, $srcId);
            // fetch page record to get editing permissions
            $lCP = $this->beUser->calcPerms(BackendUtility::getRecord('pages', $rec['pid']));
            $doEdit = $lCP & 16;

            if ($tableID == 'tt_news_cat_CM') {
                $menuItems = array();
                if ($doEdit) {
                    $menuItems['edit'] = $this->DB_edit($table, $srcId);
                    $menuItems['new'] = $this->DB_new($table, $rec);
                    $menuItems['newsub'] = $this->DB_new($table, $rec, true);
                }

                $menuItems['info'] = $this->backRef->DB_info($table, $srcId);

                if ($doEdit) {
                    $menuItems['hide'] = $this->DB_hideUnhide($table, $rec, 'hidden');
                    $elInfo = array(GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle('tt_news_cat', $rec), $this->beUser->uc['titleLen']));
                    $menuItems['spacer2'] = 'spacer';
                    $menuItems['delete'] = $this->DB_delete($table, $srcId, $elInfo);
                }
            }
        }

        return $menuItems;
    }

    /**
     * @param $table
     * @param $uid
     * @return array
     */
    function DB_edit($table, $uid)
    {
        $loc = 'top.content.list_frame';
        $editOnClick = 'if(' . $loc . '){' . $loc . ".location.href=top.TS.PATH_typo3+'alt_doc.php?returnUrl='+top.rawurlencode(" .
            $this->backRef->frameLocation($loc . '.document') . ")+'&edit[" . $table . "][" . $uid . "]=edit';}";

        return $this->backRef->linkItem(
            $this->backRef->label('edit'),
            $this->backRef->excludeIcon(IconUtility::getSpriteIcon('actions-document-open')),
            $editOnClick . 'return hideCM();'
        );
    }

    /**
     * @param $table
     * @param $rec
     * @param bool $newsub
     * @return array
     */
    function DB_new($table, $rec, $newsub = false)
    {
        $loc = 'top.content.list_frame';

        if ($newsub) {
            $parent = $rec['uid'];
        } else {
            $parent = $rec['parent_category'];
        }

        $editOnClick = 'if(' . $loc . '){' . $loc . ".location.href=top.TS.PATH_typo3+'" .
            "alt_doc.php?returnUrl='+top.rawurlencode(" . $this->backRef->frameLocation($loc . '.document') . ")+'&edit[" . $table . "][" . $rec['pid'] . "]=new" .
            ($parent ? '&defVals[' . $table . '][parent_category]=' . $parent : '') . '\';}';
        $lkey = 'new';
        if ($newsub) {
            $lkey = 'newsub';
        }

        return $this->backRef->linkItem(
            $this->LANG->getLLL($lkey, $this->LL),
            $this->backRef->excludeIcon(IconUtility::getSpriteIcon('actions-document-new')),
            $editOnClick . 'return hideCM();'
        );
    }


    /**
     * Adding CM element for hide/unhide of the input record
     *
     * @param    string $table Table name
     * @param    array $rec Record array
     * @param    string $hideField Name of the hide field
     * @return    array        Item array, element in $menuItems
     * @internal
     */
    function DB_hideUnhide($table, $rec, $hideField)
    {
        return $this->DB_changeFlag($table, $rec, $hideField, $this->backRef->label(($rec[$hideField] ? 'un' : '') . 'hide'), 'hide');
    }

    /**
     * Adding CM element for a flag field of the input record
     *
     * @param    string $table Table name
     * @param    array $rec Record array
     * @param    string $flagField Name of the flag field
     * @param    string $title Menu item Title
     * @param    string $name Name of the item used for icons and labels
     * @return    array        Item array, element in $menuItems
     */
    function DB_changeFlag($table, $rec, $flagField, $title)
    {
        $uid = $rec['_ORIG_uid'] ? $rec['_ORIG_uid'] : $rec['uid'];
        $loc = 'top.content.list_frame';
        $editOnClick = 'if(' . $loc . '){' . $loc . ".location.href=top.TS.PATH_typo3+'tce_db.php?redirect='+top.rawurlencode(" . $this->backRef->frameLocation($loc . '.document') . ")+'" .
            "&data[" . $table . '][' . $uid . '][' . $flagField . ']=' . ($rec[$flagField] ? 0 : 1) . '&prErr=1&vC=' . $this->beUser->veriCode() . BackendUtility::getUrlToken('tceAction') . "';hideCM();}";

        return $this->backRef->linkItem(
            $title,
            $this->backRef->excludeIcon(IconUtility::getSpriteIcon('actions-edit-' . ($rec[$flagField] ? 'un' : '') . 'hide')),
            $editOnClick . 'return false;',
            1
        );
    }

    /**
     * Adding CM element for Delete
     *
     * @param    string $table Table name
     * @param    integer $uid UID for the current record.
     * @param    array $elInfo Label for including in the confirmation message, EXT:lang/locallang_core.php:mess.delete
     * @return    array        Item array, element in $menuItems
     * @internal
     */
    function DB_delete($table, $uid, $elInfo)
    {
        $loc = 'top.content.list_frame';
        if ($this->beUser->jsConfirmation(4)) {
            $conf = "confirm(" . GeneralUtility::quoteJSvalue(sprintf($this->LANG->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.delete'), $elInfo[0]) .
                    BackendUtility::referenceCount($table, $uid, ' (There are %s reference(s) to this record!)')) . ")";
        } else {
            $conf = '1==1';
        }
        $editOnClick = 'if(' . $loc . " && " . $conf . " ){" . $loc . ".location.href=top.TS.PATH_typo3+'tce_db.php?redirect='+top.rawurlencode(" .
            $this->backRef->frameLocation($loc . '.document') . ")+'" .
            "&cmd[" . $table . '][' . $uid . '][DDdelete]=1&prErr=1&vC=' . $this->beUser->veriCode() . BackendUtility::getUrlToken('tceAction') . "';hideCM();}";

        return $this->backRef->linkItem(
            $this->LANG->getLLL('delete', $this->LL),
            $this->backRef->excludeIcon(IconUtility::getSpriteIcon('actions-edit-delete')),
            $editOnClick . 'return false;'
        );
    }

    /**
     *
     */
    function includeLocalLang()
    {
        $llFile = ExtensionManagementUtility::extPath('tt_news') . 'cm1/locallang.xml';
        /** @var TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser $parser */
        $parser = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Localization\\Parser\\LocallangXmlParser');
        $this->LL = $parser->getParsedData($llFile, $this->LANG->lang);
    }
}

