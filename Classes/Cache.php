<?php

/*
 * Copyright notice
 *
 * (c) 2004-2018 Rupert Germann <rupi@gmx.li>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

namespace RG\TtNews;

/**
 * class.tx_ttnews.php
 *
 * $Id: class.tx_ttnews.php 20699 2009-05-26 10:18:43Z rupi $
 *
 */

/**
 * [ Add description ]
 *
 */
class Cache
{
    public $cachingEngine;

    /**
     * @var \memcache
     */
    public $tt_news_cache;
    public $lifetime = 0;
    public $ACCESS_TIME = 0;

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    public function __construct($cachingEngine)
    {
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

    public function initMemcached()
    {
        $this->tt_news_cache = new \Memcache;
        $this->tt_news_cache->connect('localhost', 11211);
    }

    public function initCachingFramework()
    {
        try {
            $GLOBALS['typo3CacheFactory']->create(
                'tt_news_cache',
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache']['frontend'],
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache']['backend'],
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_news_cache']['options']
            );
        } catch (\TYPO3\CMS\Core\Cache\Exception\DuplicateIdentifierException $e) {
            // do nothing, a tt_news_cache cache already exists
        }

        $this->tt_news_cache = $GLOBALS['typo3CacheManager']->getCache('tt_news_cache');
    }

    public function set($hash, $content, $ident)
    {
        if ($this->cachingEngine == 'cachingFramework') {
            $this->tt_news_cache->set($hash, $content, ['ident_' . $ident], $this->lifetime);
        } elseif ($this->cachingEngine == 'memcached') {
            $this->tt_news_cache->set($hash, $content, false, $this->lifetime);
        } else {
            $table = 'tt_news_cache';
            $fields_values = [
                'identifier' => $hash,
                'content' => $content,
                'crdate' => $GLOBALS['EXEC_TIME'],
                'tags' => $ident,
                'lifetime' => $this->lifetime
            ];
            $GLOBALS['TYPO3_DB']->exec_DELETEquery(
                $table,
                'identifier=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($hash, $table)
            );
            $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $fields_values);
        }
    }

    public function get($hash)
    {
        $cacheEntry = false;
        if ($this->cachingEngine == 'cachingFramework' || $this->cachingEngine == 'memcached') {
            $cacheEntry = $this->tt_news_cache->get($hash);
        } else {
            $select_fields = 'content';
            $from_table = 'tt_news_cache';
            $where_clause = 'identifier=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($hash, $from_table);

            //			if ($period > 0) {
            $where_clause .= ' AND (crdate+lifetime>' . $this->ACCESS_TIME . ' OR lifetime=0)';
            //			}

            $cRec = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($select_fields, $from_table, $where_clause);

            if (is_array($cRec[0]) && $cRec[0]['content'] != '') {
                $cacheEntry = $cRec[0]['content'];
            }
        }

        return $cacheEntry;
    }
}
