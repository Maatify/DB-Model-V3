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

use Maatify\Json\Json;
use Maatify\PostValidatorV2\PostValidatorV2;
use ReflectionClass;

abstract class PaginationModel extends JoinTablesModel
{
    //========================================================================
    protected PostValidatorV2 $postValidator;
    protected int $page_limit = 0;
    protected int|float $offset = 0;
    protected int $pagination = 0;
    protected int $previous = 0;
    protected int $count = 0;
    protected string $class_name;
    protected int $row_id = 0;
    const IDENTIFY_TABLE_ID_COL_NAME = 'id';
    protected string $identify_table_id_col_name = self::IDENTIFY_TABLE_ID_COL_NAME;
    protected array $current_row;

    public function __construct()
    {
        $this->postValidator = PostValidatorV2::obj();
        $page_no = max(((int)$this->postValidator->Optional('page_no', 'page_no') ? : 1), 1);
        $this->page_limit = max(((int)$this->postValidator->Optional('page_limit', 'page_limit') ? : 25), 1);
        $this->pagination = $page_no - 1;
        if ($this->pagination > 0) {
            $this->previous = $this->pagination;
        }
        $this->offset = $this->pagination * $this->page_limit;
        $this->class_name = (new ReflectionClass($this))->getShortName() . '::';
    }

    protected function PaginationNext(int $count): int
    {
        if ($this->pagination + 1 >= $count / $this->page_limit) {
            return 0;
        } else {
            return $this->pagination + 2;
        }
    }

    protected function PaginationLast(int $count): int
    {
        $pages = $count / $this->page_limit;
        if ((int)$pages == $pages) {
            $page = $pages;
        } else {
            $page = (int)$pages + 1;
        }
        if ($count && $this->PaginationPrevious() + 1 > $page) {
            return 0;
        }

        return $page;
    }

    protected function PaginationPrevious(): int
    {
        return $this->previous;
    }

    protected function AddWherePagination(): string
    {
        return " limit $this->page_limit OFFSET $this->offset ";
    }

    protected function PaginationHandler(int $count, array $data, array $others = []): array
    {
        return [
            'pagination' => [
                'count'         => $count,
                'page_previous' => $this->PaginationPrevious(),
                'page_next'     => $this->PaginationNext($count),
                'page_last'     => $this->PaginationLast($count),
                'page_limit'    => $this->page_limit,
                'page_current'  => $this->pagination + 1,
            ],
            'data'       => $data,
            'other'      => $others,
        ];
    }

    protected function JsonHandlerWithOther(array $data, array $other = [], string|int $line = ''): void
    {
        Json::Success(
            [
                'data'  => $data,
                'other' => $other,
            ],
            line: $line
        );
    }


}