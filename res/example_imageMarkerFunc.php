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
 * This is an example for processing the news images by a user function.
 *
 * $Id: example_imageMarkerFunc.php,v 1.1.1.1 2004/11/04 19:12:30 rgermann Exp $
 *
 * @author	Rupert Germann <rupi@gmx.li>
 */

/**
 * Example function that adds 4 new imageWraps to the images in "SINGLE" view. 
 * Each image will be wrapped with its own css-class to display f.e. different background colors. 
 * The function expects, that you define image wraps in your TS setup, 
 * that contain a number (see example TS below)  
 * "imageWrapIfAny" (without number) can still be used to wrap all images
 */ 
/*  add this to your TS-setup:

  	# include the php script
    includeLibs.imageMarkerFunc = EXT:tt_news/res/example_imageMarkerFunc.php

	plugin.tt_news {
 		# call user function
 		imageMarkerFunc = user_imageMarkerFunc
 		displaySingle {
 			imageWrapIfAny_0 = <div class="news-single-images-container0"> | </div>
    		imageWrapIfAny_1 = <div class="news-single-images-container1"> | </div>
    		imageWrapIfAny_2 = <div class="news-single-images-container2"> | </div>
    		imageWrapIfAny_3 = <div class="news-single-images-container3"> | </div>
		}
		# example styles for the new wraps 
		_CSS_DEFAULT_STYLE (
		  	.news-single-images-container0, .news-single-images-container1, .news-single-images-container2, .news-single-images-container3 { width: 200px; margin-left: 5px; }
			.news-single-images-container0 { background-color: #900; }
			.news-single-images-container1 { background-color: #090; }
			.news-single-images-container2 { background-color: #009; }
			.news-single-images-container3 { background-color: #990; }
		)
 	}
 		
*/
/** 
 * Example function for displaying amenu items in yearly periods.
 *   
 * @param 	array	$paramArray: $markerArray and $config of the current news item in an array
 * @return	array	the processed markerArray 
 */
function user_imageMarkerFunc($paramArray,$conf){

	$markerArray = $paramArray[0];
	$lConf = $paramArray[1];
    $this = &$conf['parentObj']; // make a reference to the parent-object
	$row = $this->local_cObj->data;
	
	$imageNum = isset($lConf['imageCount']) ? $lConf['imageCount']:1;
	$imageNum = t3lib_div::intInRange($imageNum, 0, 100);
	$theImgCode = '';
	$imgs = t3lib_div::trimExplode(',', $row['image'], 1);
	$imgsCaptions = explode(chr(10), $row['imagecaption']);
	reset($imgs);
	$cc = 0;

	// unset the first image in the array (in single view) if the TS-var 'firstImageIsPreview' is set
	if (count($imgs) > 1 && $this->config['firstImageIsPreview'] && $textRenderObj == 'displaySingle') {
		unset($imgs[0]);
		unset($imgsCaptions[0]);
		$cc = 1;
	}
	while (list(, $val) = each($imgs)) {
		if ($cc == $imageNum) break;
		if ($val) {
		 	$lConf['image.']['altText'] = ''; // reset altText
			$lConf['image.']['altText'] = $lConf['image.']['altText']; // set altText to value from TS
			$lConf['image.']['file'] = 'uploads/pics/'.$val;
			switch($lConf['imgAltTextField']) {
				case 'image': 
					$lConf['image.']['altText'] .= $val;
				break;
				case 'imagecaption': 
					$lConf['image.']['altText'] .= $imgsCaptions[$cc];
				break;
				default:
					$lConf['image.']['altText'] .= $row[$lConf['imgAltTextField']];
			} 
		}
		$theImgCode .= $this->local_cObj->wrap($this->local_cObj->IMAGE($lConf['image.']).$this->local_cObj->stdWrap($imgsCaptions[$cc], $lConf['caption_stdWrap.']),$lConf['imageWrapIfAny_'.$cc]);
		$cc++;
	}
	$markerArray['###NEWS_IMAGE###'] = '';
	if ($cc) {
		$markerArray['###NEWS_IMAGE###'] = $this->local_cObj->wrap(trim($theImgCode), $lConf['imageWrapIfAny']);
		
	}
		return $markerArray;
	
}



?>