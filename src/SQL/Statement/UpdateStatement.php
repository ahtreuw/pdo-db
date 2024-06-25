<?php declare(strict_types=1);

namespace DB\SQL\Statement;

use DB\SQL\Clause\ExecutableTrait;
use DB\SQL\Clause\JoinsTrait;
use DB\SQL\Clause\WhereTrait;

class UpdateStatement extends AbstractStatement implements UpdateInterface
{
    use JoinsTrait, WhereTrait, ExecutableTrait;

    protected const INSTRUCTION_UPDATE = "UPDATE %s%s%s \r\nSET %s \r\nWHERE %s;";

    public function __construct(
        string                        $table,
        private readonly array|object $sets,
        null|array|object|string      $where = null,
        bool                          $useTransaction = false
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
        return $this->query = sprintf(self::INSTRUCTION_UPDATE,
            $this->prepareKey($this->table),
            $this->prepareSelfJoins($this->selfJoins),
            $this->prepareJoins($this->joins),
            $this->prepareSets($this->sets),
            $this->prepareWhere($this->where)
        );
    }

    private function prepareSets(object|array $data): string
    {
        $sets = [];
        foreach ($data as $key => $value) {
            if ($value instanceof SelectInterface) {
                $sets[] = sprintf("%s=(%s)", $this->prepareKey($key), $this->mergeSubSelect($value));
                continue;
            }
            $sets[] = sprintf('%s=%s',
                $this->prepareKey($key),
                $this->prepareValue($value)
            );
        }
        return implode(self::LIST_SEPARATOR, $sets);
    }
}
