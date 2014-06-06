<?php
/**
* Default  TCA_DESCR for "tt_news_cat"
*/

/*
	field.description
	field.syntax
	_field.seeAlso
	field.details
	_field.image
	field.image_descr
*/

$LOCAL_LANG = Array (
	'default' => Array (
			// table description
		'.description' => 'tt_news categories.',
		'_.seeAlso'=>'tt_news,tt_news manual | http://typo3.org/documentation/document-library/tt_news/',

		'title.description' => 'The category title for the default language. The title can act as shortcut to a certain page or as category selector (configured by "categoryMode").',
		'title.details' => 'The display of the category title on the website is configured in the tt_news content element (sheet: category settings) or by TypoScript ("categoryMode"). The titles for additional website languages are inserted in the field "title language overlays".

The category titles/images can act as shortcut to a page or as "category selector" which means: the contents of a news-list ist filtered by category. Filtering by category works recursive for subcategories.',
		'_title.seeAlso'=>'tt_news_cat:title_lang_ol,tt_news_cat:image,tt_news_cat:shortcut,tt_news manual | http://typo3.org/documentation/document-library/tt_news/',

		'title_lang_ol.description' => 'In the field "title language overlays" you can define category titles for other website languages.',
		'title_lang_ol.details' => 'If you have more than one additional website language, you can split the titles with the "|" character.

<strong>Example:</strong>
if you have a website with 3 languages (en,de,fr) it\'s required to write the category title for the default language in the field "title". The titles for german an french are written to the field "title language overlays" like shown in the image below.',
		'_title_lang_ol.image' => 'EXT:tt_news/cshimages/tt_news_cat_title_lang_ol.png',
		'title_lang_ol.image_descr' => 'the order of the overlay titles has to be the same as the order of your system languages.
In this example: en=0, german=1, french=2 ',
		'_title_lang_ol.seeAlso'=>'tt_news_cat:title',

		'parent_category.description' => 'Make the current category to a subcategory of the category in this field.',
		'parent_category.details' => 'In the field "Parent category" you can define the current category as a subcategory of the category which is selected in this field. That will include the current category and the newsitems which have this category assigned when the parent category is selected. This works recursive.
A new parent category for the current category can also be created directly with the "add" wizard (the plus button).
Some categories in the tree are printed in grey and are not selectable: the current category is never selectable, additionally it is possible to define a list of categories that are allowed for a certain BackEnd user group in the TSconfig of this group (f.e.: tt_newsPerms.tt_news_cat.allowedItems = 7,8,9). All categories that are not in this list will not be selectable for members of this group.

<strong>Recursive categories:</strong>
Sometimes it might happen that some nested categories build an endless loop. Recursive categories will not be shown in the category tree. If the current category is part of such a loop, tt_news detects this and shows an error message (see image) in the category form. To break the endless loop it should be sufficient to empty the field "parent_category" from the current category record. After saving the record the error message should disappear.',
		'_parent_category.image' => 'EXT:tt_news/cshimages/tt_news_cat_recursive_error.png',
		'parent_category.image_descr' => 'Error message when the current category is part of a loop of recursive categories.',
		'_parent_category.seeAlso'=>'tt_news:categories,be_groups:TSconfig,be_users',

		'hidden.description' => 'Use this to temporarily exclude this tt_news category from display and all news which are member of this category.',
		'hidden.details' => 'Setting this option is practical while editing a new tt_news db-record. When it is set, the newsitem will not be displayed unless you - if you\'re logged in as backend user - enable the Admin Panel&gt;Preview&gt;Show hidden records option.',
		'_hidden.seeAlso' => 'tt_news_cat:starttime,tt_news_cat:endtime,tt_news_cat:fe_group',

		'starttime.description' => 'The "Start" time determines the date from which the category an its news articles will be visible online. Use this to "publish" news articles from a certain category on a certain date. If "Start" time is not set, the category will be online instantly (unless it is hidden otherwise).',
		'starttime.syntax' => 'Format is DD-MM-YYYY. You may enter the value in other ways - it will be evaluated immediately.
If you insert eg. a "d" the current date is inserted. You can also append any value in the field with eg. "+10" which will add 10 days to the current value. For instance setting the value to "d+10" would select a date 10 days from now.',
		'_starttime.seeAlso' => 'tt_news_cat:hidden,tt_news_cat:endtime,tt_news_cat:fe_group',

		'endtime.description' => 'The "Stop" time is the date from which the category and the news which have this category assigned will not be online anymore.',
		'endtime.syntax' => 'See tt_news_cat / Start (click below).',
		'_endtime.seeAlso' => 'tt_news_cat:starttime,tt_news_cat:hidden,tt_news_cat:fe_group',

		'fe_group.description' => 'Use this to hide the tt_news category and all news which have this category assigned for users that are not member of the website usergroup (fe_groups) which is selected in this field.',
		'fe_group.details' => 'If "Access" is set to a usergroup name, only website users which are members of the selected usergroup will be able to view news with this category when they are logged in. The special option "Hide at login" means the news from this category will not be visible for website users that are logged in. Likewise "Show at login" will make those news visible for any logged in frontend user.',
		'_fe_group.seeAlso' => 'tt_news_cat:starttime,tt_news_cat:endtime,tt_news_cat:hidden,fe_groups',

		'image.description' => 'An image which can be shown instead of (or additionally to) the category title.',
		'image.details' => 'You can upload or assign an image for each news category which is shown f.e. instead of the category title. The behaviour of the category titles/images can be configured in the sheet "Category settings" in the news content element.

The category titles/images can act as shortcut to a page or as "category selector" which means: the contents of a news-list ist filtered by category. Filtering by category works recursive for subcategories.',
		'_image.seeAlso' => 'tt_news_cat:title',

		'shortcut.description' => 'An internal page where the category titles and/or images are linked to.',
		'shortcut.details' => 'Category titles or images can also act as shortcut to an internal page. If this is enabled and a visible page is defined as shortcut, the link from the category title or image points to this page.',
		'_shortcut.seeAlso' => 'tt_news_cat:shortcut_target',

		'shortcut_target.description' => 'Target for news category shortcut.',
		'shortcut_target.details' => 'With the field "Target for ..." you can configure a target for the category shortcut (this setting will have priority over a global setting for link targets in your website).',
		'_shortcut_target.seeAlso' => 'tt_news_cat:shortcut',

		'single_pid.description' => 'The page which is defined here overrides the globally configured "singlePid".',
		'single_pid.details' => 'The field "Single-view page for news from this category" gives you the possibility to define a Single-View page for each category. If a news-record has 2 or more categories assigned the SinglePid from the first category is choosen. The ordering of categories can be changed with the TSvar "catOrderBy". ',

		'description.description' => 'Here you can enter a description for the current category which will be shown as tooltip in the category tree.',
		'description.details' => 'If you have long description texts (>70 chars) Firefox and Mozilla will not display the tooltips correctly.
		Solution:
		There are some Firefox extensions which correct this problem. I tried "Popup Alt Attribute" which works flawless for me.',
	),
	'dk' => Array (
		'.description' => 'Indbygget nyhedssystem - kategorier.',
	),
	'de' => Array (
		'.description' => 'Das News System erlaubt es dem Benutzer, Nachrichten zu kategorisieren. Dadurch wird eine bessere Übersichlichkeit der Meldungen gewährleistet.',
	),
	'no' => Array (
		'.description' => 'Kategorier for det innebygde nyhetsystemet.',
	),
	'it' => Array (
		'.description' => 'Categorie delle News integrate al sito',
	),
	'fr' => Array (
	),
	'es' => Array (
		'.description' => 'Categorías incorporadas por el sistema de noticias.',
	),
	'nl' => Array (
		'.description' => 'Categoriën van het nieuwssysteem.',
	),
	'cz' => Array (
		'.description' => 'Vestavìný systém kategorií zpráv.',
	),
	'pl' => Array (
	),
	'si' => Array (
	),
	'fi' => Array (
		'.description' => 'Sisäänrakennetun uutisjärjestelmän luokat',
	),
	'tr' => Array (
		'.description' => 'Haberler Sistem kategorilerinde yapýlandýrýlýyor',
	),
	'se' => Array (
		'.description' => 'Kategorier i det inbyggda nyhetssystemet.',
	),
	'pt' => Array (
	),
	'ru' => Array (
		'.description' => 'Êàòåãîðèè âñòðîåííîé ñèñòåìû íîâîñòåé.',
	),
	'ro' => Array (
	),
	'ch' => Array (
	),
	'sk' => Array (
	),
	'lt' => Array (
	),
	'is' => Array (
	),
	'hr' => Array (
		'.description' => 'Ugraðeni sustav kategorija za novosti.',
	),
	'hu' => Array (
		'.description' => 'Beépített hír kategóriák',
	),
	'gl' => Array (
	),
	'th' => Array (
	),
	'gr' => Array (
	),
	'hk' => Array (
	),
	'eu' => Array (
	),
	'bg' => Array (
	),
	'br' => Array (
		'.description' => 'Categorias do sistema de notícias',
	),
	'et' => Array (
	),
	'ar' => Array (
	),
	'he' => Array (
	),
	'ua' => Array (
	),
	'lv' => Array (
	),
	'jp' => Array (
	),
	'vn' => Array (
	),
	'ca' => Array (
	),
	'ba' => Array (
	),
	'kr' => Array (
	),
	'eo' => Array (
	),
	'my' => Array (
	),
	'hi' => Array (
	),
);
?>