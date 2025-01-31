<?php
/**
 * @copyright   ©2023 Maatify.dev
 * @Liberary    DB-Model-V3
 * @Project     DB-Model-V3
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2023-05-21 4:17 PM
 * @see         https://www.maatify.dev Maatify.com
 * @link        https://github.com/Maatify/DB-Model-V3  view project on GitHub
 * @link        https://github.com/Maatify/Logger (maatify/logger)
 * @link        https://github.com/Maatify/Json (maatify/json)
 * @link        https://github.com/Maatify/Post-Validator-V2 (maatify/post-validator-v2)
 * @copyright   ©2023 Maatify.dev
 * @note        This Project using for MYSQL PDO (PDO_MYSQL).
 * @note        This Project extends other libraries maatify/logger, maatify/json, maatify/post-validator.
 *
 * @note        This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 *
 */

namespace Maatify\Model;

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

    protected function PaginationRows(string $tableName, string $columns = '*', string $where = '', array $wheresVal = []): array
    {
        return $this->Rows($tableName,
            $columns,
            $where . ' ' . $this->AddWherePagination(),
            $wheresVal);
    }

    protected function PaginationThisTableRows(string $where = '', array $wheresVal = []): array
    {
        return $this->Rows($this->tableName,
            '*',
            $where . ' ' . $this->AddWherePagination(),
            $wheresVal);
    }

    protected function ValidatePostedTableId(): int
    {
        $this->row_id = (int)$this->postValidator->Require($this->identify_table_id_col_name, 'int');
        if(!($this->current_row = $this->RowThisTable('*', "`$this->identify_table_id_col_name` = ? ", [$this->row_id]))){
            Json::Incorrect("$this->identify_table_id_col_name", "$this->identify_table_id_col_name Not Found", $this->class_name . __LINE__);
        }
        return $this->row_id;
    }

    protected function ExistIDThisTable(int $id): bool
    {
        return $this->RowIsExistThisTable("`$this->identify_table_id_col_name` = ? ", [$id]);
    }

    protected function RowThisTableByID(int $id): array
    {
        return $this->RowThisTable('*', "`$this->identify_table_id_col_name` = ? ", [$id]);
    }

    //========================================================================

    protected function TableName(): string
    {
        return $this->tableName;
    }

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
                $query .= "IFNULL(`$this->tableName`.`$col`," . ($this->ColJoinTypeToString($type) === '' ? "''" : $this->ColJoinTypeToString($type)) . ") as $this->tableAlias" . '_' . $col . ', ';
            }
        }

        return rtrim($query, ', ');
    }

    protected function GetCols(): array
    {
        return $this->cols;
    }

    protected function MaxIDThisTable(): int
    {
        return (int)$this->ColThisTable("`$this->identify_table_id_col_name`", "`$this->identify_table_id_col_name` > ? ORDER BY `$this->identify_table_id_col_name` DESC LIMIT 1", [0]);
    }

    protected function MaxColThisTable(string $column): int
    {
        return (int)$this->ColThisTable("MAX(`$column`)");
    }

    protected function Row(string $tableName, string $columns = '*', string $where = '', array $wheresVal = []): array
    {
        try {
            return $this->FetchRow($this->PrepareSelect($tableName, $columns, $where, $wheresVal));
        } catch (PDOException $e) {
            return $this->LogError($e, 'Row ' . $tableName . ' where ' . $where . ' ' . $columns, __LINE__, $wheresVal);
        }
    }

    protected function RowThisTable(string $columns = '*', string $where = '', array $wheresVal = []): array
    {
        try {
            return $this->Row($this->tableName, $columns, $where, $wheresVal);
        } catch (PDOException $e) {
            return $this->LogError($e, 'RowThisTable ' . $this->tableName . ' where ' . $where, __LINE__, $wheresVal);
        }
    }

    protected function ColThisTable(string $columns = '*', string $where = '', array $wheresVal = []): string
    {
        try {
            return (string)$this->Col($this->tableName, $columns, $where, $wheresVal);
        } catch (PDOException $e) {
            $this->LogError($e, 'ColThisTable ' . $this->tableName . ' where ' . $where, __LINE__, $wheresVal);

            return '';
        }
    }

    protected function Col(string $tableName, string $columns = '*', string $where = '', array $wheresVal = []): string
    {
        try {
            return (string)$this->FetchCol($this->PrepareSelect($tableName, $columns, $where, $wheresVal));
        } catch (PDOException $e) {
            $this->LogError($e, 'Col ' . $this->tableName . ' where ' . $where, __LINE__, $wheresVal);

            return '';
        }
    }

    protected function RowsThisTable(string $columns = '*', string $where = '', array $wheresVal = []): array
    {
        try {
            return $this->Rows($this->tableName, $columns, $where, $wheresVal);
        } catch (PDOException $e) {
            return $this->LogError($e, 'RowsThisTable ' . $this->tableName . ' where ' . $where, __LINE__, $wheresVal);
        }
    }

    protected function Rows(string $tableName, string $columns = '*', string $where = '', array $wheresVal = []): array
    {
        try {
            return $this->FetchRows($this->PrepareSelect($tableName, $columns, $where, $wheresVal));
        } catch (PDOException $e) {
            return $this->LogError($e, 'Rows ' . $this->tableName . ' where ' . $where . PHP_EOL, __LINE__, $wheresVal);
        }
    }

    protected function RowIsExistThisTable(string $where = '', array $wheresVal = []): bool
    {
        return (bool)$this->ColThisTable('*', $where, $wheresVal);
    }

    protected function RowISExist(string $tableName, string $where = '', array $wheresVal = []): bool
    {
        return (bool)$this->Col($tableName, '*', $where, $wheresVal);
    }


    // ======================= Json =======================


    protected function JsonCol(array $array): string
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

    protected function JsonColLimit(array $array, int $limit): string
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

    protected function JsonColRandomLimit(array $array, int $limit): string
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