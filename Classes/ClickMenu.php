<?php

/**
 * Additional items for the clickmenu
 *
 * @author        Rupert Germann <rupi@gmx.li>
 * @package       TYPO3
 * @subpackage    tt_news
 * @link          http://www.gnu.org/copyleft/gpl.html
 */

namespace RG\TtNews;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

class ClickMenu
{
    /**
     * @var \TYPO3\CMS\Backend\ClickMenu\ClickMenu
     */
    protected $backRef;

    /**
     * @var BackendUserAuthentication
     */
    protected $backendUser;

    /**
     * @var LanguageService
     */
    protected $languageService;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var array
     */
    protected $LL = array();

    /**
     * Construct
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->languageService = $GLOBALS['LANG'];
        $this->LL = $this->languageService->includeLLFile('EXT:tt_news/Resources/Private/Language/locallang_cm.xlf', false);
    }

    /**
     * Processing of clickmenu items
     *
     * @param \TYPO3\CMS\Backend\ClickMenu\ClickMenu $backRef   parent
     * @param array                                  $menuItems Menu items array to modify
     * @param string                                 $table     Table name
     * @param int                                    $uid       Uid of the record
     *
     * @return array Menu item array, returned after modification
     */
    public function main(&$backRef, $menuItems, $table, $uid)
    {
        $this->backRef = $backRef;
        $this->backendUser = $GLOBALS['BE_USER'];

        if ($table === 'tt_news_cat' && (int)$uid > 0) {
            $rec = BackendUtility::getRecordWSOL($table, $uid);
            // fetch page record to get editing permissions
            $lCP = $this->backendUser->calcPerms(BackendUtility::getRecord('pages', $rec['pid']));
            $doEdit = $lCP & Permission::CONTENT_EDIT;

            $menuItems = array();
            if ($doEdit) {
                $menuItems['edit'] = $this->DB_edit($table, $uid);
                $menuItems['new'] = $this->DB_new($table, $rec);
                $menuItems['newsub'] = $this->DB_new($table, $rec, true);
            }

            $menuItems['info'] = $this->backRef->DB_info($table, $uid);

            if ($doEdit) {
                $menuItems['hide'] = $this->DB_hideUnhide($table, $rec, 'hidden');
                $elInfo = array(
                    GeneralUtility::fixed_lgd_cs(
                        BackendUtility::getRecordTitle('tt_news_cat', $rec),
                        $this->backendUser->uc['titleLen']
                    )
                );
                $menuItems['spacer2'] = 'spacer';
                $menuItems['delete'] = $this->DB_delete($table, $uid, $elInfo);
            }
        }

        return $menuItems;
    }

    /**
     * @param string $table
     * @param int    $uid
     *
     * @return array
     */
    protected function DB_edit($table, $uid)
    {
        $loc = 'top.content.list_frame';
        $link = BackendUtility::getModuleUrl('record_edit', array(
            'edit[' . $table . '][' . $uid . ']' => 'edit'
        ));
        $editOnClick = 'if(' . $loc . '){' . $loc . '.location.href=' . GeneralUtility::quoteJSvalue($link . '&returnUrl=') . '+top.rawurlencode(' . $this->backRef->frameLocation(($loc . '.document')) . '.pathname+' . $this->backRef->frameLocation(($loc . '.document')) . '.search);}';
        return $this->backRef->linkItem(
            $this->backRef->label('edit'),
            $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render(),
            $editOnClick . ';'
        );
    }

    /**
     * @param string $table
     * @param array  $rec
     * @param bool   $newsub
     *
     * @return array
     */
    protected function DB_new($table, $rec, $newsub = false)
    {
        if ($newsub) {
            $parent = $rec['uid'];
        } else {
            $parent = $rec['parent_category'];
        }

        $urlParameters = array(
            'edit' => array(
                $table => array(
                    $rec['pid'] => 'new'
                )
            ),
        );
        if ($parent) {
            $urlParameters['defVals'] = array(
                $table => array(
                    'parent_category' => $parent
                )
            );
        }

        $loc = 'top.content.list_frame';
        $link = BackendUtility::getModuleUrl('record_edit', $urlParameters);
        $editOnClick = 'if(' . $loc . '){' . $loc . '.location.href=' . GeneralUtility::quoteJSvalue($link . '&returnUrl=') . '+top.rawurlencode(' . $this->backRef->frameLocation(($loc . '.document')) . '.pathname+' . $this->backRef->frameLocation(($loc . '.document')) . '.search);}';

        $lkey = 'new';
        if ($newsub) {
            $lkey = 'newsub';
        }

        return $this->backRef->linkItem(
            $this->languageService->getLLL($lkey, $this->LL),
            $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render(),
            $editOnClick . ';'
        );
    }


