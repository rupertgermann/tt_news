<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Rupert Germann <rg@rgdata.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   51: class tx_ttnews_realurl
 *   62:     function main(&$params, &$ref)
 *  100:     function id2alias($value,$cfg)
 *  116:     function alias2id($value,$cfg)
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * realUrl userfunction which adds a default value for the GETvar tt_news[cat]
 *
 *
 * $Id: index.php 8910 2008-04-15 07:03:23Z rupertgermann $
 *
 * @author	Rupert Germann <rg@rgdata.de>
 * @package	TYPO3
 * @subpackage	tt_news
 */
class tx_ttnews_realurl {


	/**
	 * Main function, called for both encoding and decoding of URLs.
	 * Based on the "mode" key in the $params array it branches out to either decode or encode functions.
	 *
	 * @param	array		Parameters passed from parent object, "tx_realurl". Some values are passed by reference! (paramKeyValues, pathParts and pObj)
	 * @param	tx_realurl		Copy of parent object. Not used.
	 * @return	mixed		Depends on branching.
	 */
	function main(&$params, &$ref)	{
		$this->pObj = &$params['pObj'];


		/**
		 * FIXME:
		 * how to get this from realUrl config ?
		 * seems not to be possible to detect reliably which part of the postvarset is the current one
		 */


		$lookUpTableCfg = array(
				'table' => 'tt_news_cat',
				'id_field' => 'uid',
				'alias_field' => 'title',
				'addWhereClause' => ' AND deleted=0',
				'useUniqueCache' => 1,
				'useUniqueCache_conf' => array(
					'strtolower' => 1,
					'spaceCharacter' => '-',
				),
				'autoUpdate' => 1,
				'tx_ttnews_valueDefault' => $this->pObj->extConf['tx_ttnews_valueDefault']
			);

		if ($params['decodeAlias'])	{
			return $this->alias2id($params['value'],$lookUpTableCfg);
		} else {
			return $this->id2alias($params['value'],$lookUpTableCfg);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$value: ...
	 * @param	[type]		$cfg: ...
	 * @return	[type]		...
	 */
	function id2alias($value,$cfg)	{
		if (!$value) {
			$value = $cfg['tx_ttnews_valueDefault'];
		} else {
			$value = $this->pObj->lookUpTranslation($cfg, $value);
		}
		return $value;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$value: ...
	 * @param	[type]		$cfg: ...
	 * @return	[type]		...
	 */
	function alias2id($value,$cfg)	{
		if ($value == $cfg['tx_ttnews_valueDefault']) {
			$value = false;
		} else {
			$value = $this->pObj->lookUpTranslation($cfg, $value, TRUE);
		}
		return $value;
	}



}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_realurl.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_realurl.php']);
}
?>