<?php declare(strict_types=1);

namespace DB\SQL\Statement;

use DateInterval;
use DB\SQL\Clause\JoinsInterface;
use DB\SQL\Clause\WhereInterface;
use PDO;
use PDOStatement;
use stdClass;
use Throwable;

interface SelectInterface extends JoinsInterface, WhereInterface
{
    /**
     * @throws Throwable
     */
    public function exec(): PDOStatement;

    /**
     * Fetches the remaining rows from a result set
     *
     * @return array Returns an array containing all the result set rows.
     * @throws Throwable
     * @link https://php.net/manual/en/pdostatement.fetchall.php
     */
    public function fetchAll(
        int                        $mode = PDO::FETCH_DEFAULT,
        null|int|DateInterval|bool $cacheTtl = false,
        mixed                      ...$args
    ): array;

    /**
     * Fetches the next row from a result set
     *
     * @return mixed The return value on success depends on the fetch type, on failure FALSE is returned.
     * @throws Throwable
     * @link https://php.net/manual/en/pdostatement.fetch.php
     */
    public function fetch(
        int                        $mode = PDO::FETCH_DEFAULT,
        null|int|DateInterval|bool $cacheTtl = false,
        int                        $cursorOrientation = PDO::FETCH_ORI_NEXT,
        int                        $cursorOffset = 0
    ): mixed;

    /**
     * Fetches the next row and returns it as an object.
     *
     * @return object|null an instance of the required class with property names that
     *  correspond to the column names or <b>NULL</b> on failure.
     * @throws Throwable
     * @link https://php.net/manual/en/pdostatement.fetchobject.php
     */
    public function fetchObject(
        string                     $class = 'stdClass',
        array                      $constructorArgs = [],
        null|int|DateInterval|bool $cacheTtl = false
    ): null|object;

    /**
     * Returns a single column from the next row of a result set.
     *
     * @return mixed Returns a single column from the next row of a result set or FALSE if there are no more rows.
     * @throws Throwable
     * @link https://php.net/manual/en/pdostatement.fetchcolumn.php
     */
    public function fetchColumn(
        int                        $column = 0,
        null|int|DateInterval|bool $cacheTtl = false
    ): mixed;

    public function union(null|SelectInterface $union): SelectInterface;

    public function setUnionAll(bool $unionAll): void;

    public function fields(null|array|string $fields): void;

    public function setInto(null|string $into): void;

    public function groupBy(null|array|string $groupBy): void;

    public function having(null|array|object|string $having): void;

    public function orderBy(null|array|string $orderBy): void;

    public function setLimit(null|int|string $limit): void;

    public function setPage(null|int $page): void;
}
