<?php declare(strict_types=1);

namespace DB\SQL\Clause;

use DB\SQL\Statement\SelectInterface;

trait WhereTrait
{
    protected array $where = [];


    public function where(object|array|string|null $where): void
    {
        $this->where = $this->mergeValues($this->where, $where);
    }

    protected function prepareWhere(
        null|array|object|string $where,
        bool                     $prepareValue = true,
        string                   $prefix = self::EMPTY_STRING
    ): string
    {
        if (is_string($where) || empty($where)) {
            return $where ? $prefix . $where : '';
        }
        $wheres = [];
        foreach ($where as $key => $item) {
            $wheres[] = $this->prepareWhereItem($key, $item, $prepareValue);
        }
        return $prefix . implode(' AND ', $wheres);
    }

    private function prepareWhereItem(string $key, mixed $item, bool $prepareValue): string
    {
        if (is_array($item)) {
            return sprintf('%s IN (%s)', $this->prepareKey($key), $this->prepareWhereItemValues($item));
        }
        if ($item instanceof SelectInterface) {
            if (str_contains($key, ' ')) {
                return sprintf('%s (%s)', $key, $this->mergeSubSelect($item));
            }
            return sprintf('%s = (%s)', $this->prepareKey($key), $this->mergeSubSelect($item));
        }
        if (is_numeric($key) && is_string($item)) {
            return $item;
        }
        if (is_null($item) && str_contains($key, ' ')) {
            return sprintf('%s', $key);
        }
        if (is_null($item)) {
            return sprintf('%s IS NULL', $this->prepareKey($key));
        }
        if (str_contains($key, ' ')) {
            return sprintf('%s %s', $key, $prepareValue ? $this->prepareValue($item) : $item);
        }
        return sprintf('%s=%s', $this->prepareKey($key), $prepareValue ? $this->prepareValue($item) : $item);
    }

    protected function prepareWhereItemValues(array $values): string
    {
        $keys = [];
        foreach ($values as $item) {
            if ($item instanceof SelectInterface) {
                return $this->mergeSubSelect($item);
            }
            $keys[] = $this->createParameter($item);
        }
        return implode(self::LIST_SEPARATOR, $keys);
    }

    protected function mergeValues(array $list, object|array|string|null $values): array
    {
        $this->validateRuntime();
        if (empty($values)) {
            return []; // reset list
        }
        if (is_string($values)) {
            $list[] = $values;
            return $list;
        }
        foreach ($values as $key => $item) {
            $list[$key] = $item;
        }
        return $list;
    }

}
