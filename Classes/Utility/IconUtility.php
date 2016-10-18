<?php
namespace WMDB\TtNews\Utility;

/*
 * wolo.pl '.' studio 2016
 *
 * Simple adapter for routing old Typo's icons path to tt_news icons
 * Temporary fix for TYPO3 7.6
 *
 * The original IconUtility class is deprecated, so this fix is provisional.
 * Probably when working on fixing rest of backend icons, they could be managed using this class.
 */
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class IconUtility extends \TYPO3\CMS\Backend\Utility\IconUtility
{

    /**
     * @param string $backPath Current backpath to PATH_typo3 folder
     * @param string $src Icon file name relative to PATH_typo3 folder
     * @param string $wHattribs Default width/height, defined like 'width="12" height="14"'
     * @param int $outputMode Mode: 0 (zero) is default and returns src/width/height. 1 returns value of src+backpath, 2 returns value of w/h.
     * @return string Returns ' src="[backPath][src]" [wHattribs]'
     * @deprecated if someone continues this tt_news rescuing, it will probably replace backend module with fluid-based
     * @see skinImgFile()
     */
    public static function skinImg($backPath, $src, $wHattribs = '', $outputMode = 0)
    {

        // simply return the new path from Resources
        $newBackPath = ExtensionManagementUtility::extRelPath('tt_news').'Resources/Public/Icons/';
        $newSrc = str_replace('gfx/', '', $src);

        switch ($outputMode)    {
            case 2:
                return $wHattribs;
            case 1:
                return $newSrc;
            default:
                return ' src="'.$newBackPath.$newSrc.'" '.$wHattribs;
        }
    }

}
