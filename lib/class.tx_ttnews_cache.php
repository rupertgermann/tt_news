<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2009 Rupert Germann (rupi@gmx.li)
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
 * class.tx_ttnews.php
 *
 * $Id: class.tx_ttnews.php 20699 2009-05-26 10:18:43Z rupi $
 *
 * @author Rupert Germann <rupi@gmx.li>
 */

/**
 * [ Add description ]
 *
 * @author	Rupert Germann <rupi@gmx.li>
 * @package	TYPO3
 * @subpackage
 */
class tx_ttnews_cache {


	var $cachingEngine;

	var $tt_news_cache;
	var $lifetime = 0;
	var $ACCESS_TIME = 0;

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function tx_ttnews_cache($cachingEngine) {

		$this->cachingEngine = $cachingEngine;

		switch ($this->cachingEngine) {
			case 'cachingFramework':
				$this->initCachingFramework();
			break;

			case 'memcached':
				$this->initMemcached();
			break;

			// default = internal
		}



	}

	function initMemcached() {
		$this->tt_news_cache = new Memcache;
		$this->tt_news_cache->connect('localhost', 11211);
	}




	function initCachingFramework() {
		try {
			$GLOBALS['typo3CacheFactory']->create(
				'tt_news_cache',
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache']['frontend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache']['backend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache']['options']);
		} catch (t3lib_cache_exception_DuplicateIdentifier $e) {
			// do nothing, a tt_news_cache cache already exists
		}

		$this->tt_news_cache = $GLOBALS['typo3CacheManager']->getCache('tt_news_cache');

	}


	function set($hash, $content, $ident) {
		if ($this->cachingEngine=='cachingFramework' ) {
			$this->tt_news_cache->set($hash, $content, array('ident_' . $ident),$this->lifetime);

		} elseif ( $this->cachingEngine=='memcached') {
			$this->tt_news_cache->set($hash, $content, false, $this->lifetime);

		} else {
			$table = 'tt_news_cache';
			$fields_values = array('identifier' => $hash, 'content' => $content, 'crdate' => $GLOBALS['EXEC_TIME'], 'tags' => $ident, 'lifetime' => $this->lifetime);
			$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, 'identifier=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($hash, $table));
			$GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $fields_values);
		}

	}


	function get($hash) {
		$cacheEntry = FALSE;
		if ($this->cachingEngine=='cachingFramework' || $this->cachingEngine=='memcached') {
			$cacheEntry = $this->tt_news_cache->get($hash);
		} else {
			$select_fields = 'content';
			$from_table = 'tt_news_cache';
			$where_clause = 'identifier=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($hash, $from_table);

//			if ($period > 0) {
				$where_clause .= ' AND (crdate+lifetime>' . $this->ACCESS_TIME.' OR lifetime=0)';
//			}

			$cRec = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($select_fields, $from_table, $where_clause);

			if (is_array($cRec[0]) && $cRec[0]['content'] != '') {
				$cacheEntry = $cRec[0]['content'];
			}
		}

		return $cacheEntry;

	}

}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_cache.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/lib/class.tx_ttnews_cache.php']);
}
?>