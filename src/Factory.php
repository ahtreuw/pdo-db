<?php declare(strict_types=1);

namespace DB;

use Closure;
use DB\SQL\SQL;
use DB\SQL\SQLInterface;
use DB\SQL\Statement\DeleteInterface;
use DB\SQL\Statement\DeleteStatement;
use DB\SQL\Statement\InsertInterface;
use DB\SQL\Statement\InsertStatement;
use DB\SQL\Statement\SelectInterface;
use DB\SQL\Statement\SelectStatement;
use DB\SQL\Statement\TransactionStatement;
use DB\SQL\Statement\TransactionInterface;
use DB\SQL\Statement\UpdateInterface;
use DB\SQL\Statement\UpdateStatement;
use PDO;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

class Factory implements FactoryInterface
{
    public function createSelect(
        DBInterface                                $db,
        null|CacheInterface|CacheItemPoolInterface $cache,
        string                                     $table,
        array|string                               $fields,
        null|string                                $into = null,
        object|array|string|null                   $where = null,
        array|string|null                          $group = null,
        object|array|string|null                   $having = null,
        array|string|null                          $order = null,
        null|int|string                            $limit = null,
        null|int                                   $page = null
    ): SelectInterface
    {
        return new SelectStatement(
            db: $db,
            cache: $cache,
            table: $table,
            fields: $fields,
            into: $into,
            where: $where,
            group: $group,
            having: $having,
            order: $order,
            limit: $limit,
            page: $page
        );
    }

    public function createInsert(
        string               $table,
        array|object         $values,
        null|SelectInterface $select = null,
        array                $updateValues = [],
        bool                 $useIgnore = false,
        bool                 $useReplace = false,
        bool                 $useTransaction = false
    ): InsertInterface
    {
        return new InsertStatement(
            table: $table,
            values: $values,
            select: $select,
            updateValues: $updateValues,
            useIgnore: $useIgnore,
            useReplace: $useReplace,
            useTransaction: $useTransaction
        );
    }

    public function createUpdate(
        string                   $table,
        array|object             $sets,
        null|array|object|string $where,
        bool                     $useTransaction = false
    ): UpdateInterface
    {
        return new UpdateStatement(
            table: $table,
            sets: $sets,
            where: $where,
            useTransaction: $useTransaction
        );
    }

    public function createDelete(
        string                   $table,
        null|array|object|string $where,
        bool                     $useTransaction = false
    ): DeleteInterface
    {
        return new DeleteStatement(
            table: $table,
            where: $where,
            useTransaction: $useTransaction
        );
    }

    public function createTransaction(Closure $closure, float $sleep = 0.2, int $attemptNo = 10): TransactionInterface
    {
        return new TransactionStatement(
            closure: $closure,
            sleep: $sleep,
            attemptNo: $attemptNo
        );
    }

    public function createSQL(string $sql): SQLInterface
    {
        return new SQL(sql: $sql);
    }

    public function createPDO(string $dsn, string $username, string $password, array $options): PDO
    {
        return new PDO($dsn, $username, $password, $options);
    }
}
