<?php declare(strict_types=1);

namespace DB\SQL\Clause;

trait JoinsTrait
{
    protected const INNER_JOIN = "\r\nINNER JOIN %s%s ";
    protected const LEFT_JOIN = "\r\nLEFT JOIN %s%s ";
    protected const RIGHT_JOIN = "\r\nRIGHT JOIN %s%s ";
    protected const FULL_JOIN = "\r\nFULL JOIN %s%s ";
    protected const FULL_OUTER_JOIN = "\r\nFULL OUTER JOIN %s%s ";
    protected const JOIN_ON = " ON %s ";
    protected array $selfJoins = [];
    protected array $joins = [];

    protected function prepareSelfJoins(array $selfJoins): string
    {
        if ($selfJoins) {
            return self::LIST_SEPARATOR . implode(self::LIST_SEPARATOR, $selfJoins) . ' ';
        }
        return self::EMPTY_STRING;
    }

    protected function prepareJoins(null|array $joins): string
    {
        $return = [];
        foreach ($joins as $join) {
            $return[] = sprintf($join[0], $join[1], $join[2] ? $this->joinOn($join[2]) : '');
        }
        return implode('', $return);
    }

    public function selfJoin(null|string $join): void
    {
        if (is_null($join)) {
            $this->selfJoins = [];
        }
        $this->selfJoins[] = $join;
    }

    public function innerJoin(string $join, null|array|object|string $on = null): void
    {
        $this->joins[] = [self::INNER_JOIN, $join, $on];
    }

    public function leftJoin(string $join, null|array|object|string $on = null): void
    {
        $this->joins[] = [self::LEFT_JOIN, $join, $on];
    }

    public function rightJoin(string $join, null|array|object|string $on = null): void
    {
        $this->joins[] = [self::RIGHT_JOIN, $join, $on];
    }

    public function fullJoin(string $join, null|array|object|string $on = null): void
    {
        $this->joins[] = [self::FULL_JOIN, $join, $on];
    }

    public function fullOuterJoin(string $join, null|array|object|string $on = null): void
    {
        $this->joins[] = [self::FULL_OUTER_JOIN, $join, $on];
    }

    public function joinOn(object|array|string|null $on): string
    {
        if (is_null($on)) {
            return self::EMPTY_STRING;
        }
        return sprintf(self::JOIN_ON, $this->prepareWhere($on, false));
    }

}
