<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "tt_news".
 *
 * Auto generated 07-06-2014 00:22
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
    'title' => 'News',
    'description' => 'Website news with front page teasers and article handling inside.',
    'category' => 'plugin',
    'version' => '8.7.0',
    'module' => 'mod1',
    'state' => 'beta',
    'uploadfolder' => 1,
    'createDirs' => 'uploads/pics',
    'modify_tables' => 'be_groups,be_users',
    'clearcacheonload' => 0,
    'author' => 'Rupert Germann [noerdisch]',
    'author_email' => 'rupi@gmx.li',
    'author_company' => 'www.noerdisch.de',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.7.0-8.7.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);
