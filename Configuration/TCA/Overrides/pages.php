<?php

defined('TYPO3') or die();

// add folder icon
$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
    'label' => 'LLL:EXT:tt_news/Resources/Private/Language/locallang_tca.xlf:tt_news',
    'value' => 'tt_news',
    'icon' => 'apps-pagetree-folder-contains-news',
];

// Register typeicon_classes for different naming variations (like tt_address does)
$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-tt-news'] = 'apps-pagetree-folder-contains-news';
$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-tt_news'] = 'apps-pagetree-folder-contains-news';
$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-ttnews'] = 'apps-pagetree-folder-contains-news';
