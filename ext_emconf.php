<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "tt_news".
 *
 * Auto generated 19-10-2013 13:07
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
	'version' => '3.5.0',
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
			'cms' => '',
			'php' => '5.2.0-5.4.99',
			'typo3' => '4.5.0-6.1.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:155:{s:9:"ChangeLog";s:4:"f131";s:20:"class.ext_update.php";s:4:"01ef";s:33:"class.tx_ttnews_compatibility.php";s:4:"a4b4";s:16:"ext_autoload.php";s:4:"4ce3";s:21:"ext_conf_template.txt";s:4:"6b1b";s:12:"ext_icon.gif";s:4:"5e2a";s:17:"ext_localconf.php";s:4:"74d2";s:14:"ext_tables.php";s:4:"d9e4";s:14:"ext_tables.sql";s:4:"19f7";s:15:"flexform_ds.xml";s:4:"44cc";s:23:"flexform_ds_no_sPID.xml";s:4:"48b1";s:13:"locallang.xml";s:4:"c0ea";s:17:"locallang_tca.xml";s:4:"4c8f";s:7:"tca.php";s:4:"6930";s:37:"cm1/class.tx_ttnewscatmanager_cm1.php";s:4:"6a74";s:17:"cm1/locallang.xml";s:4:"ba7a";s:26:"compat/be_axax_for_4.1.php";s:4:"224f";s:24:"compat/flashmessages.css";s:4:"4e2c";s:38:"compat/tceformsCategoryTree_for_4.1.js";s:4:"6087";s:30:"compat/tree_styles_for_4.0.css";s:4:"8d1c";s:20:"compat/gfx/error.png";s:4:"e4dd";s:26:"compat/gfx/information.png";s:4:"3750";s:21:"compat/gfx/notice.png";s:4:"a882";s:17:"compat/gfx/ok.png";s:4:"8bfe";s:22:"compat/gfx/warning.png";s:4:"c847";s:35:"csh/locallang_csh_beusersgroups.xml";s:4:"f235";s:28:"csh/locallang_csh_manual.xml";s:4:"cbde";s:35:"csh/locallang_csh_mod_newsadmin.xml";s:4:"a590";s:28:"csh/locallang_csh_ttnews.xml";s:4:"7c26";s:31:"csh/locallang_csh_ttnewscat.xml";s:4:"877c";s:35:"csh/tt_news_cat_recursive_error.png";s:4:"ceca";s:33:"csh/tt_news_cat_title_lang_ol.png";s:4:"7271";s:23:"csh/tt_news_categoy.png";s:4:"fcb1";s:27:"csh/tt_news_categoy_msg.png";s:4:"0e7a";s:14:"doc/manual.sxw";s:4:"a9f5";s:27:"doc/tt_news_3.0_changes.sxw";s:4:"d93b";s:26:"js/tceformsCategoryTree.js";s:4:"c72f";s:21:"js/tt_news_catmenu.js";s:4:"0332";s:18:"js/tt_news_mod1.js";s:4:"d8e7";s:29:"lib/class.tx_ttnews_cache.php";s:4:"e88a";s:36:"lib/class.tx_ttnews_categorytree.php";s:4:"b13d";s:31:"lib/class.tx_ttnews_catmenu.php";s:4:"5668";s:34:"lib/class.tx_ttnews_cms_layout.php";s:4:"f7a4";s:27:"lib/class.tx_ttnews_div.php";s:4:"4107";s:31:"lib/class.tx_ttnews_helpers.php";s:4:"ac74";s:37:"lib/class.tx_ttnews_itemsProcFunc.php";s:4:"cdef";s:31:"lib/class.tx_ttnews_realurl.php";s:4:"7b04";s:34:"lib/class.tx_ttnews_recordlist.php";s:4:"cc61";s:42:"lib/class.tx_ttnews_TCAform_selectTree.php";s:4:"3703";s:31:"lib/class.tx_ttnews_tcemain.php";s:4:"3f33";s:36:"lib/class.tx_ttnews_templateeval.php";s:4:"f354";s:35:"lib/class.tx_ttnews_tsparserext.php";s:4:"0253";s:33:"lib/class.tx_ttnews_typo3ajax.php";s:4:"f5b0";s:13:"mod1/ajax.php";s:4:"11b9";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"2cf1";s:14:"mod1/index.php";s:4:"f898";s:18:"mod1/locallang.xml";s:4:"24be";s:22:"mod1/locallang_mod.xml";s:4:"452b";s:26:"mod1/mod_ttnews_admin.html";s:4:"1ad7";s:19:"mod1/moduleicon.gif";s:4:"7a48";s:13:"pi/ce_wiz.gif";s:4:"db33";s:22:"pi/class.tx_ttnews.php";s:4:"a9e0";s:30:"pi/class.tx_ttnews_wizicon.php";s:4:"3184";s:15:"pi/fe_index.php";s:4:"616f";s:16:"pi/locallang.xml";s:4:"d4a8";s:23:"pi/static/css/setup.txt";s:4:"ce7b";s:32:"pi/static/rss_feed/constants.txt";s:4:"01d7";s:28:"pi/static/rss_feed/setup.txt";s:4:"44a3";s:30:"pi/static/ts_new/constants.txt";s:4:"dad8";s:26:"pi/static/ts_new/setup.txt";s:4:"bc16";s:30:"pi/static/ts_old/constants.txt";s:4:"e0eb";s:26:"pi/static/ts_old/setup.txt";s:4:"f7e4";s:15:"res/add_cat.gif";s:4:"f7fb";s:18:"res/add_subcat.gif";s:4:"745e";s:13:"res/arrow.gif";s:4:"0ee8";s:17:"res/atom_0_3.tmpl";s:4:"e4f7";s:17:"res/atom_1_0.tmpl";s:4:"7788";s:29:"res/example_amenuUserFunc.php";s:4:"9f57";s:31:"res/example_imageMarkerFunc.php";s:4:"b4a5";s:35:"res/example_itemMarkerArrayFunc.php";s:4:"2b0b";s:35:"res/example_userPageBrowserFunc.php";s:4:"a465";s:11:"res/new.gif";s:4:"7f00";s:27:"res/news_amenuUserFunc2.php";s:4:"f74c";s:18:"res/news_conf1.png";s:4:"7c1e";s:18:"res/news_help.tmpl";s:4:"6d27";s:22:"res/news_template.tmpl";s:4:"c955";s:16:"res/noedit_1.gif";s:4:"2717";s:16:"res/noedit_2.gif";s:4:"3f51";s:12:"res/rdf.tmpl";s:4:"4546";s:29:"res/realUrl_example_setup.txt";s:4:"b043";s:17:"res/rss_0_91.tmpl";s:4:"2864";s:14:"res/rss_2.tmpl";s:4:"ff8a";s:28:"res/tt_news_languageMenu.php";s:4:"fdfb";s:27:"res/tt_news_medialinks.html";s:4:"3707";s:22:"res/tt_news_styles.css";s:4:"4f61";s:25:"res/tt_news_v2_styles.css";s:4:"e894";s:28:"res/tt_news_v2_template.html";s:4:"a6e3";s:25:"res/tt_news_v3_styles.css";s:4:"9374";s:28:"res/tt_news_v3_template.html";s:4:"82ea";s:25:"res/gfx/control_first.gif";s:4:"ec47";s:34:"res/gfx/control_first_disabled.gif";s:4:"d0ee";s:24:"res/gfx/control_last.gif";s:4:"5da1";s:33:"res/gfx/control_last_disabled.gif";s:4:"1e27";s:24:"res/gfx/control_next.gif";s:4:"c1b0";s:33:"res/gfx/control_next_disabled.gif";s:4:"2f56";s:28:"res/gfx/control_previous.gif";s:4:"dcd8";s:37:"res/gfx/control_previous_disabled.gif";s:4:"7d6c";s:20:"res/gfx/ext_icon.gif";s:4:"5e2a";s:23:"res/gfx/ext_icon__f.gif";s:4:"8b98";s:23:"res/gfx/ext_icon__h.gif";s:4:"e1bc";s:24:"res/gfx/ext_icon__ht.gif";s:4:"dbd6";s:25:"res/gfx/ext_icon__htu.gif";s:4:"7975";s:24:"res/gfx/ext_icon__hu.gif";s:4:"865e";s:23:"res/gfx/ext_icon__t.gif";s:4:"ef88";s:24:"res/gfx/ext_icon__tu.gif";s:4:"22a2";s:23:"res/gfx/ext_icon__u.gif";s:4:"0fd4";s:23:"res/gfx/ext_icon__x.gif";s:4:"ede5";s:34:"res/gfx/ext_icon_ttnews_folder.gif";s:4:"7a48";s:27:"res/gfx/tt_news_article.gif";s:4:"91b6";s:30:"res/gfx/tt_news_article__h.gif";s:4:"d29b";s:31:"res/gfx/tt_news_article__ht.gif";s:4:"d092";s:32:"res/gfx/tt_news_article__htu.gif";s:4:"412b";s:31:"res/gfx/tt_news_article__hu.gif";s:4:"a2c8";s:30:"res/gfx/tt_news_article__t.gif";s:4:"3df2";s:31:"res/gfx/tt_news_article__tu.gif";s:4:"9690";s:30:"res/gfx/tt_news_article__u.gif";s:4:"4ffc";s:30:"res/gfx/tt_news_article__x.gif";s:4:"2e15";s:23:"res/gfx/tt_news_cat.gif";s:4:"2efd";s:26:"res/gfx/tt_news_cat__d.gif";s:4:"0bdf";s:30:"res/gfx/tt_news_cat__f.gif.gif";s:4:"1dc9";s:27:"res/gfx/tt_news_cat__fu.gif";s:4:"9dfa";s:26:"res/gfx/tt_news_cat__h.gif";s:4:"d98b";s:27:"res/gfx/tt_news_cat__hf.gif";s:4:"d98b";s:28:"res/gfx/tt_news_cat__hfu.gif";s:4:"d422";s:27:"res/gfx/tt_news_cat__ht.gif";s:4:"e4ea";s:28:"res/gfx/tt_news_cat__htf.gif";s:4:"e4ea";s:29:"res/gfx/tt_news_cat__htfu.gif";s:4:"f324";s:28:"res/gfx/tt_news_cat__htu.gif";s:4:"f324";s:31:"res/gfx/tt_news_cat__hu.gif.gif";s:4:"d422";s:26:"res/gfx/tt_news_cat__t.gif";s:4:"f2c9";s:27:"res/gfx/tt_news_cat__tf.gif";s:4:"f2c9";s:28:"res/gfx/tt_news_cat__tfu.gif";s:4:"dd60";s:27:"res/gfx/tt_news_cat__tu.gif";s:4:"dd60";s:26:"res/gfx/tt_news_cat__u.gif";s:4:"1b40";s:26:"res/gfx/tt_news_cat__x.gif";s:4:"a08d";s:26:"res/gfx/tt_news_exturl.gif";s:4:"57f6";s:29:"res/gfx/tt_news_exturl__h.gif";s:4:"7465";s:30:"res/gfx/tt_news_exturl__ht.gif";s:4:"9199";s:31:"res/gfx/tt_news_exturl__htu.gif";s:4:"7019";s:30:"res/gfx/tt_news_exturl__hu.gif";s:4:"467e";s:29:"res/gfx/tt_news_exturl__t.gif";s:4:"0fd0";s:30:"res/gfx/tt_news_exturl__tu.gif";s:4:"2659";s:29:"res/gfx/tt_news_exturl__u.gif";s:4:"260e";s:29:"res/gfx/tt_news_exturl__x.gif";s:4:"4ebe";}',
);

?>