<?php declare(strict_types=1);

namespace DB\SQL\Statement;

use DB\SQL\Clause\ExecutableTrait;
use DB\SQL\SQLInterface;

class InsertStatement extends AbstractStatement implements InsertInterface
{
    use ExecutableTrait;

    protected const INSTRUCTION_INSERT_INTO = "INSERT INTO %s%s \r\nVALUES %s%s;";
    protected const INSTRUCTION_INSERT_IGNORE_INTO = "INSERT IGNORE INTO %s%s \r\nVALUES %s%s;";
    protected const INSTRUCTION_REPLACE_INTO = "REPLACE INTO %s%s \r\nVALUES %s%s;";
    protected const INSTRUCTION_INSERT_INTO_SELECT = "INSERT INTO %s%s \r\n%s%s;";
    protected const INSTRUCTION_INSERT_IGNORE_INTO_SELECT = "INSERT IGNORE INTO %s%s \r\n%s%s;";
    protected const INSTRUCTION_REPLACE_INTO_SELECT = "REPLACE INTO %s%s \r\n%s%s;";
    protected const INSTRUCTION_ON_DUPLICATE_KEY_UPDATE = "\r\nON DUPLICATE KEY UPDATE %s";

    public function __construct(
        string                                $table,
        private readonly array|object         $values,
        private readonly null|SelectInterface $select = null,
        private readonly null|array|object    $updateValues = null,
        private readonly bool                 $useIgnore = false,
        private readonly bool                 $useReplace = false,
        bool                                  $useTransaction = false
    )
    {
        parent::__construct(table: $table, useTransaction: $useTransaction);
    }

    public function __toString(): string
    {
        if ($this->query) {
            return $this->query;
        }
        return sprintf($this->getInstruction(),
            $this->prepareKey($this->table),
            $this->getInsertColumns($this->values),
            $this->getInsertValues($this->values, $this->select),
            $this->getUpdateValues($this->updateValues)
        );
    }

    private function getInstruction(): string
    {
        if ($this->select && $this->useReplace) {
            return self::INSTRUCTION_REPLACE_INTO_SELECT;
        }
        if ($this->select && $this->useIgnore) {
            return self::INSTRUCTION_INSERT_IGNORE_INTO_SELECT;
        }
        if ($this->select) {
            return self::INSTRUCTION_INSERT_INTO_SELECT;
        }
        if ($this->useReplace) {
            return self::INSTRUCTION_REPLACE_INTO;
        }
        if ($this->useIgnore) {
            return self::INSTRUCTION_INSERT_IGNORE_INTO;
        }
        return self::INSTRUCTION_INSERT_INTO;
    }

    private function getInsertColumns(array|object $data): string
    {
        $columns = [];
        foreach ($data as $key => $value) {
            if (is_array($value) || (is_object($value) && $value instanceof SQLInterface === false)) {
                return $this->getInsertColumns($value);
            }
            $columns[] = is_numeric($key) ? $this->prepareKey($value) : $this->prepareKey($key);
        }
        return $columns ? $this->parenthesizeList(...$columns) : '';
    }

    private function getInsertValues(array|object $values, null|SelectInterface $select = null): string
    {
        if ($select instanceof SelectInterface) {
            return $this->mergeSubSelect($select);
        }
        $return = [];
        $parenthesizeList = false;
        foreach ($values as $value) {
            if (is_array($value) || (is_object($value) && $value instanceof SQLInterface === false)) {
                $return[] = $this->getInsertValues($value);
                continue;
            }
            $parenthesizeList = true;
            $return[] = $this->prepareValue($value);
        }
        if ($parenthesizeList) {
            return $this->parenthesizeList(...$return);
        }
        return implode(self::LIST_SEPARATOR, $return);
    }

    private function getUpdateValues(array $updateValues): string
    {
        if (empty($updateValues)) {
            return self::EMPTY_STRING;
        }
        $sets = [];
        foreach ($updateValues as $key => $value) {
            $sets[] = sprintf('%s=%s',
                $this->prepareKey($key),
                $this->prepareValue($value)
            );
        }
        return sprintf(self::INSTRUCTION_ON_DUPLICATE_KEY_UPDATE, implode(self::LIST_SEPARATOR, $sets));
    }

    protected function parenthesizeList(string ...$list): string
    {
        return sprintf('(%s)', implode(self::LIST_SEPARATOR, $list));
    }
}
