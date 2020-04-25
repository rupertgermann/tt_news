<?php

$EM_CONF['tt_news'] = [
    'title' => 'News',
    'description' => 'Website news with front page teasers and article handling inside.',
    'category' => 'plugin',
    'version' => '10.0.0',
    'module' => 'mod1',
    'state' => 'beta',
    'modify_tables' => 'be_groups,be_users',
    'clearcacheonload' => 0,
    'author' => 'Rupert Germann [pick2webServices]',
    'author_email' => 'rg@pick2.de',
    'author_company' => 'www.pick2.de',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-10.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
