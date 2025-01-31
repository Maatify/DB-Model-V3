<?php
/**
 * @copyright   ©2025 Maatify.dev
 * @Liberary    DB-Model
 * @Project     DB-Model
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2025-01-31 5:17 PM
 * @see         https://www.maatify.dev Maatify.com
 * @link        https://github.com/Maatify/DB-Model-V3  view project on GitHub
 * @link        https://github.com/Maatify/Logger (maatify/logger)
 * @link        https://github.com/Maatify/Json (maatify/json)
 * @link        https://github.com/Maatify/Post-Validator-V2 (maatify/post-validator-v2)
 * @note        This Project using for MYSQL PDO (PDO_MYSQL).
 * @note        This Project extends other libraries maatify/logger, maatify/json, maatify/post-validator.
 *
 * @note        This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 *
 */

declare(strict_types = 1);

namespace Maatify\ModelTwo;

use Maatify\Json\Json;
use Maatify\PostValidatorV2\PostValidatorV2;
use PDOException;

abstract class Model extends PaginationModel
{
    protected PostValidatorV2 $postValidator;
    protected string $class_name;
    protected int $row_id = 0;
    const IDENTIFY_TABLE_ID_COL_NAME = 'id';
    protected string $identify_table_id_col_name = self::IDENTIFY_TABLE_ID_COL_NAME;
    protected array $current_row;

    protected function paginationRows(string $tableName, string $columns = '*', string $where = '', array $wheresVal = []): array
    {
        return $this->rows($tableName,
            $columns,
            $where . ' ' . $this->addWherePagination(),
            $wheresVal);
    }

    protected function paginationThisTableRows(string $where = '', array $wheresVal = []): array
    {
        return $this->rows($this->tableName,
            '*',
            $where . ' ' . $this->addWherePagination(),
            $wheresVal);
    }

    protected function validatePostedTableId(): int
    {
        $this->row_id = (int)$this->postValidator->Require($this->identify_table_id_col_name, 'int');
        if(!($this->current_row = $this->rowThisTable('*', "`$this->identify_table_id_col_name` = ? ", [$this->row_id]))){
            Json::Incorrect("$this->identify_table_id_col_name", "$this->identify_table_id_col_name Not Found", $this->class_name . __LINE__);
        }
        return $this->row_id;
    }

    protected function existIDThisTable(int $id): bool
    {
        return $this->rowIsExistThisTable("`$this->identify_table_id_col_name` = ? ", [$id]);
    }

    protected function rowThisTableByID(int $id): array
    {
        return $this->rowThisTable('*', "`$this->identify_table_id_col_name` = ? ", [$id]);
    }

    //========================================================================


    protected function ColsJoin(): string
    {
        if (empty($this->tableAlias)) {
            $this->tableAlias = $this->tableName;
        }

        $query = '';
        /*
        IFNULL(`$tb_requirement`.`q_en`,0) as q_en,
    */
        foreach ($this->cols as $col => $type) {
            if ($col != 'id') {
                $query .= "IFNULL(`$this->tableName`.`$col`," . ($this->colJoinTypeToString($type) === '' ? "''" : $this->colJoinTypeToString($type)) . ") as $this->tableAlias" . '_' . $col . ', ';
            }
        }

        return rtrim($query, ', ');
    }

    protected function getCols(): array
    {
        return $this->cols;
    }

    protected function maxIDThisTable(): int
    {
        return (int)$this->colThisTable("`$this->identify_table_id_col_name`", "`$this->identify_table_id_col_name` > ? ORDER BY `$this->identify_table_id_col_name` DESC LIMIT 1", [0]);
    }

    protected function maxColThisTable(string $column): int
    {
        return (int)$this->colThisTable("MAX(`$column`)");
    }

    protected function row(string $tableName, string $columns = '*', string $where = '', array $wheresVal = []): array
    {
        try {
            return $this->fetchRow($this->prepareSelect($tableName, $columns, $where, $wheresVal));
        } catch (PDOException $e) {
            return $this->logError($e, 'Row ' . $tableName . ' where ' . $where . ' ' . $columns, __LINE__, $wheresVal);
        }
    }

    protected function rowThisTable(string $columns = '*', string $where = '', array $wheresVal = []): array
    {
        try {
            return $this->row($this->tableName, $columns, $where, $wheresVal);
        } catch (PDOException $e) {
            return $this->logError($e, 'RowThisTable ' . $this->tableName . ' where ' . $where, __LINE__, $wheresVal);
        }
    }

    protected function colThisTable(string $columns = '*', string $where = '', array $wheresVal = []): string
    {
        try {
            return (string)$this->col($this->tableName, $columns, $where, $wheresVal);
        } catch (PDOException $e) {
            $this->logError($e, 'ColThisTable ' . $this->tableName . ' where ' . $where, __LINE__, $wheresVal);

            return '';
        }
    }

    protected function col(string $tableName, string $columns = '*', string $where = '', array $wheresVal = []): string
    {
        try {
            return (string)$this->fetchCol($this->prepareSelect($tableName, $columns, $where, $wheresVal));
        } catch (PDOException $e) {
            $this->logError($e, 'Col ' . $this->tableName . ' where ' . $where, __LINE__, $wheresVal);

            return '';
        }
    }

    protected function rowsThisTable(string $columns = '*', string $where = '', array $wheresVal = []): array
    {
        try {
            return $this->rows($this->tableName, $columns, $where, $wheresVal);
        } catch (PDOException $e) {
            return $this->logError($e, 'RowsThisTable ' . $this->tableName . ' where ' . $where, __LINE__, $wheresVal);
        }
    }

    protected function rows(string $tableName, string $columns = '*', string $where = '', array $wheresVal = []): array
    {
        try {
            return $this->fetchRows($this->prepareSelect($tableName, $columns, $where, $wheresVal));
        } catch (PDOException $e) {
            return $this->logError($e, 'Rows ' . $this->tableName . ' where ' . $where . PHP_EOL, __LINE__, $wheresVal);
        }
    }

    protected function rowIsExistThisTable(string $where = '', array $wheresVal = []): bool
    {
        return (bool)$this->colThisTable('*', $where, $wheresVal);
    }

    protected function rowISExist(string $tableName, string $where = '', array $wheresVal = []): bool
    {
        return (bool)$this->col($tableName, '*', $where, $wheresVal);
    }


    // ======================= Json =======================


    protected function jsonCol(array $array): string
    {
        $str = "(CONCAT(
            '[',GROUP_CONCAT(distinct CONCAT( '{";
        foreach ($array as $key => $value) {
            $str .= "\"$key\":\"', ifNull($value, ''), '\",";
        }
        $str = rtrim($str, ',');
        $str .= "}') ),
            ']'
        ))";

        return $str;
    }

    protected function jsonColLimit(array $array, int $limit): string
    {
        $str = "(CONCAT(
            '[',GROUP_CONCAT(distinct CONCAT( '{";
        foreach ($array as $key => $value) {
            $str .= "\"$key\":\"', ifNull($value, ''), '\",";
        }
        $str = rtrim($str, ',');
        $str .= "}') LIMIT $limit),
            ']'
        ))";

        return $str;
    }

    protected function jsonColRandomLimit(array $array, int $limit): string
    {
        $str = "(CONCAT(
            '[',GROUP_CONCAT(distinct CONCAT( '{";
        foreach ($array as $key => $value) {
            $str .= "\"$key\":\"', ifNull($value, ''), '\",";
        }
        $str = rtrim($str, ',');
        $str .= "}') ORDER BY RAND() LIMIT $limit),
            ']'
        ))";

        return $str;
    }


}