    /**
     * Adding CM element for hide/unhide of the input record
     *
     * @param string $table     Table name
     * @param array  $rec       Record array
     * @param string $hideField Name of the hide field
     *
     * @return array Item array, element in $menuItems
     * @internal
     */
    protected function DB_hideUnhide($table, $rec, $hideField)
    {
        return $this->DB_changeFlag($table, $rec, $hideField,
            $this->backRef->label(($rec[$hideField] ? 'un' : '') . 'hide'));
    }

    /**
     * Adding CM element for a flag field of the input record
     *
     * @param    string $table     Table name
     * @param    array  $rec       Record array
     * @param    string $flagField Name of the flag field
     * @param    string $title     Menu item Title
     *
     * @return    array        Item array, element in $menuItems
     */
    protected function DB_changeFlag($table, $rec, $flagField, $title)
    {
        $uid = $rec['_ORIG_uid'] ? $rec['_ORIG_uid'] : $rec['uid'];
        $loc = 'top.content.list_frame';
        $editOnClick = 'if(' . $loc . '){' . $loc . '.location.href=' .
            GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('tce_db') . '&redirect=') . '+top.rawurlencode(' .
            $this->backRef->frameLocation($loc . '.document') . '.pathname+' . $this->backRef->frameLocation(($loc . '.document')) . '.search)+' .
            GeneralUtility::quoteJSvalue(
                '&data[' . $table . '][' . $uid . '][' . $flagField . ']=' . ($rec[$flagField] ? 0 : 1) . '&prErr=1&vC=' . $this->backendUser->veriCode()
            ) . ';};';

        $iconIdentifierPrefix = $rec[$flagField] ? 'un' : '';
        return $this->backRef->linkItem(
            $title,
            $this->iconFactory->getIcon('actions-edit-' . $iconIdentifierPrefix . 'hide', Icon::SIZE_SMALL)->render(),
            $editOnClick . 'return false;',
            1
        );
    }

    /**
     * Adding CM element for Delete
     *
     * @param string $table  Table name
     * @param int    $uid    UID for the current record.
     * @param array  $elInfo Label for including in the confirmation message
     *
     * @return array Item array, element in $menuItems
     * @internal
     */
    public function DB_delete($table, $uid, $elInfo)
    {
        $loc = 'top.content.list_frame';
        $jsCode = $loc . '.location.href='
            . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('tce_db') . '&redirect=')
            . '+top.rawurlencode(' . $this->backRef->frameLocation($loc . '.document') . '.pathname+'
            . $this->backRef->frameLocation($loc . '.document') . '.search)+'
            . GeneralUtility::quoteJSvalue(
                '&cmd[' . $table . '][' . $uid . '][delete]=1&prErr=1&vC=' . $this->backendUser->veriCode()
            );
        if ($this->backendUser->jsConfirmation(Permission::PAGE_DELETE)) {
            $title = $this->languageService->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:delete');
            $confirmMessage = sprintf(
                $this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:mess.delete'),
                $elInfo[0]
            );
            $confirmMessage .= BackendUtility::referenceCount(
                $table,
                $uid,
                ' ' . $this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.referencesToRecord')
            );
            $jsCode = 'top.TYPO3.Modal.confirm(' . GeneralUtility::quoteJSvalue($title) . ', '
                . GeneralUtility::quoteJSvalue($confirmMessage) . ')'
                . '.on(\'button.clicked\', function(e) { if (e.target.name === \'ok\') {'
                . $jsCode
                . '} top.TYPO3.Modal.dismiss(); });';
        }
        $editOnClick = 'if(' . $loc . ') { ' . $jsCode . ' }';

        return $this->backRef->linkItem(
            $this->backRef->label('delete'),
            $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render(),
            $editOnClick . 'return false;'
        );
    }
}