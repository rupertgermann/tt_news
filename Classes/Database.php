<?php
/**
 * Created by PhpStorm.
 * User: rupertgermann
 * Date: 31.10.18
 * Time: 17:32
 */

namespace RG\TtNews;


use RG\TtNews\Trais\DatabaseTrait;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Class Database
 *
 * @package RG\TtNews
 */
class Database implements SingletonInterface {

    use DatabaseTrait;


    /**
     * @param $tableName
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function admin_get_fields($tableName) {
        $columns = $this->getConnection($tableName)->executeQuery('SHOW COLUMNS FROM ' . $tableName)->fetchAll();
        $fields = [];
        if (is_array($columns)) {
            foreach ($columns as $column) {
                $fields['Field'] = $column['Field'];
            }
        }

        return $fields;
    }

    /**
     * @param $queryParts
     *
     * @return \Doctrine\DBAL\Driver\Statement
     * @throws \Doctrine\DBAL\DBALException
     */
    public function exec_SELECT_queryArray($queryParts) {
        return $this->exec_SELECTquery($queryParts['SELECT'], $queryParts['FROM'], $queryParts['WHERE'], $queryParts['GROUPBY'], $queryParts['ORDERBY'], $queryParts['LIMIT']);

    }

    /**
     * @param        $select_fields
     * @param        $from_table
     * @param        $where_clause
     * @param string $groupBy
     * @param string $orderBy
     * @param string $limit
     *
     * @return \Doctrine\DBAL\Driver\Statement
     * @throws \Doctrine\DBAL\DBALException
     */
    public function exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '') {
        $query = $this->SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);

        file_put_contents(PATH_site . 'typo3temp/log.log',
            date('YmdHis', time()) . PHP_EOL . __FILE__ . '::' . __LINE__ . PHP_EOL . print_r(array(
                __METHOD__,
                $query
            ), 1) . PHP_EOL, FILE_APPEND);


        $res = $columns = $this->getConnection($from_table)->executeQuery($query);

        return $res;
    }

    /**
     * @param        $select_fields
     * @param        $from_table
     * @param        $where_clause
     * @param string $groupBy
     * @param string $orderBy
     * @param string $limit
     *
     * @return string
     */
    public function SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '') {

        // Table and fieldnames should be "SQL-injection-safe" when supplied to this function
        // Build basic query
        $query = 'SELECT ' . $select_fields . ' FROM ' . $from_table . ((string)$where_clause !== '' ? ' WHERE ' . $where_clause : '');
        // Group by
        $query .= (string)$groupBy !== '' ? ' GROUP BY ' . $groupBy : '';
        // Order by
        $query .= (string)$orderBy !== '' ? ' ORDER BY ' . $orderBy : '';
        // Group by
        $query .= (string)$limit !== '' ? ' LIMIT ' . $limit : '';

        // Return query

        return $query;
    }

    /**
     * @param \Doctrine\DBAL\Driver\Statement $lres
     *
     * @return mixed
     */
    public function sql_fetch_assoc($lres) {
        return $lres->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param        $select_fields
     * @param        $from_table
     * @param        $where_clause
     * @param string $groupBy
     * @param string $orderBy
     * @param string $limit
     * @param string $uidIndexField
     *
     * @return array|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function exec_SELECTgetRows($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '', $uidIndexField = '') {
        $res = $this->exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);

        $output = [];
        $firstRecord = true;
        while ($record = $this->sql_fetch_assoc($res)) {
            if ($uidIndexField) {
                if ($firstRecord) {
                    $firstRecord = false;
                    if (!array_key_exists($uidIndexField, $record)) {
                        throw new \InvalidArgumentException('The given $uidIndexField "' . $uidIndexField . '" is not available in the result.', 1432933855);
                    }
                }
                $output[$record[$uidIndexField]] = $record;
            } else {
                $output[] = $record;
            }
        }

        return $output;
    }

    /**
     * @param      $str
     * @param      $table
     * @param bool $allowNull
     *
     * @return string
     */
    public function fullQuoteStr($str, $table, $allowNull = false) {
        if ($allowNull && $str === null) {
            return 'NULL';
        }
        if (is_bool($str)) {
            $str = (int)$str;
        }

        return $this->getConnection($table)->quote($str);
    }

    /**
     * @param $list
     *
     * @return string
     */
    public function cleanIntList($list) {
        return implode(',', GeneralUtility::intExplode(',', $list));

    }

    /**
     * @param \Doctrine\DBAL\Driver\Statement $res
     *
     * @return mixed
     */
    public function sql_fetch_row($res) {
        return $res->fetch(\PDO::FETCH_BOTH);
    }

    /**
     * @param \Doctrine\DBAL\Driver\Statement $res
     *
     * @return mixed
     */
    public function count($res) {
        return $res->rowCount();
    }

    /**
     * @param        $select_fields
     * @param        $from_table
     * @param        $where_clause
     * @param string $groupBy
     * @param string $orderBy
     * @param bool   $numIndex
     *
     * @return mixed|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function exec_SELECTgetSingleRow($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $numIndex = false)
    {
        $res = $this->exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, '1');
        $output = null;
        if ($res !== false) {
            if ($numIndex) {
                $output = $this->sql_fetch_row($res);
            } else {
                $output = $this->sql_fetch_assoc($res);
            }
        }
        return $output;
    }

    /**
     * @param        $select
     * @param        $local_table
     * @param        $mm_table
     * @param        $foreign_table
     * @param string $whereClause
     * @param string $groupBy
     * @param string $orderBy
     * @param string $limit
     *
     * @return array
     */
    protected function getSelectMmQueryParts($select, $local_table, $mm_table, $foreign_table, $whereClause = '', $groupBy = '', $orderBy = '', $limit = '')
    {
        $foreign_table_as = $foreign_table == $local_table ? $foreign_table . StringUtility::getUniqueId('_join') : '';
        $mmWhere = $local_table ? $local_table . '.uid=' . $mm_table . '.uid_local' : '';
        $mmWhere .= ($local_table and $foreign_table) ? ' AND ' : '';
        $tables = ($local_table ? $local_table . ',' : '') . $mm_table;
        if ($foreign_table) {
            $mmWhere .= ($foreign_table_as ?: $foreign_table) . '.uid=' . $mm_table . '.uid_foreign';
            $tables .= ',' . $foreign_table . ($foreign_table_as ? ' AS ' . $foreign_table_as : '');
        }
        return [
            'SELECT' => $select,
            'FROM' => $tables,
            'WHERE' => $mmWhere . ' ' . $whereClause,
            'GROUPBY' => $groupBy,
            'ORDERBY' => $orderBy,
            'LIMIT' => $limit
        ];
    }

    /**
     * @param        $select
     * @param        $local_table
     * @param        $mm_table
     * @param        $foreign_table
     * @param string $whereClause
     * @param string $groupBy
     * @param string $orderBy
     * @param string $limit
     *
     * @return \Doctrine\DBAL\Driver\Statement
     * @throws \Doctrine\DBAL\DBALException
     */
    public function exec_SELECT_mm_query($select, $local_table, $mm_table, $foreign_table, $whereClause = '', $groupBy = '', $orderBy = '', $limit = '')
    {
        $queryParts = $this->getSelectMmQueryParts($select, $local_table, $mm_table, $foreign_table, $whereClause, $groupBy, $orderBy, $limit);
        return $this->exec_SELECT_queryArray($queryParts);
    }

    /**
     * @return object|Database
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(__CLASS__);
    }
}