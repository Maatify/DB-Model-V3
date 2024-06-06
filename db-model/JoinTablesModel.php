<?php
/**
 * @copyright   ©2024 Maatify.dev
 * @Liberary    DB-Model-V3
 * @Project     DB-Model-V3
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2024-07-11 11:39 AM
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

/*abstract class JoinTablesModel extends PDOBuilder
{
    const IDENTIFY_TABLE_ID_COL_NAME = 'id';
    protected string $identify_table_id_col_name = self::IDENTIFY_TABLE_ID_COL_NAME;

    // ======================= Join This Table =======================
    private function generateJoinColumns(bool $withAlias = false): string
    {
        $cols = '';
        foreach ($this->cols as $col => $type) {
            if ($col != $this->identify_table_id_col_name) {
                $defaultValue = match ($type) {
                    1 => 0,
                    2 => "0",
                    default => "''",
                };
                $columnAlias = !empty($withAlias) ? $this->tableAlias . '_' . $col : $col;
                $cols .= " IFNULL(`$this->tableName`.`$col`, $defaultValue) as $columnAlias, ";
            }
        }
        return rtrim($cols, ', ');
    }

    private function generateJoinUniqueColumns(array $columns_with_types, bool $withAlias = false): string
    {
        $cols = '';
        foreach ($columns_with_types as $col => $type) {
            if ($col != $this->identify_table_id_col_name) {
                $defaultValue = match ($type) {
                    1 => 0,
                    2 => "0",
                    default => "''",
                };
                $columnAlias = !empty($withAlias) ? $this->tableAlias . '_' . $col : $col;
                $cols .= " IFNULL(`$this->tableName`.`$col`, $defaultValue) as $columnAlias, ";
            }
        }
        return rtrim($cols, ', ');
    }

    private function generateJoin(string $joinType, string $table_name, bool $withAlias = false): array
    {
        $joinClause = " $joinType JOIN `$this->tableName` ON `$this->tableName`.`$this->identify_table_id_col_name` = `$table_name`.`$this->identify_table_id_col_name`";
        $columns = $this->generateJoinColumns($withAlias);
        return [$joinClause, $columns];
    }

    private function generateJoinUniqueCols(string $joinType, string $table_name, array $columns_with_types, bool $withAlias = false): array
    {
        $joinClause = " $joinType JOIN `$this->tableName` ON `$this->tableName`.`$this->identify_table_id_col_name` = `$table_name`.`$this->identify_table_id_col_name`";
        $columns = $this->generateJoinUniqueColumns($columns_with_types, $withAlias);
        return [$joinClause, $columns];
    }

    public function InnerJoinThisTableWithTableAlias(string $table_name): array
    {
        return $this->generateJoin('INNER', $table_name, true);
    }

    public function InnerJoinThisTableWithoutTableAlias(string $table_name): array
    {
        return $this->generateJoin('INNER', $table_name);
    }

    public function LeftJoinThisTableWithTableAlias(string $table_name): array
    {
        return $this->generateJoin('LEFT', $table_name, true);
    }

    public function LeftJoinThisTableWithoutTableAlias(string $table_name): array
    {
        return $this->generateJoin('LEFT', $table_name);
    }

    public function InnerJoinThisTableWithUniqueCols(string $table_name, array $columns_with_types): array
    {
        return $this->generateJoinUniqueCols('INNER', $table_name, $columns_with_types);
    }

    public function InnerJoinThisTableWithUniqueColsWithTableAlias(string $table_name, array $columns_with_types): array
    {
        return $this->generateJoinUniqueCols('INNER', $table_name, $columns_with_types, true);
    }

    public function LeftJoinThisTableWithUniqueCols(string $table_name, array $columns_with_types): array
    {
        return $this->generateJoinUniqueCols('LEFT', $table_name, $columns_with_types);
    }

    public function LeftJoinThisTableWithUniqueColsWithTableAlias(string $table_name, array $columns_with_types): array
    {
        return $this->generateJoinUniqueCols('LEFT', $table_name, $columns_with_types, true);
    }
}*/

abstract class JoinTablesModel extends PDOBuilder
{
    private function generateJoinColumns(array $columns_with_types, bool $withAlias = false): string
    {
        $cols = '';
        foreach ($columns_with_types as $col => $type) {
            if ($col !== $this->identify_table_id_col_name) {
                $defaultValue = match ($type) {
                    1 => 0,
                    2 => "0",
                    default => "''",
                };
                $columnAlias = $withAlias ? $this->tableAlias . '_' . $col : $col;
                $cols .= "IFNULL(`$this->tableName`.`$col`, $defaultValue) as $columnAlias, ";
            }
        }
        return rtrim($cols, ', ');
    }

    private function generateJoinClause(string $joinType, string $table_name): string
    {
        return "$joinType JOIN `$this->tableName` ON `$this->tableName`.`$this->identify_table_id_col_name` = `$table_name`.`$this->identify_table_id_col_name`";
    }

    private function generateJoin(string $joinType, string $table_name, array $columns_with_types = [], bool $withAlias = false): array
    {
        $joinClause = $this->generateJoinClause($joinType, $table_name);
        $columns = $this->generateJoinColumns($columns_with_types ?: $this->cols, $withAlias);
        return [$joinClause, $columns];
    }

    public function InnerJoinThisTable(string $table_name, array $columns_with_types = [], bool $withAlias = false): array
    {
        return $this->generateJoin('INNER', $table_name, $columns_with_types, $withAlias);
    }

    public function LeftJoinThisTable(string $table_name, array $columns_with_types = [], bool $withAlias = false): array
    {
        return $this->generateJoin('LEFT', $table_name, $columns_with_types, $withAlias);
    }
}
