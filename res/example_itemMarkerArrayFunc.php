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
 * This is an example function for displaying links to atached files from news articles.
 * it uses the function itemMarkerArrayFunc() from the tt_news class fill the new (html)template
 * markers ###FILE_LINK### and ###TEXT_FILES### with values from the database field "news_files"
 *
 *
 * $Id$
 *
 * @author	Rupert Germann <rupi@gmx.li>
 */

/**
 * Example TS-setup for the display of the filelinks.
 * see: http://typo3.org/documentation/document-library/doc_core_tsref/filelink/
 * for more details about configuring filelinks
 */
/*
		add this to your TS setup:

  		# include the php script
		includeLibs.displayFileLinks = EXT:tt_news/res/example_itemMarkerArrayFunc.php
		# call user function
		plugin.tt_news.itemMarkerArrayFunc = user_displayFileLinks

		# configure some options
		plugin.tt_news{
		  newsFiles {
		    path = uploads/media/
		    icon = 1
		    stdWrap.wrap =   | <br />
		   }
		}
*/
/**
 * Example function for displaying links to atached files from news articles.
 *
 * @param	array		$markerArray: array filled with markers from the getItemMarkerArray function in tt_news class. see: EXT:tt_news/pi/class.tx_ttnews.php
 * @param	[type]		$conf: ...
 * @return	array		the changed markerArray
 */
function user_displayFileLinks($markerArray, $conf){
	$row = $conf['parentObj']->local_cObj->data; // get the data array of the current news record
   // t3lib_div::debug($markerArray);
	$markerArray['###FILE_LINK###'] = '';
	$markerArray['###TEXT_FILES###'] = $conf['parentObj']->local_cObj->stdWrap($conf['parentObj']->pi_getLL('textFiles'), $conf['parentObj']->conf['newsFilesHeader_stdWrap.']);
	if ($row['news_files']) {
		$fileArr = explode(',',$row['news_files']);
	 	while(list(,$val)=each($fileArr)) {
		// fills the marker ###FILE_LINK### with the links to the atached files
			$markerArray['###FILE_LINK###'] .= $conf['parentObj']->local_cObj->filelink($val,$conf['parentObj']->conf['newsFiles.']) ;
		}
	} else { // no file atached
	    $markerArray['###FILE_LINK###']='';
		$markerArray['###TEXT_FILES###']='';
	}

	return $markerArray;

}



?>