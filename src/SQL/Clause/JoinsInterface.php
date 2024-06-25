<?php declare(strict_types=1);

namespace DB\SQL\Clause;

interface JoinsInterface
{
    public function selfJoin(string $join): void;

    public function innerJoin(string $join, null|array|object|string $on = null): void;

    public function leftJoin(string $join, null|array|object|string $on = null): void;

    public function rightJoin(string $join, null|array|object|string $on = null): void;

    public function fullJoin(string $join, null|array|object|string $on = null): void;

    public function fullOuterJoin(string $join, null|array|object|string $on = null): void;
}
