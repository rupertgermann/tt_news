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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class IconUtility
{
    /**
     * @var boolean
     */
    protected static $isV8 = null;

    /**
     * @return boolean
     */
    protected static function useV8Style()
    {
        if (static::$isV8 === null) {
            static::$isV8 = \version_compare(TYPO3_version, '8', '>=');
        }
        return static::$isV8;
    }

    /**
     *
     * @param unknown $iconName
     * @param array $options
     * @param array $overlays
     */
    public static function getSpriteIcon($iconName, array $options = [], array $overlays = [])
    {
        if (static::useV8Style()) {
            return static::getIconByIdentifier($iconName);
        } else {
            return \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($iconName, $options, $overlays);
        }
    }

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
        if (static::useV8Style()) {
            $identifier = 'ttnews-' . \str_replace(['/', '.gif'], ['-', ''], $src);
            $newBackPath = '';
            $newSrc = static::getIconSrcByIdentifier($identifier);
        } else {
            // simply return the new path from Resources
            $newBackPath = ExtensionManagementUtility::extRelPath('tt_news').'Resources/Public/Icons/';
            $newSrc = str_replace('gfx/', '', $src);
        }

        switch ($outputMode)    {
            case 2:
                return $wHattribs;
            case 1:
                return $newSrc;
            default:
                return ' src="'.$newBackPath.$newSrc.'" '.$wHattribs;
        }
    }

    /**
     * @param string $identifier
     * @param string $size
     * @return string
     */
    public static function getIconSrcByIdentifier($identifier, $size = Icon::SIZE_SMALL)
    {
        $rendered = static::getIconByIdentifier($identifier, $size);
        if (preg_match('/<img\s+src="([^"]+)"/i', $rendered, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * @param string $identifier
     * @param string $size
     * @return string
     */
    public static function getIconByIdentifier($identifier, $size = Icon::SIZE_SMALL)
    {
        return static::getIconFactory()->getIcon($identifier, $size)->render();
    }

    /**
     * @param string $table
     * @param array $row
     * @param string $size
     * @return string
     */
    public static function getRecordIcon($table, array $row, $size = Icon::SIZE_SMALL)
    {
        return static::getIconFactory()->getIconForRecord($table, $row, $size)->render();
    }

    /**
     * @return \TYPO3\CMS\Core\Imaging\IconFactory
     */
    protected static function getIconFactory()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
    }

    /**
     * @return void
     */
    public static function registerAllIconIdentifiers()
    {
        static $registrationDone = null;
        if ($registrationDone !== null) {
            return;
        }
        $registrationDone = true;

        /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        // add folder icon
        $iconRegistry->registerIcon(
            'tcarecords-pages-contains-news',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/res/gfx/ext_icon_ttnews_folder.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-icon_note',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/icon_note.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-list',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/list.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-zoom',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/zoom.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-refresh_n',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/refresh_n.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-edit2',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/edit2.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-minusonly',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/minusonly.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-plusonly',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/plusonly.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-join',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/join.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-joinbottom',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/joinbottom.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-minus',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/minus.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-minusbottom',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/minusbottom.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-plus',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/plus.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-plusbottom',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/plusbottom.gif']
        );
    }
}
