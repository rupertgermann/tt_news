<?php
/**
* Default  TCA_DESCR for "tt_news"
*/
// 		'title.description' => 'Enter a headline for the tt_news db-record.',
// 		'title.syntax' => '',
// 		'title.details' => 'The value inserted here will subsitute the marker ###NEWS_TITLE### in the html template.',
// 		'_title.seeAlso' => '',
// 		' _title.image' => '',
// 		'title.image_descr' => '',

$LOCAL_LANG = Array (
	'default' => Array (
			// table description
		'.description' => 'Versatile news system for TYPO3.',
		'.details' => '"News" items are typically those which goes on your frontpage on the websites and allows you to link down to a page with the full story (the "single view"). They may also represent links to internal pages in the system or links to external pages.',
		'_.seeAlso'=>'tt_news_cat,tt_news manual | http://typo3.org/documentation/document-library/tt_news/',
			// title
		'title.description' => 'Enter a headline for the tt_news db-record.',
		'title.details' => 'The value inserted here will substitute the marker ###NEWS_TITLE### in the html template. If "substitutePageTitle" is enabled in TS setup the title of the page with the single view on it will be set to the title of the news article (the title of the indexed search result will also show the title of the news article).',
			// type
		'type.description' => 'Here you can define the "type" of the newsitem (normal news article, link to internal page, link to external page).',
		'type.details' => 'Possible types are:
- News: This type is used for normal news articles. Only these news will have a link to a SINGLE view.
- Link External page: These news records are only showing in list views (= LIST and LATEST). The links from these news records will point directly to the URL which is configured in the field "External URL".
- Link internal page: This news records are also showing only in lists (list, latest, search). The target for these links is configured globally in the Constant editor (advanced->target for internal links).',
			// hidden
		'hidden.description' => 'Use this to temporarily exclude this tt_news db-record from display.',
		'hidden.details' => 'Setting this option is practical while editing a new tt_news db-record. When it is set, the newsitem will not be displayed unless you - if you\'re logged in as backend user - enable the Admin Panel&gt;Preview&gt;Show hidden records option.',
		'_hidden.seeAlso' => 'tt_news:starttime,tt_news:endtime,tt_news:fe_group',
			// starttime
		'starttime.description' => 'The "Start" time determines the date from which the tt_news db-record will be available online.',
		'_starttime.seeAlso' => 'tt_news:endtime,tt_news:hidden,tt_news:fe_group',
			// endtime
		'endtime.description' => 'The "End" time determines the date from which the tt_news db-record will NOT be available online anymore.',
		'_endtime.seeAlso' => 'tt_news:starttime,tt_news:hidden,tt_news:fe_group',
			// editlock
		'editlock.description' => 'If this is enabled non-admin users can\'t open the record anymore.',
		'_editlock.seeAlso' => 'pages:editlock',
			// fe_group
		'fe_group.description' => 'If "Access" is set to a usergroup name, only website users which are members of the selected usergroup will be able to view the news article when they are logged in. The special option "Hide at login" means the news article will &lt;em&gt;not&lt;/em&gt; be visible for website users that are logged in. Likewise &quot;Show at login&quot; will make the news article visible for any logged in frontend user.',
		'_fe_group.seeAlso' => 'tt_news:starttime,tt_news:endtime,tt_news:hidden',
			// datetime
		'datetime.description' => 'The value entered here will be shown as date and/or time in news articles.',
		'datetime.details' => 'The value of this field affects several things:

- newsitems in lists and in the archivemenu are ordered by this field by default.
- if a value for "datetimeDaysToArchive" is set, this value is added to the value of the datetime field and handled as archivedate. (see section "The archive" in the tt_news manual)
- the value of these field is taken for the html-template markers ###NEWS_DATE###, ###NEWS_TIME### and ###NEWS_AGE###. (all parsed through the stdWrap function "strftime")
For new created records the current time is automatically inserted as datetime.',
		'_datetime.seeAlso' => 'tt_news:archivedate',
			// archivedate
		'archivedate.description' => 'The date entered in this field will determine if news are handled as "archived" or not.',
		'archivedate.details' => 'If archivedate shows a value in the past, the news record will be shown in lists showing only archived news. Of course it will disappear from lists showing only non-archived news.',
		'_archivedate.seeAlso' => 'tt_news:datetime',
			// sys_language_uid
		'sys_language_uid.description' => 'The language of the news record.',
		'sys_language_uid.details' => 'This should be the default language unless this record is a translation of another record.',
		'_sys_language_uid.seeAlso' => 'tt_content:sys_language_uid,pages:sys_language_uid,pages_language_overlay:sys_language_uid',
			// l18n_parent
		'l18n_parent.description' => 'The news record in the default language where the current record is a translation from.',
		'l18n_parent.details' => 'You should not change this field manually because it is handled by the TYPO3 localization system.',
			// author
		'author.description' => 'The author of the news record.',
		'author.details' => 'The value of this field will substitute the html-template marker ###NEWS_AUTHOR###. In the default TS setup the author name will be linked to the author\'s email address.',
		'_author.seeAlso' => 'tt_news:email',
			// t3ver_label
		't3ver_label.description' => 'The versioning label of this record.',
		't3ver_label.details' => 'appears only when the extension "versioning" (extkey version) is installed.',
			// author_email
		'author_email.description' => 'The email address of the news author.',
		'author_email.details' => 'In the default TS setup the value of this field will be taken as typolink parameter for the author name (if it is a valid email address). The value of this field will substitute the html-template marker ###NEWS_EMAIL###.',
		'_author_email.seeAlso' => 'tt_news:email',
			// short (subheader)
		'short.description' => 'A short teaser text for the news article.',
		'short.details' => 'The value of this field will substitute the html-template marker ###NEWS_SUBHEADER###. If this field is empty the value of the field "Text" is taken instead.
The conten of subheader is also written to a TypoScript register ("newsSubheader") which can be used to insert the subheader as "&lt;meta&gt; description" to the page header (plugin "metatags" required)',
		'_short.seeAlso' => 'tt_news:bodytext,tt_news:keywords,section "Registers" from tt_news manual | http://typo3.org/documentation/document-library/tt_news/',
			// bodytext
		'bodytext.description' => 'This is the main text for the news article.',
		'bodytext.details' => 'With the type "news" the Rich Text editor (RTE) is used for editing this field (if your browser supports it and a RTE is generally enabled in the system). Be aware that the content is "cleaned" before it goes into the database.
The content of this field will substite the html-template marker ###NEWS_CONTENT###.

In the news "single view" the content of the field "Text" can be divided to multiple pages with a pagebrowser.',
		'_bodytext.seeAlso' => 'section "the Rich-Text-editor" from tt_news manual | http://typo3.org/documentation/document-library/tt_news/,section "The SINGLE view" from tt_news manual | http://typo3.org/documentation/document-library/tt_news/,tt_news:no_auto_pb',
			// keywords
		'keywords.description' => 'Here you can enter some keywords that will be inserted as &lt;meta&gt; keywords in the page header (plugin "metatags" required).',
		'keywords.details' => 'The content of this field is written to a TypoScript register ("newsKeywords") which can be used to insert the keywords as "&lt;meta&gt; keywords" to the page header (plugin "metatags" required).
If you don\'t need this field for "&lt;meta&gt; keywords" you can use it as a second "subheader" field (it will substitute the template marker ###NEWS_KEYWORDS###).',
		'_keywords.seeAlso' => 'tt_news:short,section "Registers" from tt_news manual | http://typo3.org/documentation/document-library/tt_news/',
			// no_auto_pb
		'no_auto_pb.description' => 'Here you can disable "automatic pagebreaks" for this record.',
		'no_auto_pb.details' => 'Pagebreaks can be inserted automatically in the single view (if a value for "maxWordsInSIngleView" is set in TS setup). If this is not wanted for the current news article you can disable it here.',
		'_no_auto_pb.seeAlso' => 'tt_news:bodytext,section "The SINGLE view" from tt_news manual | http://typo3.org/documentation/document-library/tt_news/',

/** TAB: Relations  */

			// category
		'category.description' => 'Here you can assign categories to the current news record (if this record is no translation of another record).',
		'category.details' => 'You can assign categories to news records that are not translations of other records. That allows you to select newsitems for display by their assigned categories or subcategories. A tt_news record can be member of multiple categories.
If the current record is a translation (if it has a "translation original" and a non-default syslanguage) the categories are not editable, they will only be listed in the tt_news form.

Categories can have parent categories. F.e the category "FrontEnd plugins" in the screenshot below does have category "Extensions" selected as "parent category", so "FrontEnd plugins" is a subcategory of "Extensions". That has the result, that the record in this example which has category "FrontEnd plugins" will also appear in a list that shows only category "Extensions".
With "+" icon next to the categoy tree it\'s possible to create categories directly from the news record.

The titles of the assigned categories will substite the html-template marker ###NEWS_CATEGORY###, the category images will be written to the marker ###NEWS_CATEGORY_IMAGE###.

<strong>Note:</strong>
The use of subcategories has to be enabled in the TypoScript setup:
<em>
plugin.tt_news.useSubCategories = 1</em>

<strong>Controlling editing permissions with assigned categories:</strong>
It\'s possible to control the editing permissions of news records with the assigned categories. If this feature is enabled a BE user can only change news records that have categories assigned that are defined in the list of allowed categories for this BE-user. If a BE-user performs any action (move,delete,hide,localize,copy,version,modify) with a record that has non-allowed categories assigned an error message will be displayed and the action will be ignored. Another message will be displayed in the news record above the fields "Title" and "Category". Non-selectable categories will be displayed in grey text and not linked. See second screenshot below.
',
		'_category.image' => 'EXT:tt_news/cshimages/tt_news_categoy.png,EXT:tt_news/cshimages/tt_news_categoy_msg.png',
		'category.image_descr' => 'the field "Category" in the tt_news db-record.
		If a BE-user opens a record that has non-allowed categories assigned this message will be displayed.',
		'_category.seeAlso' => 'tt_news_cat:parent_category,section "Categories" from tt_news manual | http://typo3.org/documentation/document-library/tt_news/',
			// image
		'image.description' => 'Here you can assign images that will be shown in the news record.',
		'image.details' => 'All assigned images will be rendered to the template marker ###NEWS_IMAGE###.
The images will be uploaded/copied to the folder uploads/pics/.
The amount of images that are shown in a certain view (single,list,latest) can be controlled with the TS property "imageCount".',
		#'_image.seeAlso' => '',
			// image caption
		'imagecaption.description' => 'The caption which is shown under the image(s).',
		'imagecaption.details' => 'The field will be split by linebreaks.',
		'_imagecaption.seeAlso' => 'tt_news:image',
			// image altText
		'imagealttext.description' => 'The text entered here will be inserted as "alt" attribure of the image HTML-tag.',
		'imagealttext.details' => 'The field will be split by linebreaks.',
		'_imagealttext.seeAlso' => 'tt_news:imagetitletext,tt_news:image',
			// image titleText
		'imagetitletext.description' => 'The text entered here will be inserted as "title" attribure of the image HTML-tag.',
		'imagetitletext.details' => 'The field will be split by linebreaks.',
		'_imagetitletext.seeAlso' => 'tt_news:imagealttext,tt_news:image',
			// links
		'links.description' => 'The links that are inserted here will be displayed under the "bodytext" in the news single view.',
		'links.details' => 'This field is parsed through the stdWrap function "parseFunc" so it will be possible to enter links as typolink.
F.e.: <em>&lt;LINK http://typo3.org _blank&gt;open typo3.org&lt;/LINK&gt;</em>',
		'_links.seeAlso' => '',
			// related news
		'related.description' => 'Related news articles or pages.',
		'related.details' => 'In this field you can select news records or pages that will be displayed as related news. Related news with type "news" will point to the single view of the related news item. Related news with type "External URL" or "internal Link" will point to the url or page id that is inserted in the newsrecord. Related pages will be handled as news with type link to internal pages.

tt_news can be configured to insert the link which points back from the related record to this record automatically. To enable this "bi-directional relations" set "useBidirectionalRelations" to 1 in  the constant editor or by TS.',
			// files
		'news_files.description' => 'Here you can attach files to a news record.',
		'news_files.details' => 'The atached files will be rendered through the stdWrap function "filelink". See default TS setup for an example.',
		#'_news_files.seeAlso' => '',


	),
	'dk' => Array (
		'.description' => 'Indbygget nyhedssystem',
		'.details' => '\'Nyheds\' elementer er typisk dem som vises på forsiden af din hjemmeside og giver mulighed for at linke til en side med den komplette historie. De kan også repræsentere artikler(link til en komplet side) i systemet.',
	),
	'de' => Array (
		'.description' => 'News System',
		'.details' => 'Normalerweise werden \'News\'-Meldungen auf der Startseite ihrer Website angezeigt und sind mit einem Link auf den Volltext der Meldung versehen. Sie können aber auch Links zu anderen Artikeln (Seiten innerhalb der Website) darstellen.',
	),
	'no' => Array (
		'.description' => 'Innebygget nyhetsystem',
		'.details' => 'Nyheter er typisk saker som vises på forsiden og hvor det er en link til hele saken. Det kan også være artikler (linker til hele sider) i systemet.',
	),
	'it' => Array (
		'.description' => 'News integrate al sito.',
		'.details' => 'Le \'News\' solitamente sono pubblicate nelle homepages dei siti e ti collegano a delle pagine interne per leggere tutto l\'articolo. Possono anche puntare a delle pagine già esistenti all\'interno del sito.',
	),
	'fr' => Array (
	),
	'es' => Array (
		'.description' => 'Sistema de noticias.',
		'.details' => 'Los elementos de "noticias" son aquellos que típicamente van en las páginas de los sitios web y contienen un enlace a una página con la historia completa. Además, pueden representar artículos (enlaces a páginas completas) del sistema.',
	),
	'nl' => Array (
		'.description' => 'Ingebouwd nieuwssysteem',
		'.details' => '\'Nieuws\'items zijn typische deze die op de voorpagina getoond worden en het je mogelijk maken door te klikken naar het volledige verhaal. Ze kunnen ook artikelen representeren (links naar volledige pagina\'s) in het systeem.',
	),
	'cz' => Array (
		'.description' => 'Vestavìný systém zpráv',
		'.details' => '\'Zprávy\' jsou typicky na homepage webu a dovolují prokliknout na stránku s celým textem. Také mohou reprezentovat èlánky (odkazy na úplné strany) v systému.',
	),
	'pl' => Array (
	),
	'si' => Array (
	),
	'fi' => Array (
		'.description' => 'Sisäänrakennettu uutisjärjestelmä.',
		'.details' => 'Uutis tapahtumat ovat tyypillisesti web-sivustosi etusivun materiaalia, joista on linkki uutisjutun kokonaisuuteen. Ne voivat olla myös linkkejä sivustosi johonkin artikkeliin.',
	),
	'tr' => Array (
		'.description' => 'Haberler sistemde yapýlandýrýlýyor',
		'.details' => '"Haberler" nesnesi websitenizin önsayfanýza ve tüm haberler bölümüne link saðlar.Sistemde ayrýca Baþlýklar sunar(linkler tüm sayfaya yönlendirir)',
	),
	'se' => Array (
		'.description' => 'Ett inbyggt nyhetssystem.',
		'.details' => '\'Nyheter\' är ofta små notiser på webbplatsens första sida som sedan länkar vidare till en sida med hela nyheten. De kan också presentera artiklar (sidor) som finns längre in på webbplatsen.',
	),
	'pt' => Array (
	),
	'ru' => Array (
		'.description' => 'Âñòðîåííàÿ ñèñòåìà íîâîñòåé',
		'.details' => '"Íîâîñòè" - ýòî çàïèñè, îáû÷íî ïîìåùàåìûå íà ãëàâíóþ ñòðàíèöó âåá-ñàéòà è èìåþùèå ññûëêè íà ñòðàíèöó ñ ïîëíûì òåêñòîì. Îíè ìîãóò òàêæå ïðåäñòàâëÿòü ñòàòüè (ññûëêè íà öåëûå ñòðàíèöû) â ñèñòåìå.',
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
		'.description' => 'Ugraðeni sustav novosti',
		'.details' => '"Novosti" su oni elementi koji idu na poèetnu stranicu i omoguæavaju da se povezuje na stranice dublje u stablu koje sadrže potpuni sadržaj. Može ih se koristiti i za predstavljanje èlanaka u sustavu.',
	),
	'hu' => Array (
		'.description' => 'Beépített hír modul',
		'.details' => 'A hírelemek általában azok, melyek a címlapon megjelennek és egy link a teljes történetre mutat.',
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
		'.description' => 'Sistema de notícias.',
		'.details' => 'Os elementos de "notícias" são aqueles que tipicamente vão nas páginas iniciais e lhe permitem ligar com uma página que contém a história completa. Também podem representar artigos (ligação a páginas completas) do sistema.',
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