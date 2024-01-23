<?php

defined('TYPO3') or die();

// add folder icon
$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
    'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news',
    'value' => 'tt_news',
    'icon' => 'apps-pagetree-folder-contains-news',
];
$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-news'] = 'apps-pagetree-folder-contains-news';
