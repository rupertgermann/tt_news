<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is 
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
* 
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license 
*  from the author is found in LICENSE.txt distributed with these scripts.
*
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * newsLib.inc
 * 
 * Creates a news system.
 * 
 * TypoScript config:
 * - See static_template "plugin.tt_news"
 * - See TS_ref.pdf
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */

require_once(PATH_t3lib."class.t3lib_xml.php");
require_once(PATH_tslib."class.tslib_pibase.php");

class tx_ttnews extends tslib_pibase {
	var $cObj;		// The backReference to the mother cObj object set at call time

		// Default plugin variables:
	var $prefixId = 'tx_ttnews';		// Same as class name
	var $scriptRelPath = 'pi/class.tx_ttnews.php';	// Path to this script relative to the extension dir.
	var $extKey = 'tt_news';	// The extension key.

	var $tt_news_uid;
	var $conf;
	var $conf2;
	var $config;
	var $alternativeLayouts;
	var $pid;
	var $allowCaching;
	var $catExclusive;
	var $searchFieldList="short,bodytext,author,keywords,links,imagecaption,title";
	var $theCode="";
	
	var $categories=array();			// Is initialized with the categories of the shopping system
	var $pageArray=array();				// Is initialized with an array of the pages in the pid-list

	/**
	 * Main news function for XML news feed
	 */
	function main_xmlnewsfeed($content,$conf)	{
		$className=t3lib_div::makeInstanceClassName("t3lib_xml");
		$xmlObj = new $className("typo3_xmlnewsfeed");
		$xmlObj->setRecFields("tt_news","title,datetime");	// More fields here...
		$xmlObj->renderHeader();
		$xmlObj->renderRecords("tt_news",$this->getStoriesResult());
		$xmlObj->renderFooter();
		return $xmlObj->getResult();
	}
	function getStoriesResult() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_news', 'pid='.intval($GLOBALS['TSFE']->id).$this->cObj->enableFields('tt_news'), '', 'datetime DESC');
		return $res;
	}
	



	/**
	 * Main news function.
	 */
	function main_news($content,$conf)	{


		$GLOBALS["TSFE"]->set_no_cache();
	
		// *************************************
		// *** getting configuration values:
		// *************************************
		$this->conf = $conf;
		$this->tt_news_uid = intval(t3lib_div::_GP("tt_news"));
		$this->alternativeLayouts = intval($this->conf["alternatingLayouts"])>0 ? intval($this->conf["alternatingLayouts"]) : 2;
			
			// Loading language-labels
		$this->pi_loadLL();
			
			// Init FlexForm configuration for plugin:
		$this->pi_initPIflexForm();
			
			// pid_list is the pid/list of pids from where to fetch the news items.
		$this->config["pid_list"] = trim($this->cObj->stdWrap($this->conf["pid_list"],$this->conf["pid_list."]));
		$this->config["pid_list"] = $this->config["pid_list"] ? implode(t3lib_div::intExplode(",",$this->config["pid_list"]),",") : $GLOBALS["TSFE"]->id;
		$recursive=$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'recursive','sDEF');
		$this->config["recursive"] = is_numeric($recursive)?$recursive:$this->cObj->stdWrap($conf["recursive"],$conf["recursive."]);
		list($pid) = explode(",",$this->config["pid_list"]);
		$this->pid = $pid;

			// "CODE" decides what is rendered:
		$this->config["code"] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],'what_to_display','sDEF');
		$this->config["select_deselect_categories"] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],'select_deselect_categories','sDEF');
		$this->config["category_selection"] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],'category_selection','sDEF');
		$this->config["archive"] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],'archive','sDEF');

		$this->config["limit"] = t3lib_div::intInRange($conf["limit"],0,1000);
		$this->config["limit"] = $this->config["limit"] ? $this->config["limit"] : 50;
		$this->allowCaching = $this->conf["allowCaching"]?1:0;

		$catImageMode=$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'catImageMode','s_category');
		$catTextMode=$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'catTextMode','s_category');
		$catImageMaxWidth=$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'catImageMaxWidth','s_category');
		$catImageMaxHeight=$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'catImageMaxHeight','s_category');
		$maxCatImages=$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'maxCatImages','s_category');
		$catTextLength=$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'catTextLength','s_category');
		$maxCatTexts=$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'maxCatTexts','s_category');
		$this->config['catImageMode']=(is_numeric($catImageMode)?$catImageMode:$this->conf['catImageMode']);
		$this->config['catTextMode']=(is_numeric($catTextMode)?$catTextMode:$this->conf['catTextMode']);
		$this->config['catImageMaxWidth']=($catImageMaxWidth?$catImageMaxWidth:$this->conf['catImageMaxWidth']);
		$this->config['catImageMaxHeight']=($catImageMaxHeight?$catImageMaxHeight:$this->conf['catImageMaxHeight']);
		$this->config['maxCatImages']=(is_numeric($maxCatImages)?$maxCatImages:$this->conf['maxCatImages']);
		$this->config['catTextLength']=(is_numeric($catTextLength)?$catTextLength:$this->conf['catTextLength']);
		$this->config['maxCatTexts']=(is_numeric($maxCatTexts)?$maxCatTexts:$this->conf['maxCatTexts']);

			// If the current record should be displayed.
		$this->config["displayCurrentRecord"] = $this->conf["displayCurrentRecord"];
		if ($this->config["displayCurrentRecord"])	{
			$this->config["code"]="LIST";
			$this->tt_news_uid=$this->cObj->data["uid"];
		}

			// template is read.//
		$templateflex_file=$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'template_file','s_template');
		$this->templateCode = $this->cObj->fileResource($templateflex_file?"uploads/tx_ttnews/".$templateflex_file:$this->conf["templateFile"]);
			// globally substituted markers, fonts and colors.	
		$splitMark = md5(microtime());
		$globalMarkerArray=array();
		list($globalMarkerArray["###GW1B###"],$globalMarkerArray["###GW1E###"]) = explode($splitMark,$this->cObj->stdWrap($splitMark,$conf["wrap1."]));
		list($globalMarkerArray["###GW2B###"],$globalMarkerArray["###GW2E###"]) = explode($splitMark,$this->cObj->stdWrap($splitMark,$conf["wrap2."]));
		$globalMarkerArray["###GC1###"] = $this->cObj->stdWrap($conf["color1"],$conf["color1."]);
		$globalMarkerArray["###GC2###"] = $this->cObj->stdWrap($conf["color2"],$conf["color2."]);
		$globalMarkerArray["###GC3###"] = $this->cObj->stdWrap($conf["color3"],$conf["color3."]);

			// Substitute Global Marker Array
		$this->templateCode= $this->cObj->substituteMarkerArray($this->templateCode, $globalMarkerArray);

	
		// *************************************
		// *** doing the things...:
		// *************************************
		$this->enableFields = $this->cObj->enableFields("tt_news");
		$this->dontParseContent = $this->conf["dontParseContent"];
		$this->local_cObj =t3lib_div::makeInstance("tslib_cObj");		// Local cObj.

		$codes=t3lib_div::trimExplode(",", $this->config["code"]?$this->config["code"]:$this->conf["defaultCode"],1);
		if (!count($codes))	$codes=array("");
		while(list(,$theCode)=each($codes))	{
#			list($theCode,$cat,$aFlag) = explode("/",$theCode);
			$this->catExclusive = $this->config['category_selection'];
			$this->arcExclusive	= $this->config['archive'];
			$theCode = (string)strtoupper(trim($theCode));
			$this->theCode = $theCode;
			switch($theCode)	{
				case "LATEST":
				case "LIST":
				case "SINGLE":
				case "SEARCH":
					$content.= $this->news_list();
				break;
				case "AMENU":
					$content.= $this->news_archiveMenu();
				break;
				default:
					$langKey = strtoupper($GLOBALS["TSFE"]->config["config"]["language"]);
					$helpTemplate = $this->cObj->fileResource("EXT:tt_news/pi/news_help.tmpl");

						// Get language version
					$helpTemplate_lang="";
					if ($langKey)	{$helpTemplate_lang = $this->cObj->getSubpart($helpTemplate,"###TEMPLATE_".$langKey."###");}
					$helpTemplate = $helpTemplate_lang ? $helpTemplate_lang : $this->cObj->getSubpart($helpTemplate,"###TEMPLATE_DEFAULT###");

						// Markers and substitution:
					$markerArray["###CODE###"] = $this->theCode;
					$markerArray["###EXTPATH###"] = $GLOBALS['TYPO3_LOADED_EXT']['tt_news']['siteRelPath'];
					$content.=$this->cObj->substituteMarkerArray($helpTemplate,$markerArray);
				break;
			}
		}
		return $content;
	}

	/**
	 * News archive menu
	 */
	function news_archiveMenu()	{
		$this->arcExclusive=1;
		$selectConf = $this->getSelectConf('',1);

			// Finding maximum and minimum values:
		$selectConf['selectFields'] = 'max(datetime) as maxval, min(datetime) as minval';
		$res = $this->cObj->exec_getQuery("tt_news",$selectConf);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if ($row["minval"])	{
			$dateArr = array();
			$arcMode = $this->conf["archiveMode"] ? $this->conf["archiveMode"] : "month";
			$c=0;
			do {
				switch($arcMode)	{
					case "month":
						$theDate = mktime (0,0,0,date("m",$row["minval"])+$c,1,date("Y",$row["minval"]));
					break;
					case "quarter":
						$theDate = mktime (0,0,0,floor(date("m",$row["minval"])/3)+1+(3*$c),1,date("Y",$row["minval"]));
					break;
					case "year":
						$theDate = mktime (0,0,0,1,1,date("Y",$row["minval"])+$c);
					break;
				}
				$dateArr[]=$theDate;
				$c++;
				if ($c>1000)	break;
			} while ($theDate<$GLOBALS["SIM_EXEC_TIME"]);
	//		array_pop($dateArr);

			reset($dateArr);
			$periodAccum=array();
			$selectConf2['where'] = $selectConf['where'];
			while(list($k,$v)=each($dateArr))	{
				if (!isset($dateArr[$k+1]))	{break;}
				$periodInfo=array();
				$periodInfo["start"] = $dateArr[$k];
				$periodInfo["stop"] = $dateArr[$k+1]-1;
				$periodInfo["HRstart"] = date("d-m-Y",$periodInfo["start"]);
				$periodInfo["HRstop"] = date("d-m-Y",$periodInfo["stop"]);
				$periodInfo["quarter"] = floor(date("m",$dateArr[$k])/3)+1;
	
					// FInding maximum and minimum values:
				$selectConf['selectFields'] = 'count(distinct(uid))';
				$selectConf['where'] = $selectConf2['where']." AND datetime>=".$periodInfo["start"]." AND datetime<".$periodInfo["stop"];
				$res = $this->cObj->exec_getQuery("tt_news",$selectConf);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$periodInfo["count"]=$row[0];
				
				if (!$this->conf["archiveMenuNoEmpty"] || $periodInfo["count"])	{
					$periodAccum[] = $periodInfo;
				}
			}
			
			
			
	
			$t["total"] = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###TEMPLATE_ARCHIVE###"));
			$t["item"]= $this->getLayouts($t["total"],$this->alternativeLayouts,"MENUITEM");
			$cc=0;
	
			$veryLocal_cObj =t3lib_div::makeInstance("tslib_cObj");		// Local cObj.
			reset($periodAccum);
			$itemsOut="";
			while(list(,$pArr)=each($periodAccum))		{
					// Print Item Title
				$wrappedSubpartArray=array();
				$wrappedSubpartArray["###LINK_ITEM###"]= array('<a href="'.$this->getLinkUrl(0).'&amp;pS='.$pArr["start"].'&amp;pL='.($pArr["stop"]-$pArr["start"]).'&amp;arc=1">','</a>'); 
	
				$markerArray = array();
				$veryLocal_cObj->start($pArr,"");
				$markerArray["###ARCHIVE_TITLE###"]=$veryLocal_cObj->cObjGetSingle($this->conf["archiveTitleCObject"],$this->conf["archiveTitleCObject."],"archiveTitle");
				$markerArray["###ARCHIVE_COUNT###"]=$pArr["count"];
	
				$itemsOut.= $this->cObj->substituteMarkerArrayCached($t["item"][($cc%count($t["item"]))],$markerArray,array(),$wrappedSubpartArray);
				$cc++;
			}
			
						// Reset:
			$subpartArray=array();
			$wrappedSubpartArray=array();
			$markerArray=array();
				// Set content
			$subpartArray["###CONTENT###"]=$itemsOut;
			$content = $this->cObj->substituteMarkerArrayCached($t["total"],$markerArray,$subpartArray,$wrappedSubpartArray);
		} else {
			$content = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###TEMPLATE_ARCHIVE_NOITEMS###"));
		}
		return $content;
	}
	
	/**
	 * Displaying single news/ the news list / searching
	 */
	function news_list()	{
		$theCode = $this->theCode;
/*		$this->setPidlist($this->config["pid_list"]);				// The list of pid's we're operation on. All tt_products records must be in the pidlist in order to be selected.
		$this->initRecursive($this->config["recursive"]);
		$this->initCategories();
		$this->generatePageArray();
		
		debug($this->pageArray);
		debug($this->categories);

*/

		$this->initCategories();
		switch($theCode)	{
			case "LATEST": 	
				$prefix_display="displayLatest"; 
				$templateName = "TEMPLATE_LATEST";
				$this->arcExclusive=-1;	// Only latest, non archive news
				if (intval($this->conf["latestLimit"]))	$this->config["limit"] = intval($this->conf["latestLimit"]);
			break;
			case "LIST": 
			case "SEARCH": 	
				$prefix_display="displayList"; 
				$templateName = "TEMPLATE_LIST";
			break;
			default:		
				$prefix_display="displaySingle"; 
				$templateName = "TEMPLATE_SINGLE";
			break;
		}

		if ($this->tt_news_uid)	{
				// performing query:
		 	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_news', 'uid='.intval($this->tt_news_uid).' AND type=0'.$this->enableFields);	// type=0 -> only real news.
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

			if($this->config["displayCurrentRecord"] || is_array($row))		{
				$this->setPidlist(intval($row["pid"]));
				$this->generatePageArray();

					// Get the subpart code
				$item ="";
				if ($this->config["displayCurrentRecord"])	{
					$row=$this->cObj->data;
					$item = trim($this->cObj->getSubpart($this->templateCode,$this->spMarker("###TEMPLATE_SINGLE_RECORDINSERT###")));
				}
				if (!$item)	{$item = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###TEMPLATE_SINGLE###"));}

					// Fill marker arrays
				$wrappedSubpartArray=array();
				if($backPid=$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'backPid','sDEF')){
				}elseif($backPid=$this->conf["backPid"]){
				}elseif($backPid=t3lib_div::_GP("backPID")){
				}
				$wrappedSubpartArray["###LINK_ITEM###"]= array('<a href="'.$this->getLinkUrl($backPid).'">','</a>');
				$markerArray = $this->getItemMarkerArray($row,"displaySingle");
					// Substitute
				$content= $this->cObj->substituteMarkerArrayCached($item,$markerArray,array(),$wrappedSubpartArray);
			}
		} elseif ($theCode=="SINGLE") {		
			$content.="Wrong parameters, GET/POST var 'tt_news' was missing.";
		} elseif ($this->arcExclusive>0 && !t3lib_div::_GP("pS") && $theCode!="SEARCH") {			// periodStart must be set when listing from the archive.
			$content.="";
		} else {		
			$content="";
	// List news:
			$where="";			
			if ($theCode=="SEARCH")	{
					// Get search subpart
				$t["search"] = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###TEMPLATE_SEARCH###"));
					// Substitute a few markers
				$out=$t["search"];				
				$out=$this->cObj->substituteMarker($out, "###FORM_URL###", $this->getLinkUrl($this->conf["PIDsearch"]));
				$out=$this->cObj->substituteMarker($out, "###SWORDS###", htmlspecialchars(t3lib_div::_GP("swords")));
					// Add to content
				$content.=$out;
				if (t3lib_div::_GP("swords"))	{
					$where = $this->searchWhere(trim(t3lib_div::_GP("swords")));
				}
			}
			$begin_at=t3lib_div::intInRange(t3lib_div::_GP("begin_at"),0,100000);
			if (($theCode!="SEARCH" && !t3lib_div::_GP("swords")) || $where)	{

				$selectConf = $this->getSelectConf($where);

					// performing query to count all news (we need to know it for browsing):
				$selectConf['selectFields'] = 'count(distinct(uid))'; //count(*)
				$res = $this->cObj->exec_getQuery("tt_news",$selectConf);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$newsCount = $row[0];

					// range check to current newsCount
				$begin_at = t3lib_div::intInRange(($begin_at >= $newsCount) ? ($newsCount-$this->config["limit"]) : $begin_at,0); 

					// performing query for display:
				$selectConf["groupBy"] = "uid";
				$selectConf["orderBy"] = "datetime DESC"; 
				$selectConf['selectFields'] = '*';
				$selectConf['max'] = intval($this->config["limit"]+1);
				$selectConf['begin'] = $begin_at;
			 	$res = $this->cObj->exec_getQuery("tt_news",$selectConf);

					// Getting elements
				$itemsOut = "";
				$t = array();
				$t["total"] = $this->cObj->getSubpart($this->templateCode,$this->spMarker("###".$templateName."###"));
				$t["item"] = $this->getLayouts($t["total"],$this->alternativeLayouts,"NEWS");
				$cc = 0;

				$itemLinkTarget = $this->conf["itemLinkTarget"] ? 'target="'.$this->conf["itemLinkTarget"].'"' : "";
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
						// Print Item Title
					$wrappedSubpartArray=array();
					if ($row["type"]==1 || $row["type"]==2)	{
						$this->local_cObj->setCurrentVal($row["type"]==1 ? $row["page"] : $row["ext_url"]);
						$wrappedSubpartArray["###LINK_ITEM###"]= $this->local_cObj->typolinkWrap($this->conf["pageTypoLink."]);
					} else {
					$PIDitemDisplay=$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'PIDitemDisplay','sDEF');
						$wrappedSubpartArray["###LINK_ITEM###"]= array('<a href="'.$this->getLinkUrl($PIDitemDisplay?$PIDitemDisplay:$this->conf["PIDitemDisplay"]).'&amp;tt_news='.$row["uid"].'" '.$itemLinkTarget.'>','</a>'); 
					}
					$markerArray = $this->getItemMarkerArray($row,$prefix_display);

					$itemsOut.= $this->cObj->substituteMarkerArrayCached($t["item"][($cc%count($t["item"]))],$markerArray,array(),$wrappedSubpartArray);
					$cc++;
					if ($cc==$this->config["limit"])	{break;}
				}
				$out=$itemsOut;
			}
			if ($out)	{
				// next / prev:
				$url = $this->getLinkUrl("","begin_at");
					// Reset:
				$subpartArray=array();
				$wrappedSubpartArray=array();
				$markerArray=array();
					
				if ($newsCount > $begin_at+$this->config["limit"])	{
					$next = ($begin_at+$this->config["limit"] > $newsCount) ? $newsCount-$this->config["limit"] : $begin_at+$this->config["limit"];
					$wrappedSubpartArray["###LINK_NEXT###"]=array('<a href="'.$url.'&amp;begin_at='.$next.'">','</a>');
				} else {
					$subpartArray["###LINK_NEXT###"]="";
				}
				if ($begin_at)	{
					$prev = ($begin_at-$this->config["limit"] < 0) ? 0 : $begin_at-$this->config["limit"];
					$wrappedSubpartArray["###LINK_PREV###"]=array('<a href="'.$url.'&amp;begin_at='.$prev.'">','</a>');
				} else {
					$subpartArray["###LINK_PREV###"]="";
				}
				$markerArray["###BROWSE_LINKS###"]="";
				if ($newsCount > $this->config["limit"] )	{ // there is more than one page, so let's browse
					for ($i = 0 ; $i < ($newsCount/$this->config["limit"]); $i++) 	{
						if (($begin_at >= $i*$this->config["limit"]) && ($begin_at < $i*$this->config["limit"]+$this->config["limit"])) 	{
							$markerArray["###BROWSE_LINKS###"].= ' <b>'.(string)($i+1).'</b> ';
							//	you may use this if you want to link to the current page also
							//	$markerArray["###BROWSE_LINKS###"].= ' <a href="'.$url.'&amp;begin_at='.(string)($i * $this->config["limit"]).'"><b>'.(string)($i+1).'</b></a> ';
						} else {
							$markerArray["###BROWSE_LINKS###"].= ' <a href="'.$url.'&amp;begin_at='.(string)($i * $this->config["limit"]).'">'.(string)($i+1).'</a> ';
						}
					}
				}

				$subpartArray["###CONTENT###"]=$out;
				$markerArray["###CATEGORY_TITLE###"]="";	// Something here later...
				$wrappedSubpartArray["###LINK_ARCHIVE###"]=$this->local_cObj->typolinkWrap($this->conf["archiveTypoLink."]);

				$content.= $this->cObj->substituteMarkerArrayCached($t["total"],$markerArray,$subpartArray,$wrappedSubpartArray);
			} elseif ($where)	{
				$content.=$this->cObj->getSubpart($this->templateCode,$this->spMarker("###ITEM_SEARCH_EMPTY###"));
			}
		}
		return $content;
	}



	/**
	 * Returns a url for use in forms and links
	 */
	function getLinkUrl($id="",$excludeList="")	{
		$queryString=array();
		$queryString["id"] = "id=".($id ? $id : $GLOBALS["TSFE"]->id);
		// Andreas Schwarzkopf		$queryString["type"]= $GLOBALS["TSFE"]->type ? 'type='.$GLOBALS["TSFE"]->type : "";
		// der TypoScript-Setup-Wert itemLinkType wird ausgelesen, wenn nicht vorhanden, der aktuelle Type-Wert des Fensters
		if ($this->conf["itemLinkType"]) {
		         $itemLinkType = "type=".$this->conf["itemLinkType"];
		} else {
			if ($GLOBALS["TSFE"]->type) {
				$itemLinkType = 'type='.$GLOBALS["TSFE"]->type;
			} else {
				$itemLinkType = '';
			}
		}
		$queryString["type"]= $itemLinkType;
		$queryString["backPID"]= 'backPID='.$GLOBALS["TSFE"]->id;
		$queryString["begin_at"]= t3lib_div::GPvar("begin_at") ? 'begin_at='.t3lib_div::GPvar("begin_at") : "";
		$queryString["swords"]= t3lib_div::GPvar("swords") ? "swords=".rawurlencode(t3lib_div::GPvar("swords")) : "";
		$queryString["pS"]= t3lib_div::GPvar("pS") ? "pS=".intval(t3lib_div::GPvar("pS")) : "";	// period start
		$queryString["pL"]= t3lib_div::GPvar("pL") ? "pL=".intval(t3lib_div::GPvar("pL")) : ""; // Period length
		$queryString["arc"]= t3lib_div::GPvar("arc") ? "arc=".intval(t3lib_div::GPvar("arc")) : ""; // Archive flag: 0 = don't care, -1 = latest, 1 = archive
		$queryString["cat"]= t3lib_div::GPvar("cat") ? "cat=".intval(t3lib_div::GPvar("cat")) : ""; // Category uid, 0 = any

		reset($queryString);
		while(list($key,$val)=each($queryString))	{
			if (!$val || ($excludeList && t3lib_div::inList($excludeList,$key)))	{
				unset($queryString[$key]);
			}
		}
		return $GLOBALS["TSFE"]->absRefPrefix.'index.php?'.implode($queryString,"&amp;");
	}
	
	


	function getSelectConf($where,$noPeriod=0)	{
		$this->setPidlist($this->config["pid_list"]);
		$this->initRecursive($this->config["recursive"]);
		$this->generatePageArray();

			// Get news
		$selectConf = Array();
		$selectConf["pidInList"] = $this->pid_list;
		$selectConf["where"] = "1=1 ".$where;


			// Archive
		if (intval(t3lib_div::_GP("arc")))	{
			$this->arcExclusive = intval(t3lib_div::_GP("arc"));
		}
		if ($this->arcExclusive)	{
			if ($this->conf["enableArchiveDate"])	{
				if ($this->arcExclusive<0)	{	// latest
					$selectConf["where"].=' AND (tt_news.archivedate=0 OR tt_news.archivedate>'.$GLOBALS["SIM_EXEC_TIME"].')';
				} elseif ($this->arcExclusive>0)	{
					$selectConf["where"].=' AND tt_news.archivedate<'.$GLOBALS["SIM_EXEC_TIME"];
				}
			}
			if ($this->conf["datetimeDaysToArchive"])	{
				$theTime = $GLOBALS["SIM_EXEC_TIME"]-intval($this->conf["datetimeDaysToArchive"])*3600*24;
				if ($this->arcExclusive<0)	{	// latest
					$selectConf["where"].=' AND (tt_news.datetime=0 OR tt_news.datetime>'.$theTime.')';
				} elseif ($this->arcExclusive>0)	{
					$selectConf["where"].=' AND tt_news.datetime<'.$theTime;
				}
			}
		}
			// Category
/*		$codes=t3lib_div::trimExplode(",", $this->config["code"]?$this->config["code"]:$this->conf["defaultCode"],1);
		if (count($codes)) {
			while(list(,$theCode)=each($codes))	{
				list($theCode,$cat,$aFlag) = explode("/",$theCode);
			}
		}*/
		if (intval(t3lib_div::_GP("cat")))	{
			$this->catExclusive = intval(t3lib_div::_GP("cat"));
			$this->config['category_selection'] = intval(t3lib_div::_GP("cat"));
		}
		if ($this->config['category_selection']!='')	{
			$selectConf["leftjoin"] = "tt_news_cat_mm ON tt_news.uid = tt_news_cat_mm.uid_local";
			if($this->config['select_deselect_categories']!=1)$selectConf["where"] .= " AND (IFNULL(tt_news_cat_mm.uid_foreign,0) ".($this->config['select_deselect_categories']==3?"NOT ":"")."IN (".$this->config['category_selection']."))";
#			$selectConf["groupBy"] .= "uid";
		}
			// Period
		if (!$noPeriod && intval(t3lib_div::_GP("pS")))	{
			$selectConf["where"].=' AND tt_news.datetime>'.intval(t3lib_div::_GP("pS"));
			if (intval(t3lib_div::_GP("pL")))	{
				$selectConf["where"].=' AND tt_news.datetime<'.(intval(t3lib_div::_GP("pS"))+intval(t3lib_div::_GP("pL")));
			}
		}
		return $selectConf;
	}



	/**
	 * Sets the pid_list internal var
	 */
	function setPidlist($pid_list)	{
		$this->pid_list = $pid_list;
	}

	/**
	 * Extends the internal pid_list by the levels given by $recursive
	 */
	function initRecursive($recursive)	{
		if ($recursive)	{		// get pid-list if recursivity is enabled
			$pid_list_arr = explode(",",$this->pid_list);
			$this->pid_list="";
			while(list(,$val)=each($pid_list_arr))	{
				$this->pid_list.=$val.",".$this->cObj->getTreeList($val,intval($recursive));
			}
			$this->pid_list = ereg_replace(",$","",$this->pid_list);
		}
	}

	/**
	 * Getting all tt_news_cat categories into internal array
	 */
	function initCategories()	{
			// Fetching catagories:
	 	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_news_cat LEFT JOIN tt_news_cat_mm ON tt_news_cat_mm.uid_foreign = tt_news_cat.uid', '1=1'.$this->cObj->enableFields('tt_news_cat'));
		echo mysql_error();
		$this->categories=array();
		$this->categorieImages=array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			if(isset($row["uid_local"])) {
				$this->categories[$row["uid_local"]][] = array(
				"title"=>$row["title"],
				"image"=>$row["image"],
				"shortcut"=>$row["shortcut"],
				"catid"=>$row["uid_foreign"]
				);
			} else {
				$this->categories["0"][$row["uid"]] = $row["title"];
			}
		}
	}

	/**
	 * Generates an array, ->pageArray of the pagerecords from ->pid_list
	 */
	function generatePageArray()	{
			// Get pages (for category titles)		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title,uid,author,author_email', 'pages', 'uid IN ('.$this->pid_list.')');
		$this->pageArray = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))		{
			$this->pageArray[$row["uid"]] = $row;
		}
	}

	/**
	 * Fills in the markerArray with data for a product
	 */
	function getItemMarkerArray ($row,$textRenderObj="displaySingle")	{
			// Returns a markerArray ready for substitution with information for the tt_producst record, $row
			// config to use:
		$lConf = $this->conf[$textRenderObj."."];
		$this->local_cObj->start($row,"tt_news");
		$imageNum = isset($lConf["imageCount"]) ? $lConf["imageCount"] : 1;
		$imageNum = t3lib_div::intInRange($imageNum,0,100);
		$markerArray=array();


			// Get and set image:
		$theImgCode="";
		$imgs = t3lib_div::trimExplode(",",$row["image"],1);
		$imgsCaptions = explode(chr(10),$row["imagecaption"]);
		reset($imgs);
		$cc=0;
		while(list(,$val)=each($imgs))	{
			if ($cc==$imageNum)	break;
			if ($val)	{
				$lConf["image."]["file"] = "uploads/pics/".$val;
			}
			$theImgCode.=$this->local_cObj->IMAGE($lConf["image."]).$this->local_cObj->stdWrap($imgsCaptions[$cc],$lConf["caption_stdWrap."]);
			$cc++;
		}
		$markerArray["###NEWS_IMAGE###"]="";
		if ($cc)	{
			$markerArray["###NEWS_IMAGE###"] = $this->local_cObj->wrap(trim($theImgCode),$lConf["imageWrapIfAny"]); 
		}

			// Title
		$markerArray["###NEWS_UID###"] = $row["uid"];
		$markerArray["###NEWS_TITLE###"] = $this->local_cObj->stdWrap($row["title"],$lConf["title_stdWrap."]);
		$markerArray["###NEWS_AUTHOR###"] = $this->local_cObj->stdWrap($row["author"],$lConf["author_stdWrap."]);
		$markerArray["###NEWS_EMAIL###"] = $this->local_cObj->stdWrap($row["author_email"],$lConf["email_stdWrap."]);
		$markerArray["###NEWS_DATE###"] = $this->local_cObj->stdWrap($row["datetime"],$lConf["date_stdWrap."]);
		$markerArray["###NEWS_TIME###"] = $this->local_cObj->stdWrap($row["datetime"],$lConf["time_stdWrap."]);
		$markerArray["###NEWS_AGE###"] = $this->local_cObj->stdWrap($row["datetime"],$lConf["age_stdWrap."]);
		$markerArray["###NEWS_SUBHEADER###"] = $this->formatStr($this->local_cObj->stdWrap($row["short"],$lConf["subheader_stdWrap."]));
		$markerArray["###NEWS_CONTENT###"] = $this->formatStr($this->local_cObj->stdWrap($row["bodytext"],$lConf["content_stdWrap."]));
		$markerArray["###NEWS_LINKS###"] = $this->formatStr($this->local_cObj->stdWrap($row["links"],$lConf["links_stdWrap."]));
#?		$markerArray["###NEWS_LINKS###"] = $this->local_cObj->stdWrap($this->formatStr($row["links"]),$lConf["links_stdWrap."]);
			// Category fields:
		$markerArray["###NEWS_CATEGORY###"] = $this->local_cObj->stdWrap($this->categories[$row["category"]],$lConf["category_stdWrap."]);
		
			// related
		$markerArray["###NEWS_RELATED###"] = $this->local_cObj->stdWrap($this->getRelated($row["uid"]),$lConf["related_stdWrap."]);

			// Page fields:
		$markerArray["###PAGE_UID###"] = $row["pid"];
		$markerArray["###PAGE_TITLE###"] = $this->pageArray[$row["pid"]]["title"];
		$markerArray["###PAGE_AUTHOR###"] = $this->local_cObj->stdWrap($this->pageArray[$row["pid"]]["author"],$lConf["author_stdWrap."]);
		$markerArray["###PAGE_AUTHOR_EMAIL###"] = $this->local_cObj->stdWrap($this->pageArray[$row["pid"]]["author_email"],$lConf["email_stdWrap."]);
		
			// Pass to user defined function
		if ($this->conf["itemMarkerArrayFunc"])	{
			$markerArray = $this->userProcess("itemMarkerArrayFunc",$markerArray);
		}
		$news_category=array();
		$theCatImgCode="";
		$theCatImgCodeArray=array();
		if(isset($this->categories[$row["uid"]])&&($this->config["catImageMode"]!=0 or $this->config["catTextMode"]!=0)) {
			while (list ($key, $val) = each ($this->categories[$row["uid"]])) {
				if($this->config["catTextMode"]==0){
					$markerArray["###NEWS_CATEGORY###"]="";
				}elseif($this->config["catTextMode"]==1){
					$news_category[] = $this->local_cObj->stdWrap($this->categories[$row["uid"]][$key]["title"],$lConf["category_stdWrap."]);
				}elseif($this->config["catTextMode"]==2) {
					$news_category[] = $this->local_cObj->stdWrap($this->cObj->getTypoLink($this->categories[$row["uid"]][$key]["title"],$this->categories[$row["uid"]][$key]["shortcut"]),$lConf["category_stdWrap."]);
				}elseif($this->config["catTextMode"]==3) {
					$news_category[] = $this->local_cObj->stdWrap($this->cObj->getTypoLink($this->categories[$row["uid"]][$key]["title"],$GLOBALS["TSFE"]->id,array("cat"=>$this->categories[$row["uid"]][$key]["catid"])),$lConf["category_stdWrap."]);				
				}
				if($this->config["catImageMode"]==0 or empty($this->categories[$row["uid"]][$key]["image"])){
					$markerArray["###NEWS_CATEGORY_IMAGE###"]="";
				} else {
					$lConf["image."]["file"] = "uploads/pics/".$this->categories[$row["uid"]][$key]["image"];
				    $lConf["image."]["file."]["maxW"] = intval($this->config["catImageMaxWidth"]);
				    $lConf["image."]["file."]["maxH"] = intval($this->config["catImageMaxHeight"]);
					$lConf["image."]["stdWrap."]["spaceAfter"] = 0;
					if($this->config["catImageMode"]!=1){
						$lConf["image."]["stdWrap."]["typolink."]["parameter"] = ($this->config["catImageMode"]==2?$this->categories[$row["uid"]][$key]["shortcut"]:$GLOBALS["TSFE"]->id);
						$lConf["image."]["stdWrap."]["typolink."]["additionalParams"] = ($this->config["catImageMode"]==2?"":"&cat=".$this->categories[$row["uid"]][$key]["catid"]);
					}//stdWrap.htmlSpecialChars = 1")??? for xml &amp;
					$lConf["image."]["altText"] = $this->categories[$row["uid"]][$key]["title"];
					$theCatImgCodeArray[]=$this->local_cObj->IMAGE($lConf["image."]);
				}
			}
			if ($this->config["catTextMode"]!=0) {
				$news_category = implode(", ",array_slice($news_category, 0, intval($this->config["maxCatTexts"])));
				$markerArray["###NEWS_CATEGORY###"] = (strlen($news_category) < intval($this->config["catTextLength"])?$news_category:substr($news_category,0,intval($this->config["catTextLength"]))."...");
			}
			if ($this->config["catImageMode"]!=0) {
				$theCatImgCode = implode("",array_slice($theCatImgCodeArray, 0, intval($this->config["maxCatImages"])));
				$markerArray["###NEWS_CATEGORY_IMAGE###"] = $this->local_cObj->wrap(trim($theCatImgCode),$lConf["imageWrapIfAny"]);
			}
/*		} elseif($this->conf["catTextMode"]!=0) { //to show categories not defined by tt_news_cat_mm
			$markerArray["###NEWS_CATEGORY###"] = $this->categories["0"][$row["category"]];
			$markerArray["###NEWS_CATEGORY_IMAGE###"]="";*/
		} else {
			$markerArray["###NEWS_CATEGORY_IMAGE###"]="";
			$markerArray["###NEWS_CATEGORY###"]="";
		}
		return $markerArray;
	}

	/**
	 * Gets related news.
	 */
	function getRelated($uid)	{
		$veryLocal_cObj = t3lib_div::makeInstance("tslib_cObj");		// Local cObj.
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,title,short,datetime,archivedate', 'tt_news,tt_news_related_mm AS M', 'tt_news.uid=M.uid_foreign AND M.uid_local='.intval($uid));

		$lines = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$veryLocal_cObj->start($row,"tt_news");
			$lines[] = $veryLocal_cObj->cObjGetSingle($this->conf["getRelatedCObject"],$this->conf["getRelatedCObject."],"getRelated");
		}
		return implode("",$lines);
	}

	/**
	 * Calls user function
	 */
	function userProcess($mConfKey,$passVar)	{
		if ($this->conf[$mConfKey])	{
			$funcConf = $this->conf[$mConfKey."."];
			$funcConf["parentObj"]=&$this;
			$passVar = $GLOBALS["TSFE"]->cObj->callUserFunction($this->conf[$mConfKey], $funcConf, $passVar);
		}
		return $passVar;
	}

	/**
	 * Returning template subpart marker
	 */
	function spMarker($subpartMarker)	{
		$sPBody = substr($subpartMarker,3,-3);
		$altSPM = "";
		if (isset($this->conf["altMainMarkers."]))	{
			$altSPM = trim($this->cObj->stdWrap($this->conf["altMainMarkers."][$sPBody],$this->conf["altMainMarkers."][$sPBody."."]));
			$GLOBALS["TT"]->setTSlogMessage("Using alternative subpart marker for '".$subpartMarker."': ".$altSPM,1);
		}
		return $altSPM ? $altSPM : $subpartMarker;
	}


	/**
	 * Generates a search where clause.
	 */
	function searchWhere($sw)	{
		$where=$this->cObj->searchWhere($sw, $this->searchFieldList, 'tt_news');
		return $where;
	}

	/**
	 * Format string with nl2br and htmlspecialchars()
	 */
	function formatStr($str)	{
		if (is_array($this->conf["general_stdWrap."]))	{
			$str = $this->local_cObj->stdWrap($str,$this->conf["general_stdWrap."]);
		}
		return $str;
	}

	/**
	 * Returns alternating layouts
	 */
	function getLayouts($templateCode,$alternativeLayouts,$marker)	{
		$out=array();
		for($a=0;$a<$alternativeLayouts;$a++)	{
			$m= "###".$marker.($a?"_".$a:"")."###";
			if(strstr($templateCode,$m))	{
				$out[]=$GLOBALS["TSFE"]->cObj->getSubpart($templateCode, $m);
			} else {
				break;
			}
		}
		return $out;
	}
}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tt_news/pi/class.tx_ttnews.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tt_news/pi/class.tx_ttnews.php"]);
}

?>
