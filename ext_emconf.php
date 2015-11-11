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
	'shy' => 0,
	'version' => '7.6.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1',
	'state' => 'beta',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => 'be_groups,be_users',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Rupert Germann [wmdb]',
	'author_email' => 'rupi@gmx.li',
	'author_company' => 'www.wmdb.de',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '7.6.0-7.6.99',
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
);
