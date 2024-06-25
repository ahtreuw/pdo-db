<?php declare(strict_types=1);

namespace DB;

use DB\SQL\SQLInterface;
use DB\SQL\Statement\DeleteInterface;
use DB\SQL\Statement\InsertInterface;
use DB\SQL\Statement\SelectInterface;
use DB\SQL\Statement\UpdateInterface;
use PDO;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

interface FactoryInterface
{
    public function createSelect(
        DBInterface                                $db,
        null|CacheInterface|CacheItemPoolInterface $cache,
        string                                     $table,
        string|array                               $fields,
        null|string                                $into = null,
        null|array|object|string                   $where = null,
        null|array|string                          $group = null,
        null|array|object|string                   $having = null,
        null|array|string                          $order = null,
        null|int|string                            $limit = null,
        null|int                                   $page = null
    ): SelectInterface;

    public function createInsert(
        string               $table,
        array|object         $values,
        null|SelectInterface $select = null,
        array                $updateValues = [],
        bool                 $useIgnore = false,
        bool                 $useReplace = false,
        bool                 $useTransaction = false
    ): InsertInterface;

    public function createUpdate(
        string                   $table,
        array|object             $sets,
        null|array|object|string $where,
        bool                     $useTransaction = false
    ): UpdateInterface;

    public function createDelete(
        string                   $table,
        null|array|object|string $where,
        bool                     $useTransaction = false
    ): DeleteInterface;

    public function createSQL(string $sql): SQLInterface;

    public function createPDO(string $dsn, string $username, string $password, array $options): PDO;
}
