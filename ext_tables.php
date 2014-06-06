<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

$TCA["tt_news"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:tt_news/locallang_tca.php:tt_news",
		"label" => "title",
		"default_sortby" => "ORDER BY datetime DESC",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"prependAtCopy" => "LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy",
		"crdate" => "crdate",
		"type" => "type",
		"enablecolumns" => Array (
			"disabled" => "hidden",
			"starttime" => "starttime",
			"endtime" => "endtime",
			"fe_group" => "fe_group",
		),
		"typeicon_column" => "type",
		"typeicons" => Array (
			"1" => "tt_news_article.gif",
			"2" => "tt_news_exturl.gif",
		),
		"thumbnail" => "image",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."ext_icon.gif",
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php"
	)
);
$TCA["tt_news_cat"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:tt_news/locallang_tca.php:tt_news_cat",
		"label" => "title",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"prependAtCopy" => "LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy",
		"crdate" => "crdate",
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php"
	)
);

t3lib_extMgm::addPlugin(Array("LLL:EXT:tt_news/locallang_tca.php:tt_news", "9"));
t3lib_extMgm::allowTableOnStandardPages("tt_news");
t3lib_extMgm::addToInsertRecords("tt_news");
?>