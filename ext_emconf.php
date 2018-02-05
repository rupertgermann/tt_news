<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'News',
    'description' => 'Website news with front page teasers and article handling inside.',
    'category' => 'plugin',
    'version' => '8.7.6',
    'module' => 'mod1',
    'state' => 'beta',
    'uploadfolder' => 1,
    'createDirs' => 'uploads/pics',
    'modify_tables' => 'be_groups,be_users',
    'clearcacheonload' => 0,
    'author' => 'Rupert Germann [noerdisch]',
    'author_email' => 'rupi@gmx.li',
    'author_company' => 'www.noerdisch.de',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-8.7.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
