<?php
/**
 * Created by PhpStorm.
 * User: rupertgermann
 * Date: 31.10.18
 * Time: 17:32
 */

namespace RG\TtNews\Database;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use InvalidArgumentException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Class Database
 */
class Database implements SingletonInterface
{
    /**
     * @var ConnectionPool
     */
    protected ConnectionPool $connectionPool;

    /**
     * @param $tableName
     *
     * @return array
     * @throws Exception
     */
    public function admin_get_fields($tableName): array
    {
        $columns = $this->getConnection($tableName)->executeQuery(
            'SHOW COLUMNS FROM ' . $tableName
        )->fetchAllAssociative();
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
     * @return Result
     * @throws Exception
     */
    public function exec_SELECT_queryArray($queryParts): Result
    {
        return $this->exec_SELECTquery(
            $queryParts['SELECT'],
            $queryParts['FROM'],
            $queryParts['WHERE'],
            $queryParts['GROUPBY'],
            $queryParts['ORDERBY'],
            $queryParts['LIMIT']
        );
    }

    /**
     * @param        $select_fields
     * @param        $from_table
     * @param        $where_clause
     * @param string $groupBy
     * @param string $orderBy
     * @param string $limit
     *
     * @return Result
     * @throws Exception
     */
    public function exec_SELECTquery(
        $select_fields,
        $from_table,
        $where_clause,
        string $groupBy = '',
        string $orderBy = '',
        string $limit = ''
    ): Result {
        $query = $this->SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);

        return $this->getConnection($from_table)->executeQuery($query);
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
    public function SELECTquery($select_fields, $from_table, $where_clause, string $groupBy = '', string $orderBy = '', string $limit = ''): string
    {
        // Table and fieldnames should be "SQL-injection-safe" when supplied to this function
        // Build basic query
        $query = 'SELECT ' . $select_fields . ' FROM ' . $from_table . ((string)$where_clause !== '' ? ' WHERE ' . $where_clause : '');
        // Group by
        $query .= $groupBy !== '' ? ' GROUP BY ' . $groupBy : '';
        // Order by
        $query .= $orderBy !== '' ? ' ORDER BY ' . $orderBy : '';
        // Group by
        $query .= $limit !== '' ? ' LIMIT ' . $limit : '';

        // Return query

        return $query;
    }

    /**
     * @param Result $lres
     *
     * @return array|bool
     * @throws Exception
     */
    public function sql_fetch_assoc(Result $lres): array|bool
    {
        return $lres->fetchAssociative();
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
     * @throws Exception
     */
    public function exec_SELECTgetRows(
        $select_fields,
        $from_table,
        $where_clause,
        string $groupBy = '',
        string $orderBy = '',
        string $limit = '',
        string $uidIndexField = ''
    ): ?array {
        $res = $this->exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);

        $output = [];
        $firstRecord = true;
        while ($record = $this->sql_fetch_assoc($res)) {
            if ($uidIndexField) {
                if ($firstRecord) {
                    $firstRecord = false;
                    if (!array_key_exists($uidIndexField, $record)) {
                        throw new InvalidArgumentException(
                            'The given $uidIndexField "' . $uidIndexField . '" is not available in the result.',
                            1_432_933_855
                        );
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
    public function fullQuoteStr($str, $table, bool $allowNull = false): string
    {
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
    public function cleanIntList($list): string
    {
        return implode(',', GeneralUtility::intExplode(',', $list));
    }

    /**
     * @param Result $res
     *
     * @return array|bool
     * @throws Exception
     */
    public function sql_fetch_row(Result $res): array|bool
    {
        return $res->fetchNumeric();
    }

    /**
     * @param Result $res
     *
     * @return int
     * @throws Exception
     */
    public function count(Result $res): int
    {
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
     * @return array|bool|null
     * @throws Exception
     */
    public function exec_SELECTgetSingleRow(
        $select_fields,
        $from_table,
        $where_clause,
        string $groupBy = '',
        string $orderBy = '',
        bool $numIndex = false
    ): bool|array|null {
        $res = $this->exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, '1');
        $output = null;
        if ($res) {
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
    protected function getSelectMmQueryParts(
        $select,
        $local_table,
        $mm_table,
        $foreign_table,
        string $whereClause = '',
        string $groupBy = '',
        string $orderBy = '',
        string $limit = ''
    ): array {
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
            'LIMIT' => $limit,
        ];
    }

    /**
     * Removes the prefix "ORDER BY" from the input string.
     * This function is used when you call the exec_SELECTquery() function and want to pass the ORDER BY parameter by
     * can't guarantee that "ORDER BY" is not prefixed. Generally; This function provides a work-around to the
     * situation where you cannot pass only the fields by which to order the result.
     *
     * @param string $str eg. "ORDER BY title, uid
     *
     * @return string eg. "title, uid
     * @see exec_SELECTquery(), stripGroupBy()
     */
    public function stripOrderBy(string $str): string
    {
        return preg_replace('/^(?:ORDER[[:space:]]*BY[[:space:]]*)+/i', '', trim($str));
    }

    /**
     * Counts the number of rows in a table.
     *
     * @param string $field Name of the field to use in the COUNT() expression (e.g. '*')
     * @param string $table Name of the table to count rows for
     * @param string $where (optional) WHERE statement of the query
     *
     * @return int|bool Number of rows counter (int) or FALSE if something went wrong (bool)
     * @throws Exception
     */
    public function exec_SELECTcountRows(string $field, string $table, string $where = '1=1'): int|bool
    {
        $count = false;
        $resultSet = $this->exec_SELECTquery('COUNT(' . $field . ')', $table, $where);
        if ($resultSet) {
            [$count] = $this->sql_fetch_row($resultSet);
            $count = (int)$count;
        }

        return $count;
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
     * @return Result
     * @throws Exception
     */
    public function exec_SELECT_mm_query(
        $select,
        $local_table,
        $mm_table,
        $foreign_table,
        string $whereClause = '',
        string $groupBy = '',
        string $orderBy = '',
        string $limit = ''
    ): Result {
        $queryParts = $this->getSelectMmQueryParts(
            $select,
            $local_table,
            $mm_table,
            $foreign_table,
            $whereClause,
            $groupBy,
            $orderBy,
            $limit
        );

        return $this->exec_SELECT_queryArray($queryParts);
    }

    /**
     * @param string $table
     *
     * @return Connection
     */
    protected function getConnection(string $table): Connection
    {
        if (empty($this->connectionPool)) {
            $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        }

        return $this->connectionPool->getConnectionForTable($table);
    }

    /**
     * @return Database
     */
    public static function getInstance(): Database
    {
        return GeneralUtility::makeInstance(self::class);
    }
}
