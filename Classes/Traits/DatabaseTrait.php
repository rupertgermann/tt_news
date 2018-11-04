<?php

namespace RG\TtNews\Traits;


use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait DatabaseTrait
{

    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    /**
     * @param string $table
     *
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected function getQueryBuilder(string $table) {
        if(empty($this->connectionPool)) {
            $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        }
        return $this->connectionPool->getConnectionForTable($table)->createQueryBuilder();
    }

    /**
     * @param string $table
     *
     * @return \TYPO3\CMS\Core\Database\Connection
     */
    protected function getConnection(string $table) {
        if(empty($this->connectionPool)) {
            $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        }
        return $this->connectionPool->getConnectionForTable($table);
    }
}