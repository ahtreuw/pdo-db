<?php declare(strict_types=1);

namespace DB\SQL\Statement;

use DB\SQL\Clause\ExecutableTrait;
use DB\SQL\Clause\WhereTrait;

class DeleteStatement extends AbstractStatement implements DeleteInterface
{
    use WhereTrait, ExecutableTrait;

    protected const INSTRUCTION_DELETE = "DELETE FROM %s \r\nWHERE %s;";

    public function __construct(
        string                   $table,
        null|array|object|string $where = null,
        bool                     $useTransaction = false
    )
    {
        parent::__construct(table: $table, useTransaction: $useTransaction);
        $this->where($where);
    }

    public function __toString(): string
    {
        if ($this->query) {
            return $this->query;
        }
        return $this->query = sprintf(self::INSTRUCTION_DELETE,
            $this->prepareKey($this->table),
            $this->prepareWhere($this->where)
        );
    }
}
