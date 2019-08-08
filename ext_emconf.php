<?php

$EM_CONF['tt_news'] = [
    'title' => 'News',
    'description' => 'Website news with front page teasers and article handling inside.',
    'category' => 'plugin',
    'version' => '9.5.3',
    'module' => 'mod1',
    'state' => 'beta',
    'uploadfolder' => 1,
    'createDirs' => 'uploads/pics',
    'modify_tables' => 'be_groups,be_users',
    'clearcacheonload' => 0,
    'author' => 'Rupert Germann [pick2webServices]',
    'author_email' => 'rg@pick2.de',
    'author_company' => 'www.pick2.de',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-9.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
