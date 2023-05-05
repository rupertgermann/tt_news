<?php

defined('TYPO3') || die();

$GLOBALS['TCA']['sys_file_reference']['palettes']['newsImagePalette'] = [
    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette',
    'showitem' => '
        title,alternative,--linebreak--,
        description,--linebreak--,crop
        ',
];
