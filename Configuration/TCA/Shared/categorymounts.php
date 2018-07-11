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

return [
    'exclude' => 1,
    'l10n_mode' => 'exclude',
    'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xml:tt_news.categorymounts',
    'config' => [
        'type' => 'select',
        'renderType' => 'selectTree',
        'foreign_table' => 'tt_news_cat',
        'foreign_table_where' => ' ORDER BY tt_news_cat.title ASC',
        'size' => 10,
        'autoSizeMax' => 50,
        'minitems' => 0,
        'maxitems' => 500,
        'renderMode' => 'tree',
        'treeConfig' => [
            'expandAll' => true,
            'parentField' => 'parent_category',
            'appearance' => [
                'showHeader' => true,
                'width' => 400
            ]
        ]
    ]
];
