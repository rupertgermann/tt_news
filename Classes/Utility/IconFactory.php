<?php

namespace RG\TtNews\Utility;

/*
 * wolo.pl '.' studio 2016
 *
 * Simple adapter for routing old Typo's icons path to tt_news icons
 */
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IconFactory extends \TYPO3\CMS\Core\Imaging\IconFactory
{

    /**
     * @param string $backPath   Current backpath to PATH_typo3 folder
     * @param string $src        Icon file name relative to PATH_typo3 folder
     * @param string $wHattribs  Default width/height, defined like 'width="12" height="14"'
     * @param int    $outputMode Mode: 0 (zero) is default and returns src/width/height. 1 returns value of
     *                           src+backpath, 2 returns value of w/h.
     *
     * @return string Returns ' src="[backPath][src]" [wHattribs]'
     * @see skinImgFile()
     */
    public static function skinImg($src, $wHattribs = '', $outputMode = 0)
    {

        // simply return the new path from Resources
        $newBackPath = ExtensionManagementUtility::extRelPath('tt_news') . 'Resources/Public/Icons/';
        $newSrc = str_replace('gfx/', '', $src);

        switch ($outputMode) {
            case 2:
                return $wHattribs;
            case 1:
                return $newSrc;
            default:
                return ' src="' . $newBackPath . $newSrc . '" ' . $wHattribs;
        }
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

        /**
         * @var IconRegistry $iconRegistry
         */
        $iconRegistry
            = GeneralUtility::makeInstance(IconRegistry::class);

        $iconRegistry->registerIcon(
            'tcarecords-pages-contains-news',
            BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ext_icon_ttnews_folder.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-icon_note',
            BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/icon_note.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-list',
            BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/list.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-zoom',
            BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/zoom.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-refresh_n',
            BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/refresh_n.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-edit2',
            BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/edit2.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-minusonly',
            BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/minusonly.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-plusonly',
            BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/plusonly.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-join',
            BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/join.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-joinbottom',
            BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/joinbottom.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-minus',
            BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/minus.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-minusbottom',
            BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/minusbottom.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-plus',
            BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/plus.gif']
        );
        $iconRegistry->registerIcon(
            'ttnews-gfx-ol-plusbottom',
            BitmapIconProvider::class,
            ['source' => 'EXT:tt_news/Resources/Public/Icons/ol/plusbottom.gif']
        );
    }

}
