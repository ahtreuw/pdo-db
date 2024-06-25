<?php declare(strict_types=1);

namespace DB\SQL\Clause;

interface WhereInterface
{
    public function where(object|array|string|null $where): void;

    public function createParameter(float|bool|int|string|null $value): string;
}
