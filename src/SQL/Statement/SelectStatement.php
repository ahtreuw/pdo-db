<?php declare(strict_types=1);

namespace DB\SQL\Statement;

use DB\DBInterface;
use DB\SQL\Clause\JoinsTrait;
use DB\SQL\Clause\WhereTrait;
use DB\SQL\FetchDataTrait;
use PDOStatement;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

class SelectStatement extends AbstractStatement implements SelectInterface
{
    use JoinsTrait, WhereTrait, FetchDataTrait;

    protected const INSTRUCTION_SELECT = "SELECT %s%s FROM %s%s%s%s%s%s%s%s;";
    protected const INSTRUCTION_INTO_TABLE = "\r\nINTO %s ";
    protected const INSTRUCTION_WHERE = "\r\nWHERE ";
    protected const INSTRUCTION_GROUP_BY = "\r\nGROUP BY %s ";
    protected const INSTRUCTION_HAVING = "\r\nHAVING %s ";
    protected const INSTRUCTION_ORDER_BY = "\r\nORDER BY %s ";
    protected const INSTRUCTION_LIMIT = "\r\nLIMIT %s ";
    protected const INSTRUCTION_UNION_ALL = "\r\n) UNION ALL (\r\n";
    protected const INSTRUCTION_UNION_UNIQUE = "\r\n) UNION DISTINCT (\r\n";
    protected const UNION_FORMAT = "((%s)) AS %s";
    protected const ALIAS_FORMAT = "%s AS %s";
    protected const SUB_SELECT_FORMAT = "(%s) AS %s";
    protected const LIMIT_FORMAT = "%d, %d";
    protected const DEFAULT_LIMIT = 1000;
    private array $fields = [];
    private array $groupBy = [];
    private array $having = [];
    private array $orderBy = [];
    private null|int|string $limit = null;
    private null|int $page = null;
    private null|string $into = null;
    private array $union = [];
    private bool $unionAll = false;

    public function __construct(
        private readonly DBInterface                                $db,
        private readonly null|CacheInterface|CacheItemPoolInterface $cacheAdapter,
        string                                                      $table,
        string|array                                                $fields,
        null|string                                                 $into = null,
        null|array|object|string                                    $where = null,
        null|array|string                                           $group = null,
        null|array|string                                           $having = null,
        null|array|string                                           $order = null,
        null|int|string                                             $limit = null,
        null|int                                                    $page = null
    )
    {
        parent::__construct(table: $table);
        $this->fields($fields);
        $this->setInto($into);
        $this->where($where);
        $this->groupBy($group);
        $this->having($having);
        $this->orderBy($order);
        $this->setLimit($limit);
        $this->setPage($page);
    }


    public function exec(): PDOStatement
    {
        return $this->db->exec($this->__toString(), $this->getParameters());
    }

    public function __toString(): string
    {
        if ($this->query) {
            return $this->query;
        }
        return $this->query = sprintf(self::INSTRUCTION_SELECT,
            $this->prepareFields($this->fields),
            $this->prepareInto($this->into),
            $this->prepareTable($this->table),
            $this->prepareSelfJoins($this->selfJoins),
            $this->prepareJoins($this->joins),
            $this->prepareWhere($this->where, true, self::INSTRUCTION_WHERE),
            $this->prepareGroupBy($this->groupBy),
            $this->prepareHaving($this->having),
            $this->prepareOrderBy($this->orderBy),
            $this->prepareLimit($this->limit, $this->page)
        );
    }

    public function union(null|SelectInterface $union): SelectInterface
    {
        $this->validateRuntime();
        if (is_null($union)) {
            $this->union = [];
        }
        $this->union[] = $union;
        return $this;
    }

    public function setUnionAll(bool $unionAll): void
    {
        $this->validateRuntime();
        $this->unionAll = $unionAll;
    }

    public function fields(null|array|string $fields): void
    {
        $this->fields = $this->mergeValues($this->fields, $fields);
    }

    public function setInto(null|string $into): void
    {
        $this->validateRuntime();
        $this->into = $into;
    }

    public function groupBy(null|array|string $groupBy): void
    {
        $this->groupBy = $this->mergeValues($this->groupBy, $groupBy);
    }

    public function having(null|array|object|string $having): void
    {
        $this->having = $this->mergeValues($this->having, $having);
    }

    public function orderBy(null|array|string $orderBy): void
    {
        $this->orderBy = $this->mergeValues($this->orderBy, $orderBy);
    }

    public function setLimit(null|int|string $limit): void
    {
        $this->validateRuntime();
        $this->limit = $limit;
    }

    public function setPage(null|int $page): void
    {
        $this->validateRuntime();
        $this->page = $page;
    }

    private function prepareFields(array|string $fields): string
    {
        if (is_string($fields)) {
            return $fields;
        }
        $return = [];
        foreach ($fields as $key => $field) {
            if (is_numeric($key)) {
                $return[] = strval($field);
                continue;
            }
            if ($field instanceof SelectInterface) {
                $return[] = sprintf(self::SUB_SELECT_FORMAT, $this->mergeSubSelect($field), $this->prepareKey($key));
                continue;
            }
            $return[] = sprintf(self::ALIAS_FORMAT, $this->prepareKey($key), $this->prepareKey($field));
        }
        return implode(self::LIST_SEPARATOR, $return);
    }

    private function prepareInto(null|string $into): string
    {
        if (empty($into)) {
            return self::EMPTY_STRING;
        }
        return sprintf(self::INSTRUCTION_INTO_TABLE, $into);
    }

    private function prepareTable(string $table): string
    {
        if (empty($this->union)) {
            return $table;
        }
        $return = [];
        foreach ($this->union as $select) {
            $return[] = $this->mergeSubSelect($select);
        }
        $separator = $this->unionAll ? self::INSTRUCTION_UNION_ALL : self::INSTRUCTION_UNION_UNIQUE;
        return sprintf(self::UNION_FORMAT, implode($separator, $return), $table);
    }

    private function prepareGroupBy(array $groupBy): string
    {
        if (empty($groupBy)) {
            return self::EMPTY_STRING;
        }
        $return = [];
        foreach ($groupBy as $field) {
            $return[] = $this->prepareKey($field);
        }
        return sprintf(self::INSTRUCTION_GROUP_BY, implode(self::LIST_SEPARATOR, $return));
    }

    private function prepareHaving(array $having): string
    {
        if (empty($having)) {
            return self::EMPTY_STRING;
        }
        return sprintf(self::INSTRUCTION_HAVING, $this->prepareWhere($having));
    }

    private function prepareOrderBy(array $orderBy): string
    {
        if (empty($orderBy)) {
            return self::EMPTY_STRING;
        }
        $return = [];
        foreach ($orderBy as $field) {
            $return[] = $this->prepareKey($field);
        }
        return sprintf(self::INSTRUCTION_ORDER_BY, implode(self::LIST_SEPARATOR, $return));
    }

    private function prepareLimit(null|int|string $limit, null|int $page): string
    {
        if (is_null($limit) && is_null($page)) {
            return self::EMPTY_STRING;
        }
        if (is_numeric($limit) && $page) {
            return sprintf(self::INSTRUCTION_LIMIT,
                sprintf(self::LIMIT_FORMAT, intval($limit) * $page, $limit));
        }
        if (is_null($limit)) {
            return sprintf(self::INSTRUCTION_LIMIT,
                sprintf(self::LIMIT_FORMAT, $page * self::DEFAULT_LIMIT, self::DEFAULT_LIMIT));
        }
        return sprintf(self::INSTRUCTION_LIMIT, strval($limit));
    }
}
