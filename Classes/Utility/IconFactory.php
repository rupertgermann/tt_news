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
        $newBackPath = ExtensionManagementUtility::extPath('tt_news') . 'Resources/Public/Icons/';
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
