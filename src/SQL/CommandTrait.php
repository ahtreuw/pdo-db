<?php declare(strict_types=1);

namespace DB\SQL;

use Closure;
use DB\SQL\Statement\SelectInterface;
use PDOStatement;

trait CommandTrait
{


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
    ): SelectInterface
    {
        return $this->factory->createSelect(
            db: $this,
            cacheAdapter: $this->cacheAdapter,
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

    public function insert(
        string               $table,
        array|object         $values,
        null|SelectInterface $select = null,
        array                $updateValues = [],
        bool                 $useIgnore = false,
        bool                 $useReplace = false,
        bool                 $useTransaction = false
    ): PDOStatement
    {
        return $this->factory->createInsert(
            table: $table,
            values: $values,
            select: $select,
            updateValues: $updateValues,
            useIgnore: $useIgnore,
            useReplace: $useReplace,
            useTransaction: $useTransaction
        )->exec($this);
    }

    public function update(
        string                   $table,
        array|object             $sets,
        null|array|object|string $where = null,
        bool                     $useTransaction = false
    ): PDOStatement
    {
        return $this->factory->createUpdate(
            table: $table,
            sets: $sets,
            where: $where,
            useTransaction: $useTransaction
        )->exec($this);
    }

    public function delete(
        string                   $table,
        null|array|object|string $where,
        bool                     $useTransaction = false
    ): PDOStatement
    {
        return $this->factory->createDelete(
            table: $table,
            where: $where,
            useTransaction: $useTransaction
        )->exec($this);
    }

    public function transaction(Closure $closure, $sleep = 0.2, $attemptNo = 10): mixed
    {
        return $this->factory->createTransaction(
            closure: $closure,
            sleep: $sleep,
            attemptNo: $attemptNo
        )->exec($this);
    }

    public function sql(string $sql, array $parameters = []): SQLInterface
    {
        return $this->factory->createSQL($sql, $parameters);
    }
}