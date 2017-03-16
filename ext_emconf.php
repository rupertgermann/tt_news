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
	'version' => '7.6.3~netcreators_0.0.2',
	'module' => 'mod1',
	'state' => 'excludeFromUpdates',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => 'be_groups,be_users',
	'clearcacheonload' => 0,
	'author' => 'Rupert Germann [noerdisch], Leonie Philine Bitto [Netcreators]',
	'author_email' => 'rupi@gmx.li',
	'author_company' => 'www.noerdisch.de',
	'constraints' => array(
		'depends' => array(
			'typo3' => '7.6.0-7.6.99',
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
);
