<?php
/**
 * @copyright   ©2023 Maatify.dev
 * @Liberary    DB-Model
 * @Project     DB-Model
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
use Maatify\Logger\Logger;
use PDOException;
use PDOStatement;

abstract class PDOBuilder
{

    protected DB $db;
    const TABLE_NAME = 'admin';
    protected string $tableName;
    protected string $tableAlias;

    protected array $cols = [];
    // as key => 0, 1, 2 :  0 is string, 1 is int, 2 is float

    //          ['id'=>1, 'name_en'=>0, 'name_ar'=>0, 'association_id'=>1, 'salary_percentage'=>1, 'required_all'=>1, 'status'=>1];

    protected function ColJoinTypeToString(int $type): float|int|string
    {
        return match ($type) {
            1 => 0,
            2 => 0.00,
            default => '',
        };
    }

    protected function Edit(array $colsValues, string $where, array $wheresVal, array $expectCols = []): bool {
        try {
            $query = 'UPDATE `' . $this->tableName . '` SET ';
            $params = [];
            $setStatements = [];

            foreach ($colsValues as $col => $value) {
                $setStatements[] = "`$col` = ?";
                if (!empty($expectCols) && in_array($col, $expectCols) || $col == 'description') {
                    $params[] = $value;
                } else {
                    $params[] = $this->handleHtmlTags($value);
                }
            }

            $query .= implode(', ', $setStatements);
            $query .= ' WHERE ' . $where;

            $params = array_merge($params, $wheresVal);

            return (bool) $this->ExecuteStatement($query, $params);
        } catch (PDOException $e) {
            $this->logError($e, 'Update ' . $this->tableName . ' ' . $where, __LINE__, array_merge($colsValues, $wheresVal));
            return false;
        }
    }

    protected function Add(array $colsValues, array $expectCols = []): int {
        return $this->AddOrIgnoreAdd($colsValues, $expectCols);
    }

    protected function AddIgnore(array $colsValues, array $expectCols = []): int {
        return $this->AddOrIgnoreAdd($colsValues, $expectCols, true);
    }

    private function AddOrIgnoreAdd(array $colsValues, array $expectCols = [], bool $ignore = false): int
    {
        try {
            $query = 'INSERT ' . ($ignore ? 'IGNORE' : '') . ' INTO `' . $this->tableName . '` (';
            $cols = [];
            $params = [];

            foreach ($colsValues as $col => $value) {
                $cols[] = "`$col`";
                if (!empty($expectCols) && in_array($col, $expectCols) || $col == 'description') {
                    $params[] = $value;
                } else {
                    $params[] = $this->handleHtmlTags($value);
                }
            }

            $query .= implode(', ', $cols);
            $query .= ') VALUES (' . rtrim(str_repeat('?,', count($colsValues)), ',') . ')';

            $this->ExecuteStatement($query, $params);

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->logError($e, 'Insert', __LINE__, $colsValues);
            return 0;
        }
    }

    protected function Delete(string $where, array $wheresVal): bool {
        try {
            $query = 'DELETE FROM `' . $this->tableName . '` WHERE ' . $where;

            return (bool) $this->ExecuteStatement($query, $wheresVal);
        } catch (PDOException $e) {
            $this->logError($e, 'Delete ' . $this->tableName . ' ' . $where, __LINE__, $wheresVal);
            return false;
        }
    }

    private function ExecuteStatement(string $queryString, array $values = []): bool|PDOStatement {
        // Remove extra line breaks from the query string
        $queryString = preg_replace('~[\r\n]+~', '', $queryString);
        // Remove extra whitespace from the query string
        $queryString = preg_replace('!\s+!', ' ', $queryString);
        $query = $this->db->prepare($queryString);
        $query->execute($values);
        return $query;
    }

    private static function handleHtmlTags($value) {
        if (is_string($value)) {
            // Replace commas with a placeholder to avoid issues in SQL
            $value = str_replace(',', '&#44;', $value);
            // Replace double quotes back to standard double quotes
            $value = str_replace('&quot;', '"', $value);
            // Replace single quotes with a proper character
            $value = str_replace(["'", "&#039;"], "’", $value);
            // Escape HTML entities & Sanitize user input to prevent cross-site scripting (XSS) attacks
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }

    protected function CountThisTableRows(string $column = '*', string $where = '', array $wheresVal = []): int
    {
        return $this->CountTableRows($this->tableName, $column, $where, $wheresVal);
    }

    protected function CountTableRows(string $table, string $column = '*', string $where = '', array $wheresVal = []): int
    {
        try {
            $queryString = 'SELECT count(' . $column . ') as count FROM ' . $table . ($where ? ' WHERE ' . $where : '') . ';';
            //            Logger::RecordLog($queryString);
            $query = $this->db->prepare($queryString);
            $query->execute($wheresVal);

            return (int)$query->fetchColumn();
        } catch (PDOException $e) {
            $this->LogError($e, 'CountTableRows: ' . $queryString ?? '', __LINE__);

            return 0;
        }
    }

    protected function SortTable(string $column): bool
    {
        try {
            $queryString = 'SET @num := 0; UPDATE ' . $this->tableName . ' SET `' . $column . '` = @num := (@num+1); ALTER TABLE ' . $this->tableName . ' AUTO_INCREMENT = 1;';

            return $this->ExecuteStatement($queryString);
        } catch (PDOException $e) {
            $this->LogError($e, 'SortTable: ' . $queryString ?? '', __LINE__);

            return false;
        }
    }

    protected function PrepareSelect(string $tablesName, string $columns = '*', string $where = '', array $wheresVal = []): bool|PDOStatement
    {
        $queryString = 'SELECT ' . $columns;
        $queryString .= ' FROM ' . $tablesName;
        if (! empty($where)) {
            $queryString .= ' WHERE ' . $where;
        }

//        Logger::RecordLog($queryString);
        return $this->ExecuteStatement($queryString, $wheresVal);
    }

    protected function FetchRow(PDOStatement $query): array
    {
        return $query->fetch() ? : [];
    }

    protected function FetchRows(PDOStatement $query): array
    {
        return $query->fetchAll() ? : [];
    }

    protected function FetchCol(PDOStatement $query): string
    {
        return $query->fetchColumn() ? : '';
    }

    protected function LogError(PDOException $e, string $queryString, int $line, array $wheresVal = []): array
    {
        Logger::RecordLog(['query' => $queryString, 'wheresVal' => $wheresVal, 'line' => $line, 'exception' => $e,], 'db_errors');

        return [];
    }

    protected function AlertTable($fullAlert): bool
    {
        return $this->SpecialQuery($fullAlert);
    }

    protected function SpecialQuery($queryString): bool
    {
        try {
            return (bool)$this->ExecuteStatement($queryString);
        } catch (PDOException $e) {
            $this->LogError($e, 'SpecialQuery ' . $queryString, __LINE__, []);
            Json::DbError(__LINE__);
            return false;
        }
    }

    protected function HtmlDecode(string $str): string
    {
        $str = str_replace('’', '&#039;', $str);
        $str = str_replace('"', '&quot;', $str);
        $str = htmlspecialchars_decode($str);
        $str = stripslashes($str);
        return html_entity_decode($str);
    }

    protected function Str2JsonFromDB(string $json_string){
        return json_decode($this->HtmlDecode($json_string), true );
    }
}