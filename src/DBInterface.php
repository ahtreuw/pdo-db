<?php declare(strict_types=1);

namespace DB;

use Closure;
use DB\SQL\SQLInterface;
use DB\SQL\Statement\SelectInterface;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

interface DBInterface
{
    public function prefix(null|string $prefix): DBInterface;

    /**
     * @throws PDOException
     */
    public function connect(): void;

    public function disconnect(): void;

    /**
     * @throws PDOException
     */
    public function exec(string $query, array $parameters = []): PDOStatement;

    /**
     * @throws Throwable
     */
    public function select(
        string                   $table,
        string|array             $fields,
        null|string              $into = null,
        null|array|object|string $where = null,
        null|array|string        $group = null,
        null|array|object|string $having = null,
        null|array|string        $order = null,
        null|int|string          $limit = null,
        null|int                 $page = null
    ): SelectInterface;

    /**
     * @throws Throwable
     */
    public function insert(
        string               $table,
        array|object         $values,
        null|SelectInterface $select = null,
        array                $updateValues = [],
        bool                 $useIgnore = false,
        bool                 $useReplace = false,
        bool                 $useTransaction = false
    ): PDOStatement;

    /**
     * @throws Throwable
     */
    public function update(
        string                   $table,
        array|object             $sets,
        null|array|object|string $where = null,
        bool                     $useTransaction = false
    ): PDOStatement;

    /**
     * @throws Throwable
     */
    public function delete(
        string                   $table,
        null|array|object|string $where,
        bool                     $useTransaction = false
    ): PDOStatement;

    /**
     * @throws Throwable
     */
    public function transaction(Closure $closure, $sleep = 0.2, $attemptNo = 10): mixed;

    public function sql(string $sql): SQLInterface;

    public function lastInsertId(): ?string;

    /**
     * @throws PDOException
     */
    public function beginTransaction(): bool;

    /**
     * @throws PDOException
     */
    public function commit(): bool;

    /**
     * @throws PDOException
     */
    public function rollBack(): bool;

    public function inTransaction(): bool;

    public function prepare(string $query, array $options): PDOStatement|false;

    public function quote(string $string, int $type = PDO::PARAM_STR): string|false;

    public function query(string $query, int|null $fetchMode = null, ...$fetch_mode_args): PDOStatement|false;
}