[![Current version](https://img.shields.io/packagist/v/maatify/db-model-v3)][pkg]
[![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/maatify/db-model-v3)][pkg]
[![Monthly Downloads](https://img.shields.io/packagist/dm/maatify/db-model-v3)][pkg-stats]
[![Total Downloads](https://img.shields.io/packagist/dt/maatify/db-model-v3)][pkg-stats]
[![Stars](https://img.shields.io/packagist/stars/maatify/db-model-v3)](https://github.com/maatify/db-model-v3/stargazers)

[pkg]: <https://packagist.org/packages/maatify/db-model-v3>
[pkg-stats]: <https://packagist.org/packages/maatify/db-model-v3/stats>

# DB-Model

maatify.dev MySql Database PDO Model handler, known by our team

# Installation

```shell
composer require maatify/db-model-v3
```

# Usage
#### Create DbConnector Connection Class Extends Model

```PHP
<?php

namespace Maatify\files;

use Maatify\Json\Json;use Maatify\Logger\Logger;use Maatify\Model\Model;use model\DB;use PDOException;

abstract class DbConnector extends Model
{
    private static DB $connection;

    public function __construct()
    {
        if(empty(static::$connection)){
            try {
                static::$connection = new DB([
                    'host'     => __DB_HOST__,
                    'user'     => __DB_USER__,
                    'password' => __DB_PASSa__,
                    'dbname'   => __DB_DATABASE__,
                    'charset'  => 'utf8mb4',
                ]);
            }
            catch (PDOException $e){
                Logger::RecordLog(message: [$e->getMessage(), (int)$e->getCode()], 
                logFile: 'app_connections');
                Json::DbError(__LINE__);
            }
        }
        parent::__construct();
        $this->pdo = static::$connection;
    }
}
```
#### Create Connection Table Handler Class Extends DbConnector
```PHP
<?php

class Info extends DbConnector
{
    const TABLE_NAME = 'info';
    protected string $tableName = self::TABLE_NAME;
    const IDENTIFY_TABLE_ID_COL_NAME = 'id';
    protected string $identify_table_id_col_name = self::IDENTIFY_TABLE_ID_COL_NAME;

    public function Insert(int $id, string $name): void
    {
        $this->Add(
            [
                $this->identify_table_id_col_name   => $id,
                'name' => $name,
            ]
        );
    }

    public function SelectAll(): array
    {
        return $this->Rows($this->tableName);
    }

    public function SelectById(int $id): array
    {
        return $this->Rows($this->tableName, '*', '`id` = ?', [$id]);
    }

    public function UpdateNameByID(int $id, string $name): bool
    {
        return $this->Edit(['name'=>$name], '`id` = ?', [$id]);
    }
}
```
